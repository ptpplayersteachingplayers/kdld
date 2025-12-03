<?php
/**
 * WooCommerce Integration
 * Links trainers to camps/clinics with referral tracking
 */

if (!defined('ABSPATH')) exit;

class PTP_WooCommerce {
    
    private static $instance = null;
    
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        // Only init if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Referral tracking
        add_action('init', array($this, 'track_referral_click'));
        add_action('woocommerce_checkout_order_processed', array($this, 'track_referral_conversion'), 10, 3);
        add_action('woocommerce_order_status_completed', array($this, 'credit_referral_commission'));
        
        // Product display enhancements
        add_action('woocommerce_before_add_to_cart_form', array($this, 'show_trainer_referral_badge'));
        
        // Shortcodes for camp/clinic display
        add_shortcode('ptp_camp_finder', array($this, 'render_camp_finder'));
        add_shortcode('ptp_upcoming_clinics', array($this, 'render_upcoming_clinics'));
        
        // REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Admin hooks
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_product_fields'));
        add_action('woocommerce_process_product_meta', array($this, 'save_product_fields'));
        
        // Trainer dashboard integration
        add_filter('ptp_trainer_dashboard_tabs', array($this, 'add_referral_tab'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        $namespace = 'ptp-training/v1';
        
        register_rest_route($namespace, '/camps', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_camps'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route($namespace, '/clinics', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_clinics'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route($namespace, '/trainer/referral-link', array(
            'methods' => 'POST',
            'callback' => array($this, 'generate_referral_link'),
            'permission_callback' => array($this, 'check_is_trainer')
        ));
        
        register_rest_route($namespace, '/trainer/referral-stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_referral_stats'),
            'permission_callback' => array($this, 'check_is_trainer')
        ));
    }
    
    public function check_is_trainer() {
        if (!is_user_logged_in()) return false;
        $trainer = PTP_Database::get_trainer_by_user(get_current_user_id());
        return $trainer && $trainer->status === 'approved';
    }
    
