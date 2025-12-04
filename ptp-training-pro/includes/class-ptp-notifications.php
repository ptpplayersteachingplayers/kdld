<?php
/**
 * PTP Notifications - Email & Communication System
 * Handles all email notifications and provides hooks for future integrations
 */

if (!defined('ABSPATH')) exit;

class PTP_Notifications {

    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // Hook into session events
        add_action('ptp_session_created', array($this, 'on_session_created'), 10, 2);
        add_action('ptp_session_status_changed', array($this, 'on_session_status_changed'), 10, 3);
        add_action('ptp_session_payment_succeeded', array($this, 'on_payment_succeeded'), 10, 1);
        add_action('ptp_session_payment_status_changed', array($this, 'on_payment_status_changed'), 10, 3);

        // Add settings
        add_action('admin_init', array($this, 'register_notification_settings'));
    }

    /**
     * Register notification settings
     */
    public function register_notification_settings() {
        register_setting('ptp_training_settings', 'ptp_notification_admin_email');
        register_setting('ptp_training_settings', 'ptp_notification_from_name', array('default' => 'Players Teaching Players'));
        register_setting('ptp_training_settings', 'ptp_notification_from_email');

        // Toggle settings for each notification type
        register_setting('ptp_training_settings', 'ptp_notify_parent_booking_request', array('type' => 'boolean', 'default' => true));
        register_setting('ptp_training_settings', 'ptp_notify_trainer_booking_request', array('type' => 'boolean', 'default' => true));
        register_setting('ptp_training_settings', 'ptp_notify_admin_booking_request', array('type' => 'boolean', 'default' => true));
        register_setting('ptp_training_settings', 'ptp_notify_parent_session_confirmed', array('type' => 'boolean', 'default' => true));
        register_setting('ptp_training_settings', 'ptp_notify_trainer_session_confirmed', array('type' => 'boolean', 'default' => true));
        register_setting('ptp_training_settings', 'ptp_notify_parent_session_cancelled', array('type' => 'boolean', 'default' => true));
        register_setting('ptp_training_settings', 'ptp_notify_trainer_session_cancelled', array('type' => 'boolean', 'default' => true));
        register_setting('ptp_training_settings', 'ptp_notify_parent_session_completed', array('type' => 'boolean', 'default' => true));
        register_setting('ptp_training_settings', 'ptp_notify_parent_payment_success', array('type' => 'boolean', 'default' => true));
        register_setting('ptp_training_settings', 'ptp_notify_admin_payment_success', array('type' => 'boolean', 'default' => true));

        // Email template customization
        register_setting('ptp_training_settings', 'ptp_email_booking_request_subject');
        register_setting('ptp_training_settings', 'ptp_email_booking_request_intro');
        register_setting('ptp_training_settings', 'ptp_email_session_confirmed_subject');
        register_setting('ptp_training_settings', 'ptp_email_session_confirmed_intro');
        register_setting('ptp_training_settings', 'ptp_email_session_cancelled_subject');
        register_setting('ptp_training_settings', 'ptp_email_session_cancelled_intro');
        register_setting('ptp_training_settings', 'ptp_email_session_completed_subject');
        register_setting('ptp_training_settings', 'ptp_email_session_completed_intro');
        register_setting('ptp_training_settings', 'ptp_email_payment_success_subject');
        register_setting('ptp_training_settings', 'ptp_email_payment_success_intro');
    }

    /**
     * Get admin email address
     */
    private function get_admin_email() {
        return get_option('ptp_notification_admin_email', get_option('ptp_admin_email', get_option('admin_email')));
    }

    /**
     * Get from name for emails
     */
    private function get_from_name() {
        return get_option('ptp_notification_from_name', 'Players Teaching Players');
    }

    /**
     * Get from email address
     */
    private function get_from_email() {
        return get_option('ptp_notification_from_email', get_option('admin_email'));
    }

    /**
     * Send email with proper headers
     */
    private function send_email($to, $subject, $message, $type = '', $session_id = null) {
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->get_from_name() . ' <' . $this->get_from_email() . '>'
        );

        // Wrap message in HTML template
        $html_message = $this->get_email_template($subject, $message);

        $sent = wp_mail($to, $subject, $html_message, $headers);

        // Log the email
        $this->log_email($to, $subject, $type, $session_id, $sent ? 'sent' : 'failed');

        return $sent;
    }

    /**
     * Get HTML email template
     */
    private function get_email_template($title, $content) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html($title); ?></title>
        </head>
        <body style="margin: 0; padding: 0; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #F4F3F0;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #F4F3F0; padding: 40px 20px;">
                <tr>
                    <td align="center">
                        <table width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; background-color: #FFFFFF; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                            <!-- Header -->
                            <tr>
                                <td style="background-color: #0E0F11; padding: 32px 40px; text-align: center;">
                                    <h1 style="margin: 0; color: #FCB900; font-size: 24px; font-weight: 700;">Players Teaching Players</h1>
                                    <p style="margin: 8px 0 0; color: #9CA3AF; font-size: 14px;">Private Training</p>
                                </td>
                            </tr>
                            <!-- Content -->
                            <tr>
                                <td style="padding: 40px;">
                                    <?php echo $content; ?>
                                </td>
                            </tr>
                            <!-- Footer -->
                            <tr>
                                <td style="background-color: #F9FAFB; padding: 24px 40px; border-top: 1px solid #E5E7EB;">
                                    <p style="margin: 0 0 8px; color: #6B7280; font-size: 14px; text-align: center;">
                                        Questions? Reply to this email or contact us at support@ptpsummercamps.com
                                    </p>
                                    <p style="margin: 0; color: #9CA3AF; font-size: 12px; text-align: center;">
                                        &copy; <?php echo date('Y'); ?> Players Teaching Players. All rights reserved.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Log email to database
     */
    private function log_email($recipient, $subject, $type, $session_id, $status) {
        global $wpdb;
        $wpdb->insert(
            "{$wpdb->prefix}ptp_email_log",
            array(
                'recipient_email' => $recipient,
                'recipient_type' => $type,
                'subject' => $subject,
                'message_type' => $type,
                'session_id' => $session_id,
                'status' => $status
            ),
            array('%s', '%s', '%s', '%s', '%d', '%s')
        );
    }

    /**
     * Format session details for email
     */
    private function format_session_details($session) {
        $date = $session->session_date !== '0000-00-00'
            ? date('l, F j, Y', strtotime($session->session_date))
            : 'To be scheduled';

        $time = $session->start_time !== '00:00:00'
            ? date('g:i A', strtotime($session->start_time))
            : 'To be scheduled';

        $location = $session->location_text ?: 'To be confirmed';

        $details = '<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">';
        $details .= '<tr><td style="padding: 12px 0; border-bottom: 1px solid #E5E7EB; color: #6B7280; width: 140px;">Trainer</td><td style="padding: 12px 0; border-bottom: 1px solid #E5E7EB; color: #0E0F11; font-weight: 500;">' . esc_html($session->trainer_name) . '</td></tr>';
        $details .= '<tr><td style="padding: 12px 0; border-bottom: 1px solid #E5E7EB; color: #6B7280;">Player</td><td style="padding: 12px 0; border-bottom: 1px solid #E5E7EB; color: #0E0F11; font-weight: 500;">' . esc_html($session->player_name ?: 'N/A') . '</td></tr>';
        $details .= '<tr><td style="padding: 12px 0; border-bottom: 1px solid #E5E7EB; color: #6B7280;">Date</td><td style="padding: 12px 0; border-bottom: 1px solid #E5E7EB; color: #0E0F11; font-weight: 500;">' . esc_html($date) . '</td></tr>';
        $details .= '<tr><td style="padding: 12px 0; border-bottom: 1px solid #E5E7EB; color: #6B7280;">Time</td><td style="padding: 12px 0; border-bottom: 1px solid #E5E7EB; color: #0E0F11; font-weight: 500;">' . esc_html($time) . '</td></tr>';
        $details .= '<tr><td style="padding: 12px 0; border-bottom: 1px solid #E5E7EB; color: #6B7280;">Location</td><td style="padding: 12px 0; border-bottom: 1px solid #E5E7EB; color: #0E0F11; font-weight: 500;">' . esc_html($location) . '</td></tr>';

        if ($session->price > 0) {
            $details .= '<tr><td style="padding: 12px 0; color: #6B7280;">Price</td><td style="padding: 12px 0; color: #0E0F11; font-weight: 600;">$' . number_format($session->price, 2) . '</td></tr>';
        }

        $details .= '</table>';

        return $details;
    }

    /**
     * Get CTA button HTML
     */
    private function get_cta_button($url, $text, $color = '#FCB900') {
        $text_color = ($color === '#FCB900') ? '#0E0F11' : '#FFFFFF';
        return '<table width="100%" cellpadding="0" cellspacing="0" style="margin: 24px 0;">
            <tr>
                <td align="center">
                    <a href="' . esc_url($url) . '" style="display: inline-block; background-color: ' . $color . '; color: ' . $text_color . '; text-decoration: none; padding: 16px 32px; border-radius: 8px; font-weight: 600; font-size: 16px;">' . esc_html($text) . '</a>
                </td>
            </tr>
        </table>';
    }

    // ==========================================
    // EVENT HANDLERS
    // ==========================================

    /**
     * Handle new session creation
     */
    public function on_session_created($session_id, $data) {
        $session = PTP_Database::get_session($session_id);
        if (!$session) return;

        $parent = get_user_by('ID', $session->customer_id);
        if (!$parent) return;

        // Get trainer user for their email
        global $wpdb;
        $trainer_email = $wpdb->get_var($wpdb->prepare(
            "SELECT u.user_email FROM {$wpdb->prefix}ptp_trainers t
             LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID
             WHERE t.id = %d",
            $session->trainer_id
        ));

        // Email to Parent
        if (get_option('ptp_notify_parent_booking_request', true)) {
            $subject = get_option('ptp_email_booking_request_subject', 'We received your PTP private training request');
            $intro = get_option('ptp_email_booking_request_intro', 'Thank you for submitting your training request!');

            $message = '<h2 style="margin: 0 0 16px; color: #0E0F11; font-size: 24px;">' . esc_html($subject) . '</h2>';
            $message .= '<p style="margin: 0 0 24px; color: #4B5563; font-size: 16px; line-height: 1.6;">' . esc_html($intro) . '</p>';
            $message .= '<p style="margin: 0 0 16px; color: #4B5563; font-size: 16px; line-height: 1.6;">Here are the details of your request:</p>';
            $message .= $this->format_session_details($session);
            $message .= '<div style="background-color: #FEF3C7; border-radius: 8px; padding: 16px; margin: 24px 0;">';
            $message .= '<p style="margin: 0; color: #92400E; font-size: 14px;"><strong>What happens next?</strong></p>';
            $message .= '<p style="margin: 8px 0 0; color: #92400E; font-size: 14px;">Your trainer will review your request and confirm the session details. You\'ll receive another email once confirmed.</p>';
            $message .= '</div>';
            $message .= $this->get_cta_button(home_url('/my-training/'), 'View My Training');

            $this->send_email($parent->user_email, $subject, $message, 'parent_booking_request', $session_id);
        }

        // Email to Trainer
        if (get_option('ptp_notify_trainer_booking_request', true) && $trainer_email) {
            $subject = 'New private training request from ' . $parent->display_name;

            $message = '<h2 style="margin: 0 0 16px; color: #0E0F11; font-size: 24px;">New Training Request!</h2>';
            $message .= '<p style="margin: 0 0 24px; color: #4B5563; font-size: 16px; line-height: 1.6;">You have a new training request waiting for your confirmation.</p>';
            $message .= $this->format_session_details($session);

            if ($session->customer_notes) {
                $message .= '<div style="background-color: #F3F4F6; border-radius: 8px; padding: 16px; margin: 24px 0;">';
                $message .= '<p style="margin: 0 0 8px; color: #374151; font-weight: 600; font-size: 14px;">Customer Notes:</p>';
                $message .= '<p style="margin: 0; color: #4B5563; font-size: 14px;">' . esc_html($session->customer_notes) . '</p>';
                $message .= '</div>';
            }

            $message .= $this->get_cta_button(home_url('/trainer-dashboard/'), 'View in Dashboard');

            $this->send_email($trainer_email, $subject, $message, 'trainer_booking_request', $session_id);
        }

        // Email to Admin
        if (get_option('ptp_notify_admin_booking_request', true)) {
            $admin_email = $this->get_admin_email();
            $subject = 'New PTP private training session request';

            $message = '<h2 style="margin: 0 0 16px; color: #0E0F11; font-size: 24px;">New Session Request</h2>';
            $message .= '<p style="margin: 0 0 24px; color: #4B5563; font-size: 16px; line-height: 1.6;">A new private training session has been requested.</p>';
            $message .= '<p style="color: #4B5563;"><strong>Customer:</strong> ' . esc_html($parent->display_name) . ' (' . esc_html($parent->user_email) . ')</p>';
            $message .= $this->format_session_details($session);
            $message .= $this->get_cta_button(admin_url('admin.php?page=ptp-training-sessions'), 'View in Admin', '#0E0F11');

            $this->send_email($admin_email, $subject, $message, 'admin_booking_request', $session_id);
        }
    }

    /**
     * Handle session status change
     */
    public function on_session_status_changed($session_id, $new_status, $old_status) {
        $session = PTP_Database::get_session($session_id);
        if (!$session) return;

        $parent = get_user_by('ID', $session->customer_id);
        if (!$parent) return;

        global $wpdb;
        $trainer_email = $wpdb->get_var($wpdb->prepare(
            "SELECT u.user_email FROM {$wpdb->prefix}ptp_trainers t
             LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID
             WHERE t.id = %d",
            $session->trainer_id
        ));

        // Handle CONFIRMED status
        if ($new_status === PTP_Database::SESSION_STATUS_CONFIRMED) {
            // Email to Parent
            if (get_option('ptp_notify_parent_session_confirmed', true)) {
                $subject = get_option('ptp_email_session_confirmed_subject', 'Your training session is confirmed!');
                $intro = get_option('ptp_email_session_confirmed_intro', 'Great news! Your training session has been confirmed.');

                $message = '<h2 style="margin: 0 0 16px; color: #0E0F11; font-size: 24px;">' . esc_html($subject) . '</h2>';
                $message .= '<p style="margin: 0 0 24px; color: #4B5563; font-size: 16px; line-height: 1.6;">' . esc_html($intro) . '</p>';
                $message .= $this->format_session_details($session);
                $message .= '<div style="background-color: #D1FAE5; border-radius: 8px; padding: 16px; margin: 24px 0;">';
                $message .= '<p style="margin: 0; color: #065F46; font-size: 14px;"><strong>Your session is confirmed!</strong> Be sure to arrive 5-10 minutes early.</p>';
                $message .= '</div>';
                $message .= $this->get_cta_button(home_url('/my-training/'), 'View Session Details');

                $this->send_email($parent->user_email, $subject, $message, 'parent_session_confirmed', $session_id);
            }

            // Email to Trainer
            if (get_option('ptp_notify_trainer_session_confirmed', true) && $trainer_email) {
                $subject = 'Session confirmed: ' . $session->player_name . ' on ' . date('M j', strtotime($session->session_date));

                $message = '<h2 style="margin: 0 0 16px; color: #0E0F11; font-size: 24px;">Session Confirmed</h2>';
                $message .= '<p style="margin: 0 0 24px; color: #4B5563; font-size: 16px; line-height: 1.6;">This session is now confirmed and on your schedule.</p>';
                $message .= $this->format_session_details($session);
                $message .= $this->get_cta_button(home_url('/trainer-dashboard/'), 'View Dashboard');

                $this->send_email($trainer_email, $subject, $message, 'trainer_session_confirmed', $session_id);
            }
        }

        // Handle CANCELLED status
        if ($new_status === PTP_Database::SESSION_STATUS_CANCELLED) {
            // Email to Parent
            if (get_option('ptp_notify_parent_session_cancelled', true)) {
                $subject = get_option('ptp_email_session_cancelled_subject', 'Your training session has been cancelled');
                $intro = get_option('ptp_email_session_cancelled_intro', 'We\'re sorry to inform you that your training session has been cancelled.');

                $message = '<h2 style="margin: 0 0 16px; color: #0E0F11; font-size: 24px;">' . esc_html($subject) . '</h2>';
                $message .= '<p style="margin: 0 0 24px; color: #4B5563; font-size: 16px; line-height: 1.6;">' . esc_html($intro) . '</p>';
                $message .= $this->format_session_details($session);

                if ($session->cancellation_reason) {
                    $message .= '<div style="background-color: #FEE2E2; border-radius: 8px; padding: 16px; margin: 24px 0;">';
                    $message .= '<p style="margin: 0 0 8px; color: #991B1B; font-weight: 600; font-size: 14px;">Reason:</p>';
                    $message .= '<p style="margin: 0; color: #991B1B; font-size: 14px;">' . esc_html($session->cancellation_reason) . '</p>';
                    $message .= '</div>';
                }

                if ($session->refund_amount > 0) {
                    $message .= '<p style="color: #4B5563; font-size: 16px;">A refund of <strong>$' . number_format($session->refund_amount, 2) . '</strong> has been initiated to your original payment method.</p>';
                }

                $message .= '<p style="color: #4B5563; font-size: 16px; margin-top: 24px;">Would you like to book another session?</p>';
                $message .= $this->get_cta_button(home_url('/private-training/'), 'Find a Trainer');

                $this->send_email($parent->user_email, $subject, $message, 'parent_session_cancelled', $session_id);
            }

            // Email to Trainer
            if (get_option('ptp_notify_trainer_session_cancelled', true) && $trainer_email) {
                $subject = 'Session cancelled: ' . $session->player_name . ' on ' . date('M j', strtotime($session->session_date));

                $message = '<h2 style="margin: 0 0 16px; color: #0E0F11; font-size: 24px;">Session Cancelled</h2>';
                $message .= '<p style="margin: 0 0 24px; color: #4B5563; font-size: 16px; line-height: 1.6;">A training session has been cancelled.</p>';
                $message .= $this->format_session_details($session);

                if ($session->cancellation_reason) {
                    $message .= '<div style="background-color: #F3F4F6; border-radius: 8px; padding: 16px; margin: 24px 0;">';
                    $message .= '<p style="margin: 0 0 8px; color: #374151; font-weight: 600; font-size: 14px;">Reason:</p>';
                    $message .= '<p style="margin: 0; color: #4B5563; font-size: 14px;">' . esc_html($session->cancellation_reason) . '</p>';
                    $message .= '</div>';
                }

                $message .= $this->get_cta_button(home_url('/trainer-dashboard/'), 'View Dashboard');

                $this->send_email($trainer_email, $subject, $message, 'trainer_session_cancelled', $session_id);
            }
        }

        // Handle COMPLETED status
        if ($new_status === PTP_Database::SESSION_STATUS_COMPLETED) {
            // Email to Parent
            if (get_option('ptp_notify_parent_session_completed', true)) {
                $subject = get_option('ptp_email_session_completed_subject', 'Thanks for training with PTP!');
                $intro = get_option('ptp_email_session_completed_intro', 'We hope your training session was great!');

                $message = '<h2 style="margin: 0 0 16px; color: #0E0F11; font-size: 24px;">' . esc_html($subject) . '</h2>';
                $message .= '<p style="margin: 0 0 24px; color: #4B5563; font-size: 16px; line-height: 1.6;">' . esc_html($intro) . '</p>';

                if ($session->homework) {
                    $message .= '<div style="background-color: #EFF6FF; border-radius: 8px; padding: 16px; margin: 24px 0;">';
                    $message .= '<p style="margin: 0 0 8px; color: #1E40AF; font-weight: 600; font-size: 14px;">Homework from your trainer:</p>';
                    $message .= '<p style="margin: 0; color: #1E40AF; font-size: 14px;">' . esc_html($session->homework) . '</p>';
                    $message .= '</div>';
                }

                if ($session->skills_worked) {
                    $message .= '<p style="color: #4B5563; font-size: 16px;"><strong>Skills covered:</strong> ' . esc_html($session->skills_worked) . '</p>';
                }

                $message .= '<div style="background-color: #FEF3C7; border-radius: 8px; padding: 16px; margin: 24px 0;">';
                $message .= '<p style="margin: 0; color: #92400E; font-size: 14px;"><strong>How was your session?</strong> We\'d love to hear your feedback!</p>';
                $message .= '</div>';

                $message .= $this->get_cta_button(home_url('/my-training/'), 'Leave a Review');

                $message .= '<p style="color: #4B5563; font-size: 16px; margin-top: 24px;">Ready for another session? Check out our upcoming camps and clinics too!</p>';

                $this->send_email($parent->user_email, $subject, $message, 'parent_session_completed', $session_id);
            }
        }
    }

    /**
     * Handle payment success
     */
    public function on_payment_succeeded($session_id) {
        $session = PTP_Database::get_session($session_id);
        if (!$session) return;

        $parent = get_user_by('ID', $session->customer_id);
        if (!$parent) return;

        // Email receipt to Parent
        if (get_option('ptp_notify_parent_payment_success', true)) {
            $subject = get_option('ptp_email_payment_success_subject', 'Payment confirmed - PTP Private Training');
            $intro = get_option('ptp_email_payment_success_intro', 'Your payment has been successfully processed.');

            $message = '<h2 style="margin: 0 0 16px; color: #0E0F11; font-size: 24px;">Payment Received</h2>';
            $message .= '<p style="margin: 0 0 24px; color: #4B5563; font-size: 16px; line-height: 1.6;">' . esc_html($intro) . '</p>';

            $message .= '<div style="background-color: #D1FAE5; border-radius: 8px; padding: 16px; margin: 24px 0; text-align: center;">';
            $message .= '<p style="margin: 0 0 8px; color: #065F46; font-size: 14px;">Amount Paid</p>';
            $message .= '<p style="margin: 0; color: #065F46; font-size: 32px; font-weight: 700;">$' . number_format($session->price, 2) . '</p>';
            $message .= '</div>';

            $message .= $this->format_session_details($session);

            $message .= '<p style="color: #9CA3AF; font-size: 12px; margin-top: 24px;">Transaction ID: ' . esc_html($session->stripe_payment_intent_id) . '</p>';

            $message .= $this->get_cta_button(home_url('/my-training/'), 'View My Training');

            $this->send_email($parent->user_email, $subject, $message, 'parent_payment_success', $session_id);
        }

        // Notify Admin
        if (get_option('ptp_notify_admin_payment_success', true)) {
            $admin_email = $this->get_admin_email();
            $subject = 'Payment received: $' . number_format($session->price, 2) . ' - ' . $parent->display_name;

            $message = '<h2 style="margin: 0 0 16px; color: #0E0F11; font-size: 24px;">Payment Received</h2>';
            $message .= '<p style="margin: 0 0 24px; color: #4B5563; font-size: 16px; line-height: 1.6;">A payment has been successfully processed.</p>';

            $message .= '<table style="width: 100%; margin: 24px 0;">';
            $message .= '<tr><td style="padding: 12px 0; color: #6B7280;">Customer</td><td style="padding: 12px 0; color: #0E0F11; font-weight: 500;">' . esc_html($parent->display_name) . '</td></tr>';
            $message .= '<tr><td style="padding: 12px 0; color: #6B7280;">Amount</td><td style="padding: 12px 0; color: #0E0F11; font-weight: 600;">$' . number_format($session->price, 2) . '</td></tr>';
            $message .= '<tr><td style="padding: 12px 0; color: #6B7280;">Platform Fee</td><td style="padding: 12px 0; color: #10B981; font-weight: 500;">$' . number_format($session->platform_fee, 2) . '</td></tr>';
            $message .= '<tr><td style="padding: 12px 0; color: #6B7280;">Trainer</td><td style="padding: 12px 0; color: #0E0F11;">' . esc_html($session->trainer_name) . '</td></tr>';
            $message .= '</table>';

            $this->send_email($admin_email, $subject, $message, 'admin_payment_success', $session_id);
        }
    }

    /**
     * Handle payment status change (for refunds, failures, etc)
     */
    public function on_payment_status_changed($session_id, $new_status, $old_status) {
        // Handle refund notifications
        if ($new_status === PTP_Database::PAYMENT_STATUS_REFUNDED) {
            $session = PTP_Database::get_session($session_id);
            if (!$session) return;

            $parent = get_user_by('ID', $session->customer_id);
            if (!$parent) return;

            $subject = 'Refund processed - PTP Private Training';

            $message = '<h2 style="margin: 0 0 16px; color: #0E0F11; font-size: 24px;">Refund Processed</h2>';
            $message .= '<p style="margin: 0 0 24px; color: #4B5563; font-size: 16px; line-height: 1.6;">Your refund has been processed successfully.</p>';

            $refund_amount = $session->refund_amount ?: $session->price;

            $message .= '<div style="background-color: #EFF6FF; border-radius: 8px; padding: 16px; margin: 24px 0; text-align: center;">';
            $message .= '<p style="margin: 0 0 8px; color: #1E40AF; font-size: 14px;">Refund Amount</p>';
            $message .= '<p style="margin: 0; color: #1E40AF; font-size: 32px; font-weight: 700;">$' . number_format($refund_amount, 2) . '</p>';
            $message .= '</div>';

            $message .= '<p style="color: #4B5563; font-size: 14px;">The refund will appear on your statement within 5-10 business days, depending on your bank.</p>';

            $this->send_email($parent->user_email, $subject, $message, 'parent_refund_processed', $session_id);
        }
    }

    // ==========================================
    // MANUAL NOTIFICATION METHODS
    // ==========================================

    /**
     * Send custom notification
     */
    public function send_custom_notification($to, $subject, $message, $session_id = null) {
        return $this->send_email($to, $subject, $message, 'custom', $session_id);
    }

    /**
     * Send session reminder
     */
    public function send_session_reminder($session_id, $type = '24hr') {
        $session = PTP_Database::get_session($session_id);
        if (!$session) return false;

        $parent = get_user_by('ID', $session->customer_id);
        if (!$parent) return false;

        $hours = ($type === '2hr') ? '2' : '24';
        $subject = 'Reminder: Training session in ' . $hours . ' hours';

        $message = '<h2 style="margin: 0 0 16px; color: #0E0F11; font-size: 24px;">Session Reminder</h2>';
        $message .= '<p style="margin: 0 0 24px; color: #4B5563; font-size: 16px; line-height: 1.6;">Your training session with ' . esc_html($session->trainer_name) . ' is coming up in ' . $hours . ' hours!</p>';
        $message .= $this->format_session_details($session);
        $message .= '<div style="background-color: #FEF3C7; border-radius: 8px; padding: 16px; margin: 24px 0;">';
        $message .= '<p style="margin: 0; color: #92400E; font-size: 14px;"><strong>Remember:</strong> Arrive 5-10 minutes early. Bring water and appropriate gear!</p>';
        $message .= '</div>';

        return $this->send_email($parent->user_email, $subject, $message, 'session_reminder_' . $type, $session_id);
    }
}

// Initialize
PTP_Notifications::instance();
