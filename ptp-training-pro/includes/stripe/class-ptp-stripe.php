<?php
/**
 * Stripe Integration - v4.1
 * Enhanced PaymentIntents, Connect, and Webhook handling
 */

if (!defined('ABSPATH')) exit;

class PTP_Stripe {

    private static $secret_key;
    private static $publishable_key;
    private static $webhook_secret;
    private static $platform_fee_percent = 20;

    public static function init() {
        self::$secret_key = get_option('ptp_stripe_secret_key');
        self::$publishable_key = get_option('ptp_stripe_publishable_key');
        self::$webhook_secret = get_option('ptp_stripe_webhook_secret');

        $custom_fee = get_option('ptp_platform_fee_percent');
        if ($custom_fee) {
            self::$platform_fee_percent = floatval($custom_fee);
        }
    }

    /**
     * Check if Stripe is configured
     */
    public static function is_configured() {
        return !empty(self::$secret_key) && !empty(self::$publishable_key);
    }

    /**
     * Make Stripe API request
     */
    private static function api_request($endpoint, $method = 'POST', $data = array()) {
        if (!self::$secret_key) {
            return new WP_Error('stripe_not_configured', 'Stripe API keys are not configured');
        }

        $url = 'https://api.stripe.com/v1/' . $endpoint;

        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Bearer ' . self::$secret_key,
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Stripe-Version' => '2023-10-16'
            ),
            'timeout' => 30
        );

        if (!empty($data)) {
            $args['body'] = $data;
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            error_log('PTP Stripe API Error: ' . $response->get_error_message());
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code >= 400) {
            $error_message = isset($body['error']['message']) ? $body['error']['message'] : 'Unknown Stripe error';
            error_log('PTP Stripe API Error (' . $status_code . '): ' . $error_message);
            return new WP_Error('stripe_api_error', $error_message, array('status' => $status_code));
        }

        return $body;
    }

    // ==========================================
    // CUSTOMER MANAGEMENT
    // ==========================================

    /**
     * Get or create Stripe customer for a user
     */
    public static function get_or_create_customer($user_id) {
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return new WP_Error('user_not_found', 'User not found');
        }

        // Check if customer ID exists
        $customer_id = get_user_meta($user_id, 'ptp_stripe_customer_id', true);

        if ($customer_id) {
            // Verify customer still exists in Stripe
            $customer = self::api_request('customers/' . $customer_id, 'GET');
            if (!is_wp_error($customer) && !isset($customer['deleted'])) {
                return $customer_id;
            }
        }

        // Create new customer
        $customer = self::api_request('customers', 'POST', array(
            'email' => $user->user_email,
            'name' => $user->display_name,
            'metadata[user_id]' => $user_id,
            'metadata[platform]' => 'ptp_training'
        ));

        if (is_wp_error($customer)) {
            return $customer;
        }

        // Save customer ID
        update_user_meta($user_id, 'ptp_stripe_customer_id', $customer['id']);
        PTP_Database::save_stripe_customer_id($user_id, $customer['id']);

        return $customer['id'];
    }

    // ==========================================
    // CONNECT ACCOUNT MANAGEMENT
    // ==========================================

    /**
     * Create Stripe Connect account for trainer
     */
    public static function create_connect_account($trainer_id) {
        global $wpdb;
        $trainer = $wpdb->get_row($wpdb->prepare(
            "SELECT t.*, u.user_email FROM {$wpdb->prefix}ptp_trainers t
             LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID
             WHERE t.id = %d",
            $trainer_id
        ));

        if (!$trainer) {
            return new WP_Error('not_found', 'Trainer not found');
        }

        // Create Express account
        $account = self::api_request('accounts', 'POST', array(
            'type' => 'express',
            'email' => $trainer->user_email,
            'capabilities[card_payments][requested]' => 'true',
            'capabilities[transfers][requested]' => 'true',
            'business_type' => 'individual',
            'metadata[trainer_id]' => $trainer_id,
            'metadata[platform]' => 'ptp_training'
        ));

        if (is_wp_error($account) || isset($account['error'])) {
            return new WP_Error('stripe_error', $account['error']['message'] ?? 'Failed to create Stripe account');
        }

        // Save account ID
        $wpdb->update(
            "{$wpdb->prefix}ptp_trainers",
            array('stripe_account_id' => $account['id']),
            array('id' => $trainer_id)
        );

        return $account['id'];
    }

    /**
     * Create onboarding link for Connect account
     */
    public static function create_connect_account_link($trainer_id) {
        $trainer = PTP_Database::get_trainer($trainer_id);

        if (!$trainer) {
            return new WP_Error('not_found', 'Trainer not found');
        }

        // Create account if doesn't exist
        if (!$trainer->stripe_account_id) {
            $account_id = self::create_connect_account($trainer_id);
            if (is_wp_error($account_id)) {
                return $account_id;
            }
        } else {
            $account_id = $trainer->stripe_account_id;
        }

        // Create account link
        $link = self::api_request('account_links', 'POST', array(
            'account' => $account_id,
            'refresh_url' => home_url('/trainer-dashboard/?stripe=refresh'),
            'return_url' => home_url('/trainer-dashboard/?stripe=complete'),
            'type' => 'account_onboarding'
        ));

        if (is_wp_error($link) || isset($link['error'])) {
            return new WP_Error('stripe_error', $link['error']['message'] ?? 'Failed to create account link');
        }

        return $link['url'];
    }

    /**
     * Check if trainer's Stripe account is fully onboarded
     */
    public static function is_onboarding_complete($trainer_id) {
        global $wpdb;
        $trainer = PTP_Database::get_trainer($trainer_id);

        if (!$trainer || !$trainer->stripe_account_id) {
            return false;
        }

        $account = self::api_request('accounts/' . $trainer->stripe_account_id, 'GET');

        if (is_wp_error($account) || isset($account['error'])) {
            return false;
        }

        $complete = $account['charges_enabled'] && $account['payouts_enabled'];

        // Update database
        if ($complete && !$trainer->stripe_onboarding_complete) {
            $wpdb->update(
                "{$wpdb->prefix}ptp_trainers",
                array('stripe_onboarding_complete' => 1),
                array('id' => $trainer_id)
            );
        }

        return $complete;
    }

    // ==========================================
    // PAYMENT INTENTS (Single Session Payments)
    // ==========================================

    /**
     * Create PaymentIntent for a single session booking
     */
    public static function create_session_payment_intent($session_data) {
        $trainer = PTP_Database::get_trainer($session_data['trainer_id']);

        if (!$trainer) {
            return new WP_Error('trainer_not_found', 'Trainer not found');
        }

        // Get or create customer
        $customer_id = self::get_or_create_customer($session_data['customer_id']);
        if (is_wp_error($customer_id)) {
            return $customer_id;
        }

        // Calculate amounts
        $amount_cents = round($session_data['price'] * 100);
        $platform_fee_cents = round($session_data['price'] * (self::$platform_fee_percent / 100) * 100);
        $trainer_payout = $session_data['price'] - ($session_data['price'] * (self::$platform_fee_percent / 100));

        // Build description
        $description = 'Private Training Session';
        if (!empty($session_data['player_name'])) {
            $description .= ' for ' . $session_data['player_name'];
        }
        $description .= ' with ' . $trainer->display_name;

        $intent_data = array(
            'amount' => $amount_cents,
            'currency' => 'usd',
            'customer' => $customer_id,
            'description' => $description,
            'metadata[session_type]' => 'single_session',
            'metadata[trainer_id]' => $session_data['trainer_id'],
            'metadata[customer_id]' => $session_data['customer_id'],
            'metadata[player_name]' => $session_data['player_name'] ?? '',
            'metadata[player_age]' => $session_data['player_age'] ?? '',
            'metadata[session_date]' => $session_data['session_date'] ?? '',
            'metadata[location]' => $session_data['location'] ?? '',
            'metadata[platform]' => 'ptp_training'
        );

        // If trainer has Connect account, use destination charges
        if ($trainer->stripe_account_id && $trainer->stripe_onboarding_complete) {
            $intent_data['application_fee_amount'] = $platform_fee_cents;
            $intent_data['transfer_data[destination]'] = $trainer->stripe_account_id;
        }

        // Create PaymentIntent
        $intent = self::api_request('payment_intents', 'POST', $intent_data);

        if (is_wp_error($intent)) {
            return $intent;
        }

        return array(
            'payment_intent_id' => $intent['id'],
            'client_secret' => $intent['client_secret'],
            'amount' => $session_data['price'],
            'platform_fee' => $session_data['price'] * (self::$platform_fee_percent / 100),
            'trainer_payout' => $trainer_payout,
            'customer_id' => $customer_id
        );
    }

    /**
     * Confirm a PaymentIntent (server-side confirmation if needed)
     */
    public static function confirm_payment_intent($payment_intent_id) {
        return self::api_request('payment_intents/' . $payment_intent_id . '/confirm', 'POST');
    }

    /**
     * Retrieve a PaymentIntent
     */
    public static function get_payment_intent($payment_intent_id) {
        return self::api_request('payment_intents/' . $payment_intent_id, 'GET');
    }

    // ==========================================
    // CHECKOUT SESSIONS (Pack Purchases)
    // ==========================================

    /**
     * Create checkout session for lesson pack purchase
     */
    public static function create_checkout_session($data) {
        $trainer = PTP_Database::get_trainer($data['trainer_id']);

        if (!$trainer) {
            return new WP_Error('trainer_not_found', 'Trainer not found');
        }

        // Get or create customer
        $customer_id = self::get_or_create_customer($data['customer_id']);
        if (is_wp_error($customer_id)) {
            // Fall back to email-only
            $customer_id = null;
        }

        // Calculate platform fee
        $platform_fee = round($data['price'] * (self::$platform_fee_percent / 100) * 100);

        // Build line item description
        $pack_names = array(
            'single' => 'Single Training Session',
            'pack_4' => '4-Session Training Pack',
            'pack_8' => '8-Session Training Pack'
        );

        $description = $pack_names[$data['pack_type']] ?? 'Training Sessions';
        $description .= ' with ' . $trainer->display_name;

        // Build session data
        $session_data = array(
            'mode' => 'payment',
            'success_url' => home_url('/my-training/?purchase=success&session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => home_url('/trainer/' . $trainer->slug . '/?purchase=cancelled'),
            'line_items[0][price_data][currency]' => 'usd',
            'line_items[0][price_data][unit_amount]' => round($data['price'] * 100),
            'line_items[0][price_data][product_data][name]' => $description,
            'line_items[0][price_data][product_data][description]' => $data['sessions'] . ' session(s) for ' . $data['athlete_name'],
            'line_items[0][quantity]' => 1,
            'metadata[session_type]' => 'lesson_pack',
            'metadata[trainer_id]' => $data['trainer_id'],
            'metadata[customer_id]' => $data['customer_id'],
            'metadata[pack_type]' => $data['pack_type'],
            'metadata[sessions]' => $data['sessions'],
            'metadata[athlete_name]' => $data['athlete_name'],
            'metadata[athlete_age]' => $data['athlete_age'],
            'metadata[athlete_skill]' => $data['athlete_skill'],
            'metadata[athlete_goals]' => $data['athlete_goals'],
            'metadata[platform]' => 'ptp_training'
        );

        if ($customer_id) {
            $session_data['customer'] = $customer_id;
        } else {
            $session_data['customer_email'] = wp_get_current_user()->user_email;
        }

        // Add Connect charges if trainer has account
        if ($trainer->stripe_account_id && $trainer->stripe_onboarding_complete) {
            $session_data['payment_intent_data[application_fee_amount]'] = $platform_fee;
            $session_data['payment_intent_data[transfer_data][destination]'] = $trainer->stripe_account_id;
        }

        $session = self::api_request('checkout/sessions', 'POST', $session_data);

        if (is_wp_error($session) || isset($session['error'])) {
            return new WP_Error('stripe_error', $session['error']['message'] ?? 'Failed to create checkout session');
        }

        return array(
            'url' => $session['url'],
            'session_id' => $session['id']
        );
    }

    // ==========================================
    // WEBHOOK HANDLING
    // ==========================================

    /**
     * Handle Stripe webhook
     */
    public static function handle_webhook($request) {
        $payload = $request->get_body();
        $sig_header = $request->get_header('stripe-signature');

        // Verify webhook signature
        if (self::$webhook_secret) {
            $verified = self::verify_webhook_signature($payload, $sig_header);
            if (is_wp_error($verified)) {
                error_log('PTP Stripe Webhook: Signature verification failed');
                return $verified;
            }
        }

        $event = json_decode($payload, true);

        if (!$event || !isset($event['type'])) {
            error_log('PTP Stripe Webhook: Invalid payload');
            return new WP_Error('invalid_payload', 'Invalid webhook payload', array('status' => 400));
        }

        error_log('PTP Stripe Webhook: Received event ' . $event['type']);

        try {
            switch ($event['type']) {
                case 'payment_intent.succeeded':
                    self::handle_payment_intent_succeeded($event['data']['object']);
                    break;

                case 'payment_intent.payment_failed':
                    self::handle_payment_intent_failed($event['data']['object']);
                    break;

                case 'checkout.session.completed':
                    self::handle_checkout_completed($event['data']['object']);
                    break;

                case 'charge.refunded':
                    self::handle_charge_refunded($event['data']['object']);
                    break;

                case 'account.updated':
                    self::handle_account_updated($event['data']['object']);
                    break;

                default:
                    // Log unhandled events for debugging
                    error_log('PTP Stripe Webhook: Unhandled event type ' . $event['type']);
            }
        } catch (Exception $e) {
            error_log('PTP Stripe Webhook Error: ' . $e->getMessage());
            return new WP_Error('webhook_error', $e->getMessage(), array('status' => 500));
        }

        return rest_ensure_response(array('received' => true));
    }

    /**
     * Verify webhook signature
     */
    private static function verify_webhook_signature($payload, $sig_header) {
        if (!$sig_header) {
            return new WP_Error('missing_signature', 'Missing webhook signature', array('status' => 400));
        }

        $timestamp = null;
        $signatures = array();

        foreach (explode(',', $sig_header) as $item) {
            $parts = explode('=', $item, 2);
            if (count($parts) === 2) {
                if ($parts[0] === 't') {
                    $timestamp = $parts[1];
                } elseif ($parts[0] === 'v1') {
                    $signatures[] = $parts[1];
                }
            }
        }

        if (!$timestamp || empty($signatures)) {
            return new WP_Error('invalid_signature', 'Invalid webhook signature format', array('status' => 400));
        }

        // Check timestamp tolerance (5 minutes)
        if (abs(time() - intval($timestamp)) > 300) {
            return new WP_Error('timestamp_expired', 'Webhook timestamp too old', array('status' => 400));
        }

        $signed_payload = $timestamp . '.' . $payload;
        $expected_sig = hash_hmac('sha256', $signed_payload, self::$webhook_secret);

        $valid = false;
        foreach ($signatures as $sig) {
            if (hash_equals($expected_sig, $sig)) {
                $valid = true;
                break;
            }
        }

        if (!$valid) {
            return new WP_Error('invalid_signature', 'Webhook signature verification failed', array('status' => 400));
        }

        return true;
    }

    /**
     * Handle successful PaymentIntent
     */
    private static function handle_payment_intent_succeeded($payment_intent) {
        $metadata = $payment_intent['metadata'] ?? array();

        // Check if this is a session payment (not a checkout session)
        if (isset($metadata['ptp_session_id'])) {
            $session_id = $metadata['ptp_session_id'];
            $session = PTP_Database::get_session($session_id);

            if ($session) {
                // Update session payment status
                PTP_Database::update_session($session_id, array(
                    'payment_status' => PTP_Database::PAYMENT_STATUS_PAID,
                    'stripe_payment_intent_id' => $payment_intent['id']
                ));

                // Optionally auto-confirm session
                if (get_option('ptp_auto_confirm_on_payment', false)) {
                    PTP_Database::update_session($session_id, array(
                        'session_status' => PTP_Database::SESSION_STATUS_CONFIRMED
                    ));
                }

                error_log('PTP Stripe: Session ' . $session_id . ' payment succeeded');
            }
        }

        // Check for session by payment intent ID
        $session = PTP_Database::get_session_by_payment_intent($payment_intent['id']);
        if ($session && $session->payment_status !== PTP_Database::PAYMENT_STATUS_PAID) {
            PTP_Database::update_session($session->id, array(
                'payment_status' => PTP_Database::PAYMENT_STATUS_PAID
            ));
            error_log('PTP Stripe: Session ' . $session->id . ' payment succeeded (matched by PI)');
        }
    }

    /**
     * Handle failed PaymentIntent
     */
    private static function handle_payment_intent_failed($payment_intent) {
        $metadata = $payment_intent['metadata'] ?? array();

        if (isset($metadata['ptp_session_id'])) {
            $session_id = $metadata['ptp_session_id'];

            PTP_Database::update_session($session_id, array(
                'payment_status' => PTP_Database::PAYMENT_STATUS_FAILED
            ));

            error_log('PTP Stripe: Session ' . $session_id . ' payment failed');
        }

        // Check for session by payment intent ID
        $session = PTP_Database::get_session_by_payment_intent($payment_intent['id']);
        if ($session) {
            PTP_Database::update_session($session->id, array(
                'payment_status' => PTP_Database::PAYMENT_STATUS_FAILED
            ));
            error_log('PTP Stripe: Session ' . $session->id . ' payment failed (matched by PI)');
        }
    }

    /**
     * Handle successful checkout (lesson packs)
     */
    private static function handle_checkout_completed($checkout_session) {
        global $wpdb;

        $metadata = $checkout_session['metadata'];

        // Skip if not a lesson pack
        if (!isset($metadata['session_type']) || $metadata['session_type'] !== 'lesson_pack') {
            // Could be single session checkout
            if (isset($metadata['ptp_session_id'])) {
                PTP_Database::update_session($metadata['ptp_session_id'], array(
                    'payment_status' => PTP_Database::PAYMENT_STATUS_PAID
                ));
            }
            return;
        }

        // Create lesson pack
        $pack_data = array(
            'customer_id' => $metadata['customer_id'],
            'trainer_id' => $metadata['trainer_id'],
            'pack_type' => $metadata['pack_type'],
            'total_sessions' => $metadata['sessions'],
            'sessions_used' => 0,
            'sessions_remaining' => $metadata['sessions'],
            'price_paid' => $checkout_session['amount_total'] / 100,
            'price_per_session' => ($checkout_session['amount_total'] / 100) / $metadata['sessions'],
            'athlete_name' => $metadata['athlete_name'],
            'athlete_age' => $metadata['athlete_age'],
            'athlete_skill_level' => $metadata['athlete_skill'],
            'athlete_goals' => $metadata['athlete_goals'],
            'status' => 'active',
            'payment_status' => PTP_Database::PAYMENT_STATUS_PAID,
            'stripe_payment_intent_id' => $checkout_session['payment_intent'] ?? '',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+6 months'))
        );

        $wpdb->insert("{$wpdb->prefix}ptp_lesson_packs", $pack_data);
        $pack_id = $wpdb->insert_id;

        if (!$pack_id) {
            error_log('PTP Stripe: Failed to create lesson pack');
            return;
        }

        // Create placeholder sessions
        $platform_fee_percent = self::$platform_fee_percent;
        $price_per_session = $pack_data['price_per_session'];

        for ($i = 0; $i < $metadata['sessions']; $i++) {
            $session_data = array(
                'pack_id' => $pack_id,
                'trainer_id' => $metadata['trainer_id'],
                'customer_id' => $metadata['customer_id'],
                'player_name' => $metadata['athlete_name'],
                'player_age' => $metadata['athlete_age'],
                'session_date' => '0000-00-00',
                'start_time' => '00:00:00',
                'end_time' => '00:00:00',
                'price' => $price_per_session,
                'platform_fee' => $price_per_session * ($platform_fee_percent / 100),
                'trainer_payout' => $price_per_session * (1 - $platform_fee_percent / 100),
                'session_status' => PTP_Database::SESSION_STATUS_UNSCHEDULED,
                'payment_status' => PTP_Database::PAYMENT_STATUS_PAID
            );

            PTP_Database::create_session($session_data);
        }

        // Send confirmation emails
        self::send_purchase_confirmation($pack_id);
        self::send_trainer_notification($pack_id);

        error_log('PTP Stripe: Lesson pack ' . $pack_id . ' created with ' . $metadata['sessions'] . ' sessions');
    }

    /**
     * Handle charge refund
     */
    private static function handle_charge_refunded($charge) {
        global $wpdb;

        $payment_intent_id = $charge['payment_intent'] ?? null;

        if (!$payment_intent_id) {
            return;
        }

        // Find session by payment intent
        $session = PTP_Database::get_session_by_payment_intent($payment_intent_id);

        if ($session) {
            $refund_amount = $charge['amount_refunded'] / 100;

            PTP_Database::update_session($session->id, array(
                'payment_status' => PTP_Database::PAYMENT_STATUS_REFUNDED,
                'refund_amount' => $refund_amount
            ));

            error_log('PTP Stripe: Session ' . $session->id . ' refunded $' . $refund_amount);
        }

        // Also check lesson packs
        $pack = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ptp_lesson_packs WHERE stripe_payment_intent_id = %s",
            $payment_intent_id
        ));

        if ($pack) {
            $wpdb->update(
                "{$wpdb->prefix}ptp_lesson_packs",
                array('payment_status' => PTP_Database::PAYMENT_STATUS_REFUNDED),
                array('id' => $pack->id)
            );

            error_log('PTP Stripe: Lesson pack ' . $pack->id . ' refunded');
        }
    }

    /**
     * Handle Connect account update
     */
    private static function handle_account_updated($account) {
        global $wpdb;

        if ($account['charges_enabled'] && $account['payouts_enabled']) {
            $wpdb->update(
                "{$wpdb->prefix}ptp_trainers",
                array('stripe_onboarding_complete' => 1),
                array('stripe_account_id' => $account['id'])
            );

            error_log('PTP Stripe: Connect account ' . $account['id'] . ' onboarding complete');
        }
    }

    // ==========================================
    // PAYOUTS
    // ==========================================

    /**
     * Process payout to trainer
     */
    public static function process_payout($payout_id) {
        global $wpdb;

        $payout = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, t.stripe_account_id FROM {$wpdb->prefix}ptp_payouts p
             LEFT JOIN {$wpdb->prefix}ptp_trainers t ON p.trainer_id = t.id
             WHERE p.id = %d",
            $payout_id
        ));

        if (!$payout) {
            return new WP_Error('invalid_payout', 'Payout not found');
        }

        if (!$payout->stripe_account_id) {
            return new WP_Error('no_stripe_account', 'Trainer does not have a Stripe account connected');
        }

        if ($payout->status === 'paid') {
            return new WP_Error('already_paid', 'This payout has already been processed');
        }

        // Create transfer
        $transfer = self::api_request('transfers', 'POST', array(
            'amount' => round($payout->trainer_payout * 100),
            'currency' => 'usd',
            'destination' => $payout->stripe_account_id,
            'metadata[payout_id]' => $payout_id,
            'metadata[session_id]' => $payout->session_id,
            'metadata[platform]' => 'ptp_training'
        ));

        if (is_wp_error($transfer) || isset($transfer['error'])) {
            return new WP_Error('transfer_failed', $transfer['error']['message'] ?? 'Transfer failed');
        }

        // Update payout record
        $wpdb->update(
            "{$wpdb->prefix}ptp_payouts",
            array(
                'stripe_transfer_id' => $transfer['id'],
                'status' => 'paid',
                'paid_at' => current_time('mysql')
            ),
            array('id' => $payout_id)
        );

        return $transfer['id'];
    }

    /**
     * Create refund for a payment
     */
    public static function create_refund($payment_intent_id, $amount = null, $reason = 'requested_by_customer') {
        $refund_data = array(
            'payment_intent' => $payment_intent_id,
            'reason' => $reason
        );

        if ($amount !== null) {
            $refund_data['amount'] = round($amount * 100);
        }

        return self::api_request('refunds', 'POST', $refund_data);
    }

    /**
     * Get trainer's Stripe dashboard link
     */
    public static function get_dashboard_link($trainer_id) {
        $trainer = PTP_Database::get_trainer($trainer_id);

        if (!$trainer || !$trainer->stripe_account_id) {
            return null;
        }

        $link = self::api_request('accounts/' . $trainer->stripe_account_id . '/login_links', 'POST');

        if (is_wp_error($link) || isset($link['error'])) {
            return null;
        }

        return $link['url'];
    }

    /**
     * Get trainer balance
     */
    public static function get_trainer_balance($trainer_id) {
        $trainer = PTP_Database::get_trainer($trainer_id);

        if (!$trainer || !$trainer->stripe_account_id) {
            return null;
        }

        $response = wp_remote_get('https://api.stripe.com/v1/balance', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . self::$secret_key,
                'Stripe-Account' => $trainer->stripe_account_id
            )
        ));

        if (is_wp_error($response)) {
            return null;
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    // ==========================================
    // EMAIL HELPERS
    // ==========================================

    private static function send_purchase_confirmation($pack_id) {
        $pack = PTP_Database::get_pack($pack_id);
        if (!$pack) return;

        $customer = get_user_by('ID', $pack->customer_id);
        if (!$customer) return;

        $subject = 'Training Package Confirmed - ' . $pack->trainer_name;
        $message = "Hi {$customer->display_name},\n\n";
        $message .= "Your training package with {$pack->trainer_name} has been confirmed!\n\n";
        $message .= "Package Details:\n";
        $message .= "- Sessions: {$pack->total_sessions}\n";
        $message .= "- Athlete: {$pack->athlete_name}\n";
        $message .= "- Amount Paid: $" . number_format($pack->price_paid, 2) . "\n\n";
        $message .= "Next Steps:\n";
        $message .= "1. Visit your dashboard to schedule your first session\n";
        $message .= "2. Your trainer will confirm the time and location\n\n";
        $message .= "Schedule your sessions: " . home_url('/my-training/') . "\n\n";
        $message .= "Questions? Reply to this email.\n\n";
        $message .= "See you on the field!\nThe PTP Team";

        wp_mail($customer->user_email, $subject, $message);
    }

    private static function send_trainer_notification($pack_id) {
        $pack = PTP_Database::get_pack($pack_id);
        if (!$pack) return;

        global $wpdb;
        $trainer_email = $wpdb->get_var($wpdb->prepare(
            "SELECT u.user_email FROM {$wpdb->prefix}ptp_trainers t
             LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID
             WHERE t.id = %d",
            $pack->trainer_id
        ));

        if (!$trainer_email) return;

        $trainer_payout = $pack->price_paid * (1 - self::$platform_fee_percent / 100);

        $subject = 'New Booking! ' . $pack->athlete_name . ' - ' . $pack->total_sessions . ' Sessions';
        $message = "Great news! You have a new training package booking.\n\n";
        $message .= "Athlete: {$pack->athlete_name} (Age {$pack->athlete_age})\n";
        $message .= "Skill Level: {$pack->athlete_skill_level}\n";
        $message .= "Sessions: {$pack->total_sessions}\n";
        $message .= "Goals: {$pack->athlete_goals}\n\n";
        $message .= "Your Earnings: $" . number_format($trainer_payout, 2) . "\n\n";
        $message .= "The customer will schedule their first session soon. You'll be notified when they do.\n\n";
        $message .= "View in dashboard: " . home_url('/trainer-dashboard/') . "\n\n";
        $message .= "Keep up the great work!\nThe PTP Team";

        wp_mail($trainer_email, $subject, $message);
    }

    /**
     * Get publishable key for frontend
     */
    public static function get_publishable_key() {
        return self::$publishable_key;
    }

    /**
     * Get platform fee percentage
     */
    public static function get_platform_fee_percent() {
        return self::$platform_fee_percent;
    }
}

PTP_Stripe::init();