    /**
     * Get camps from WooCommerce
     */
    public function get_camps($request) {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => array('summer-camps', 'camps', 'summer-camp', 'camp'),
                    'operator' => 'IN'
                )
            ),
            'meta_query' => array(
                array(
                    'key' => '_stock_status',
                    'value' => 'instock'
                )
            )
        );
        
        // Filter by state if provided
        $state = $request->get_param('state');
        if ($state) {
            $args['meta_query'][] = array(
                'key' => '_ptp_camp_state',
                'value' => $state
            );
        }
        
        // Filter by date range
        $start_date = $request->get_param('start_date');
        if ($start_date) {
            $args['meta_query'][] = array(
                'key' => '_ptp_camp_start_date',
                'value' => $start_date,
                'compare' => '>=',
                'type' => 'DATE'
            );
        }
        
        $products = get_posts($args);
        $camps = array();
        
        foreach ($products as $product) {
            $wc_product = wc_get_product($product->ID);
            if (!$wc_product) continue;
            
            $camps[] = $this->format_camp($wc_product);
        }
        
        // Sort by start date
        usort($camps, function($a, $b) {
            return strtotime($a['start_date']) - strtotime($b['start_date']);
        });
        
        return rest_ensure_response($camps);
    }
    
    /**
     * Get clinics from WooCommerce
     */
    public function get_clinics($request) {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $request->get_param('limit') ?: 20,
            'post_status' => 'publish',
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => array('clinics', 'clinic', 'training-clinics'),
                    'operator' => 'IN'
                )
            ),
            'meta_query' => array(
                array(
                    'key' => '_stock_status',
                    'value' => 'instock'
                )
            )
        );
        
        $products = get_posts($args);
        $clinics = array();
        
        foreach ($products as $product) {
            $wc_product = wc_get_product($product->ID);
            if (!$wc_product) continue;
            
            $clinics[] = $this->format_clinic($wc_product);
        }
        
        return rest_ensure_response($clinics);
    }
    
    /**
     * Format camp product data
     */
    private function format_camp($product) {
        $id = $product->get_id();
        
        return array(
            'id' => $id,
            'name' => $product->get_name(),
            'slug' => $product->get_slug(),
            'url' => get_permalink($id),
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'image' => wp_get_attachment_url($product->get_image_id()),
            'short_description' => $product->get_short_description(),
            'start_date' => get_post_meta($id, '_ptp_camp_start_date', true),
            'end_date' => get_post_meta($id, '_ptp_camp_end_date', true),
            'location' => array(
                'name' => get_post_meta($id, '_ptp_camp_location_name', true),
                'address' => get_post_meta($id, '_ptp_camp_address', true),
                'city' => get_post_meta($id, '_ptp_camp_city', true),
                'state' => get_post_meta($id, '_ptp_camp_state', true),
                'zip' => get_post_meta($id, '_ptp_camp_zip', true),
                'lat' => get_post_meta($id, '_ptp_camp_lat', true),
                'lng' => get_post_meta($id, '_ptp_camp_lng', true)
            ),
            'age_groups' => get_post_meta($id, '_ptp_camp_age_groups', true),
            'time_slot' => get_post_meta($id, '_ptp_camp_time_slot', true),
            'spots_remaining' => $product->get_stock_quantity(),
            'is_on_sale' => $product->is_on_sale(),
            'categories' => wp_get_post_terms($id, 'product_cat', array('fields' => 'names'))
        );
    }
    
    /**
     * Format clinic product data
     */
    private function format_clinic($product) {
        $id = $product->get_id();
        
        return array(
            'id' => $id,
            'name' => $product->get_name(),
            'slug' => $product->get_slug(),
            'url' => get_permalink($id),
            'price' => $product->get_price(),
            'image' => wp_get_attachment_url($product->get_image_id()),
            'short_description' => $product->get_short_description(),
            'date' => get_post_meta($id, '_ptp_clinic_date', true),
            'time' => get_post_meta($id, '_ptp_clinic_time', true),
            'duration' => get_post_meta($id, '_ptp_clinic_duration', true),
            'location' => array(
                'name' => get_post_meta($id, '_ptp_clinic_location_name', true),
                'address' => get_post_meta($id, '_ptp_clinic_address', true),
                'city' => get_post_meta($id, '_ptp_clinic_city', true),
                'state' => get_post_meta($id, '_ptp_clinic_state', true)
            ),
            'skill_focus' => get_post_meta($id, '_ptp_clinic_skill_focus', true),
            'age_groups' => get_post_meta($id, '_ptp_clinic_age_groups', true),
            'spots_remaining' => $product->get_stock_quantity()
        );
    }
    
    /**
     * Generate referral link for trainer
     */
    public function generate_referral_link($request) {
        $trainer = PTP_Database::get_trainer_by_user(get_current_user_id());
        $product_id = $request->get_param('product_id');
        $referral_type = $request->get_param('type') ?: 'camp'; // camp, clinic, or general
        
        global $wpdb;
        
        // Check for existing referral code
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ptp_trainer_referrals 
             WHERE trainer_id = %d AND product_id = %d AND referral_type = %s",
            $trainer->id, $product_id, $referral_type
        ));
        
        if ($existing) {
            $referral_code = $existing->referral_code;
        } else {
            // Generate unique code
            $referral_code = strtoupper(substr($trainer->slug, 0, 4)) . '-' . strtoupper(wp_generate_password(6, false));
            
            $commission_rate = floatval(get_option('ptp_trainer_referral_commission', 10));
            
            $wpdb->insert("{$wpdb->prefix}ptp_trainer_referrals", array(
                'trainer_id' => $trainer->id,
                'referral_type' => $referral_type,
                'referral_code' => $referral_code,
                'product_id' => $product_id,
                'product_name' => get_the_title($product_id),
                'commission_rate' => $commission_rate,
                'is_active' => 1
            ));
        }
        
        // Build referral URL
        $base_url = $product_id ? get_permalink($product_id) : home_url('/');
        $referral_url = add_query_arg('ref', $referral_code, $base_url);
        
        return rest_ensure_response(array(
            'code' => $referral_code,
            'url' => $referral_url,
            'short_url' => $referral_url // Could integrate with URL shortener
        ));
    }
    
    /**
     * Get trainer's referral stats
     */
    public function get_referral_stats($request) {
        $trainer = PTP_Database::get_trainer_by_user(get_current_user_id());
        
        global $wpdb;
        
        $referrals = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ptp_trainer_referrals WHERE trainer_id = %d ORDER BY created_at DESC",
            $trainer->id
        ));
        
        $totals = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                SUM(clicks) as total_clicks,
                SUM(conversions) as total_conversions,
                SUM(revenue) as total_revenue,
                SUM(commission_earned) as total_commission
             FROM {$wpdb->prefix}ptp_trainer_referrals 
             WHERE trainer_id = %d",
            $trainer->id
        ));
        
        return rest_ensure_response(array(
            'referrals' => $referrals,
            'totals' => array(
                'clicks' => intval($totals->total_clicks),
                'conversions' => intval($totals->total_conversions),
                'revenue' => floatval($totals->total_revenue),
                'commission' => floatval($totals->total_commission),
                'conversion_rate' => $totals->total_clicks > 0 
                    ? round(($totals->total_conversions / $totals->total_clicks) * 100, 1) 
                    : 0
            )
        ));
    }
    
    /**
     * Track referral link click
     */
    public function track_referral_click() {
        if (!isset($_GET['ref'])) return;
        
        $referral_code = sanitize_text_field($_GET['ref']);
        
        global $wpdb;
        
        // Validate and get referral
        $referral = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ptp_trainer_referrals WHERE referral_code = %s AND is_active = 1",
            $referral_code
        ));
        
        if (!$referral) return;
        
        // Increment click count
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}ptp_trainer_referrals SET clicks = clicks + 1 WHERE id = %d",
            $referral->id
        ));
        
        // Store in session/cookie for conversion tracking
        if (!session_id()) {
            session_start();
        }
        $_SESSION['ptp_referral_code'] = $referral_code;
        $_SESSION['ptp_referral_id'] = $referral->id;
        $_SESSION['ptp_trainer_id'] = $referral->trainer_id;
        
        // Also set cookie for 30 days
        setcookie('ptp_referral_code', $referral_code, time() + (30 * DAY_IN_SECONDS), '/');
    }
    
    /**
     * Track referral conversion on checkout
     */
    public function track_referral_conversion($order_id, $posted_data, $order) {
        // Check session first, then cookie
        if (!session_id()) {
            session_start();
        }
        
        $referral_code = isset($_SESSION['ptp_referral_code']) 
            ? $_SESSION['ptp_referral_code'] 
            : (isset($_COOKIE['ptp_referral_code']) ? $_COOKIE['ptp_referral_code'] : null);
        
        if (!$referral_code) return;
        
        global $wpdb;
        
        $referral = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ptp_trainer_referrals WHERE referral_code = %s",
            $referral_code
        ));
        
        if (!$referral) return;
        
        // Store referral info on order
        update_post_meta($order_id, '_ptp_referral_code', $referral_code);
        update_post_meta($order_id, '_ptp_referral_id', $referral->id);
        update_post_meta($order_id, '_ptp_trainer_id', $referral->trainer_id);
        update_post_meta($order_id, '_ptp_referral_status', 'pending');
        
        // Calculate commission
        $order_total = $order->get_total();
        $commission = $order_total * ($referral->commission_rate / 100);
        update_post_meta($order_id, '_ptp_referral_commission', $commission);
        
        // Clear session
        unset($_SESSION['ptp_referral_code']);
        unset($_SESSION['ptp_referral_id']);
        unset($_SESSION['ptp_trainer_id']);
    }
    
    /**
     * Credit commission when order completes
     */
    public function credit_referral_commission($order_id) {
        $referral_id = get_post_meta($order_id, '_ptp_referral_id', true);
        $status = get_post_meta($order_id, '_ptp_referral_status', true);
        
        if (!$referral_id || $status === 'credited') return;
        
        $order = wc_get_order($order_id);
        $order_total = $order->get_total();
        $commission = floatval(get_post_meta($order_id, '_ptp_referral_commission', true));
        
        global $wpdb;
        
        // Update referral stats
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}ptp_trainer_referrals 
             SET conversions = conversions + 1,
                 revenue = revenue + %f,
                 commission_earned = commission_earned + %f
             WHERE id = %d",
            $order_total, $commission, $referral_id
        ));
        
        // Mark as credited
        update_post_meta($order_id, '_ptp_referral_status', 'credited');
        
        // Log the commission credit
        $trainer_id = get_post_meta($order_id, '_ptp_trainer_id', true);
        $wpdb->insert("{$wpdb->prefix}ptp_payouts", array(
            'trainer_id' => $trainer_id,
            'pack_id' => null,
            'session_id' => null,
            'gross_amount' => $order_total,
            'platform_fee' => $order_total - $commission,
            'trainer_payout' => $commission,
            'status' => 'pending'
        ));
        
        // Notify trainer
        $this->notify_trainer_of_referral($trainer_id, $order_id, $commission);
    }
    
    /**
     * Notify trainer of referral commission
     */
    private function notify_trainer_of_referral($trainer_id, $order_id, $commission) {
        $trainer = PTP_Database::get_trainer($trainer_id);
        if (!$trainer) return;
        
        $user = get_user_by('ID', $trainer->user_id);
        if (!$user) return;
        
        $order = wc_get_order($order_id);
        
        $subject = '🎉 Referral Commission Earned!';
        $message = "Hi {$trainer->display_name},\n\n";
        $message .= "Great news! Someone used your referral link and made a purchase.\n\n";
        $message .= "Order Details:\n";
        $message .= "- Product: " . implode(', ', array_map(function($item) { 
            return $item->get_name(); 
        }, $order->get_items())) . "\n";
        $message .= "- Order Total: $" . number_format($order->get_total(), 2) . "\n";
        $message .= "- Your Commission: $" . number_format($commission, 2) . "\n\n";
        $message .= "This amount will be added to your next payout.\n\n";
        $message .= "Keep sharing your referral links!\n";
        $message .= "View your stats: " . home_url('/trainer-dashboard/?tab=referrals') . "\n\n";
        $message .= "The PTP Team";
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * Show referral badge on product page
     */
    public function show_trainer_referral_badge() {
        if (!isset($_GET['ref'])) return;
        
        $referral_code = sanitize_text_field($_GET['ref']);
        
        global $wpdb;
        $referral = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, t.display_name, t.profile_photo FROM {$wpdb->prefix}ptp_trainer_referrals r
             LEFT JOIN {$wpdb->prefix}ptp_trainers t ON r.trainer_id = t.id
             WHERE r.referral_code = %s AND r.is_active = 1",
            $referral_code
        ));
        
        if (!$referral) return;
        
        ?>
        <div class="ptp-referral-badge">
            <?php if ($referral->profile_photo): ?>
                <img src="<?php echo esc_url($referral->profile_photo); ?>" alt="<?php echo esc_attr($referral->display_name); ?>" />
            <?php endif; ?>
            <div class="ptp-referral-badge-content">
                <span class="ptp-referral-badge-label">Referred by PTP Trainer</span>
                <strong><?php echo esc_html($referral->display_name); ?></strong>
            </div>
        </div>
        <style>
            .ptp-referral-badge {
                display: flex;
                align-items: center;
                gap: 12px;
                background: linear-gradient(135deg, #FCB900 0%, #F59E0B 100%);
                color: #0E0F11;
                padding: 12px 16px;
                border-radius: 8px;
                margin-bottom: 20px;
            }
            .ptp-referral-badge img {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                object-fit: cover;
                border: 2px solid rgba(255,255,255,0.5);
            }
            .ptp-referral-badge-label {
                display: block;
                font-size: 11px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                opacity: 0.8;
            }
            .ptp-referral-badge strong {
                font-size: 15px;
            }
        </style>
        <?php
    }
    
    /**
     * Add product meta fields for camps/clinics
     */
    public function add_product_fields() {
        global $post;
        
        echo '<div class="options_group ptp-product-fields">';
        echo '<h4 style="padding-left: 12px; margin-top: 10px;">PTP Camp/Clinic Details</h4>';
        
        // Start Date
        woocommerce_wp_text_input(array(
            'id' => '_ptp_camp_start_date',
            'label' => 'Start Date',
            'type' => 'date',
            'desc_tip' => true,
            'description' => 'Camp/clinic start date'
        ));
        
        // End Date
        woocommerce_wp_text_input(array(
            'id' => '_ptp_camp_end_date',
            'label' => 'End Date',
            'type' => 'date',
            'desc_tip' => true,
            'description' => 'Camp end date (leave empty for single-day clinics)'
        ));
        
        // Time Slot
        woocommerce_wp_select(array(
            'id' => '_ptp_camp_time_slot',
            'label' => 'Time Slot',
            'options' => array(
                '' => 'Select time slot',
                'morning' => 'Morning (9am-12pm)',
                'afternoon' => 'Afternoon (1pm-4pm)',
                'full_day' => 'Full Day (9am-4pm)',
                'evening' => 'Evening (5pm-7pm)'
            )
        ));
        
        // Location Name
        woocommerce_wp_text_input(array(
            'id' => '_ptp_camp_location_name',
            'label' => 'Location Name',
            'placeholder' => 'e.g., Radnor High School'
        ));
        
        // Address
        woocommerce_wp_text_input(array(
            'id' => '_ptp_camp_address',
            'label' => 'Address',
            'placeholder' => 'Street address'
        ));
        
        // City
        woocommerce_wp_text_input(array(
            'id' => '_ptp_camp_city',
            'label' => 'City'
        ));
        
        // State
        woocommerce_wp_select(array(
            'id' => '_ptp_camp_state',
            'label' => 'State',
            'options' => array(
                '' => 'Select state',
                'PA' => 'Pennsylvania',
                'NJ' => 'New Jersey',
                'DE' => 'Delaware',
                'MD' => 'Maryland',
                'NY' => 'New York'
            )
        ));
        
        // ZIP
        woocommerce_wp_text_input(array(
            'id' => '_ptp_camp_zip',
            'label' => 'ZIP Code'
        ));
        
        // Lat/Lng for map
        woocommerce_wp_text_input(array(
            'id' => '_ptp_camp_lat',
            'label' => 'Latitude',
            'placeholder' => 'e.g., 40.0379'
        ));
        
        woocommerce_wp_text_input(array(
            'id' => '_ptp_camp_lng',
            'label' => 'Longitude',
            'placeholder' => 'e.g., -75.3599'
        ));
        
        // Age Groups
        woocommerce_wp_text_input(array(
            'id' => '_ptp_camp_age_groups',
            'label' => 'Age Groups',
            'placeholder' => 'e.g., Ages 6-14'
        ));
        
        echo '</div>';
    }
    
    /**
     * Save product meta fields
     */
    public function save_product_fields($post_id) {
        $fields = array(
            '_ptp_camp_start_date',
            '_ptp_camp_end_date',
            '_ptp_camp_time_slot',
            '_ptp_camp_location_name',
            '_ptp_camp_address',
            '_ptp_camp_city',
            '_ptp_camp_state',
            '_ptp_camp_zip',
            '_ptp_camp_lat',
            '_ptp_camp_lng',
            '_ptp_camp_age_groups'
        );
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }
    
    /**
     * Camp Finder Shortcode
     */
    public function render_camp_finder($atts) {
        $atts = shortcode_atts(array(
            'state' => '',
            'show_map' => 'true',
            'limit' => 20
        ), $atts);
        
        ob_start();
        include PTP_TRAINING_PATH . 'templates/frontend/camp-finder.php';
        return ob_get_clean();
    }
    
    /**
     * Upcoming Clinics Shortcode
     */
    public function render_upcoming_clinics($atts) {
        $atts = shortcode_atts(array(
            'limit' => 6,
            'style' => 'grid'
        ), $atts);
        
        ob_start();
        include PTP_TRAINING_PATH . 'templates/frontend/clinics-grid.php';
        return ob_get_clean();
    }
}

// Initialize
PTP_WooCommerce::instance();
