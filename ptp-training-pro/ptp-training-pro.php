<?php
/**
 * Plugin Name: PTP Private Training
 * Plugin URI: https://ptpsummercamps.com
 * Description: Professional private training marketplace with TeachMeTo-style UX, map-based discovery, trainer videos, lesson packs, Google Calendar sync, and mobile-first design. Features polished admin UI and fully wired application system.
 * Version: 4.0.0
 * Author: PTP Soccer Camps
 * Text Domain: ptp-training-pro
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 */

if (!defined('ABSPATH')) exit;

define('PTP_TRAINING_VERSION', '4.1.0');
define('PTP_TRAINING_PATH', plugin_dir_path(__FILE__));
define('PTP_TRAINING_URL', plugin_dir_url(__FILE__));
define('PTP_TRAINING_BASENAME', plugin_basename(__FILE__));

class PTP_Training_Pro {
    
    private static $instance = null;
    private $woocommerce_active = false;
    
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->check_woocommerce();
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Check if WooCommerce is active
     */
    private function check_woocommerce() {
        $this->woocommerce_active = class_exists('WooCommerce') || in_array(
            'woocommerce/woocommerce.php', 
            apply_filters('active_plugins', get_option('active_plugins'))
        );
    }
    
    /**
     * Check if WooCommerce is available
     */
    public function is_woocommerce_active() {
        return $this->woocommerce_active;
    }
    
    private function load_dependencies() {
        // Core
        require_once PTP_TRAINING_PATH . 'includes/class-ptp-database.php';
        require_once PTP_TRAINING_PATH . 'includes/class-ptp-roles.php';
        require_once PTP_TRAINING_PATH . 'includes/class-ptp-post-types.php';
        
        // Features
        require_once PTP_TRAINING_PATH . 'includes/api/class-ptp-rest-api.php';
        require_once PTP_TRAINING_PATH . 'includes/stripe/class-ptp-stripe.php';
        require_once PTP_TRAINING_PATH . 'includes/calendar/class-ptp-google-calendar.php';
        require_once PTP_TRAINING_PATH . 'includes/maps/class-ptp-maps.php';
        require_once PTP_TRAINING_PATH . 'includes/video/class-ptp-video.php';
        require_once PTP_TRAINING_PATH . 'includes/reviews/class-ptp-reviews.php';
        require_once PTP_TRAINING_PATH . 'includes/sms/class-ptp-twilio.php';
        require_once PTP_TRAINING_PATH . 'includes/class-ptp-notifications.php';
        
        // WooCommerce integration (if available)
        if ($this->woocommerce_active && file_exists(PTP_TRAINING_PATH . 'includes/class-ptp-woocommerce.php')) {
            require_once PTP_TRAINING_PATH . 'includes/class-ptp-woocommerce.php';
        }
        
        // Admin
        if (is_admin()) {
            require_once PTP_TRAINING_PATH . 'includes/class-ptp-admin.php';
        }
    }
    
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin'));
        add_action('admin_notices', array($this, 'admin_notices'));
        
        // Application form handler
        add_action('wp_ajax_ptp_submit_application', array($this, 'handle_application_submission'));
        add_action('wp_ajax_nopriv_ptp_submit_application', array($this, 'handle_application_submission'));
        
        // Primary shortcodes (spec naming)
        add_shortcode('ptp_training_directory', array($this, 'render_marketplace'));
        add_shortcode('ptp_trainer_profile', array($this, 'render_trainer_profile'));
        add_shortcode('ptp_training_checkout', array($this, 'render_checkout'));
        add_shortcode('ptp_parent_dashboard', array($this, 'render_my_training'));
        add_shortcode('ptp_trainer_dashboard', array($this, 'render_trainer_dashboard'));
        add_shortcode('ptp_application_form', array($this, 'render_application'));
        add_shortcode('ptp_private_training', array($this, 'render_private_training'));

        // Alias shortcodes (backward compatibility)
        add_shortcode('ptp_trainer_marketplace', array($this, 'render_marketplace'));
        add_shortcode('ptp_trainer_application', array($this, 'render_application'));
        add_shortcode('ptp_my_training', array($this, 'render_my_training'));
        add_shortcode('ptp_checkout', array($this, 'render_checkout'));

        // Quick booking form AJAX handler
        add_action('wp_ajax_ptp_quick_booking_request', array($this, 'handle_quick_booking_request'));
        add_action('wp_ajax_nopriv_ptp_quick_booking_request', array($this, 'handle_quick_booking_request'));
    }
    
    /**
     * Display admin notices
     */
    public function admin_notices() {
        // WooCommerce notice (informational, not required)
        if (!$this->woocommerce_active && current_user_can('manage_options')) {
            $screen = get_current_screen();
            if ($screen && strpos($screen->id, 'ptp-training') !== false) {
                echo '<div class="notice notice-info is-dismissible">';
                echo '<p><strong>PTP Private Training:</strong> WooCommerce is not active. The plugin will use Stripe direct checkout. Install WooCommerce for additional cart/order features.</p>';
                echo '</div>';
            }
        }
    }
    
    /**
     * Handle application form submission via AJAX
     */
    public function handle_application_submission() {
        // Verify nonce
        if (!isset($_POST['ptp_application_nonce']) || !wp_verify_nonce($_POST['ptp_application_nonce'], 'ptp_application_submit')) {
            wp_send_json_error(array('message' => 'Security check failed. Please refresh the page and try again.'));
        }
        
        // Validate required fields
        $required = array('first_name', 'last_name', 'email', 'phone', 'role_type');
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(array('message' => 'Please fill in all required fields.'));
            }
        }
        
        // Validate email
        $email = sanitize_email($_POST['email']);
        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Please enter a valid email address.'));
        }
        
        // Sanitize all data
        $data = array(
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'email' => $email,
            'phone' => sanitize_text_field($_POST['phone']),
            'role_type' => sanitize_text_field($_POST['role_type']),
            'location_city' => sanitize_text_field($_POST['location_city'] ?? ''),
            'location_state' => sanitize_text_field($_POST['location_state'] ?? ''),
            'location_zip' => sanitize_text_field($_POST['location_zip'] ?? ''),
            'experience_summary' => sanitize_textarea_field($_POST['experience_summary'] ?? ''),
            'playing_background' => sanitize_textarea_field($_POST['playing_background'] ?? ''),
            'coaching_experience' => sanitize_textarea_field($_POST['coaching_experience'] ?? ''),
            'certifications' => sanitize_textarea_field($_POST['certifications'] ?? ''),
            'intro_video_url' => esc_url_raw($_POST['intro_video_url'] ?? ''),
            'instagram_handle' => sanitize_text_field($_POST['instagram_handle'] ?? ''),
            'referral_source' => sanitize_text_field($_POST['referral_source'] ?? ''),
            'why_join' => sanitize_textarea_field($_POST['why_join'] ?? ''),
            'availability_notes' => sanitize_textarea_field($_POST['availability_notes'] ?? ''),
            'status' => 'pending',
            'created_at' => current_time('mysql')
        );
        
        // Insert into database
        global $wpdb;
        $result = $wpdb->insert(
            "{$wpdb->prefix}ptp_applications",
            $data,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => 'There was an error submitting your application. Please try again.'));
        }
        
        // Send notification emails
        $this->send_application_emails($data);
        
        wp_send_json_success(array(
            'message' => 'Thank you for your application! We\'ll review it and get back to you within 2-3 business days.'
        ));
    }
    
    /**
     * Send application notification emails
     */
    private function send_application_emails($data) {
        // Admin notification
        $admin_email = get_option('ptp_admin_email', get_option('admin_email'));
        $subject = 'New Trainer Application: ' . $data['first_name'] . ' ' . $data['last_name'];
        $message = "A new trainer application has been submitted.\n\n";
        $message .= "Name: {$data['first_name']} {$data['last_name']}\n";
        $message .= "Email: {$data['email']}\n";
        $message .= "Phone: {$data['phone']}\n";
        $message .= "Role: {$data['role_type']}\n";
        $message .= "Location: {$data['location_city']}, {$data['location_state']}\n\n";
        $message .= "Review in admin: " . admin_url('admin.php?page=ptp-training-applications');
        
        wp_mail($admin_email, $subject, $message);
        
        // Applicant confirmation
        $subject = 'PTP Training Application Received';
        $message = "Hi {$data['first_name']},\n\n";
        $message .= "Thank you for applying to become a PTP trainer! We've received your application and will review it within 2-3 business days.\n\n";
        $message .= "What happens next:\n";
        $message .= "1. Our team reviews your application\n";
        $message .= "2. If approved, you'll receive onboarding instructions\n";
        $message .= "3. Set up your profile and start accepting bookings\n\n";
        $message .= "Questions? Reply to this email.\n\n";
        $message .= "Best,\nThe PTP Team";
        
        wp_mail($data['email'], $subject, $message);
    }
    
    public function activate() {
        PTP_Database::create_tables();
        PTP_Roles::create_roles();
        $this->create_pages();
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    public function init() {
        load_plugin_textdomain('ptp-training-pro', false, dirname(PTP_TRAINING_BASENAME) . '/languages');
    }
    
    private function create_pages() {
        $pages = array(
            'private-training' => array(
                'title' => 'Find a Trainer',
                'content' => '[ptp_trainer_marketplace]'
            ),
            'trainer' => array(
                'title' => 'Trainer Profile',
                'content' => '[ptp_trainer_profile]'
            ),
            'trainer-dashboard' => array(
                'title' => 'Trainer Dashboard',
                'content' => '[ptp_trainer_dashboard]'
            ),
            'become-a-trainer' => array(
                'title' => 'Become a Trainer',
                'content' => '[ptp_trainer_application]'
            ),
            'my-training' => array(
                'title' => 'My Training',
                'content' => '[ptp_my_training]'
            ),
            'book-training' => array(
                'title' => 'Book Training',
                'content' => '[ptp_checkout]'
            )
        );
        
        foreach ($pages as $slug => $page) {
            if (!get_page_by_path($slug)) {
                wp_insert_post(array(
                    'post_title' => $page['title'],
                    'post_name' => $slug,
                    'post_content' => $page['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page'
                ));
            }
        }
    }
    
    public function enqueue_frontend() {
        wp_enqueue_style('ptp-training-frontend', PTP_TRAINING_URL . 'assets/css/frontend.css', array(), PTP_TRAINING_VERSION);
        wp_enqueue_script('ptp-training-frontend', PTP_TRAINING_URL . 'assets/js/frontend.js', array(), PTP_TRAINING_VERSION, true);
        
        // Google Maps
        $maps_key = get_option('ptp_google_maps_key');
        if ($maps_key) {
            wp_enqueue_script('google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . $maps_key . '&libraries=places', array(), null, true);
        }
        
        wp_localize_script('ptp-training-frontend', 'ptpTraining', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url('ptp-training/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'review_nonce' => wp_create_nonce('ptp_review_nonce'),
            'maps_key' => $maps_key,
            'currency' => function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '$',
            'user_logged_in' => is_user_logged_in(),
            'user_id' => get_current_user_id()
        ));
    }
    
    public function enqueue_admin($hook) {
        if (strpos($hook, 'ptp-training') === false) return;
        
        wp_enqueue_style('ptp-training-admin', PTP_TRAINING_URL . 'assets/css/admin.css', array(), PTP_TRAINING_VERSION);
        wp_enqueue_script('ptp-training-admin', PTP_TRAINING_URL . 'assets/js/admin.js', array('jquery'), PTP_TRAINING_VERSION, true);
        
        wp_localize_script('ptp-training-admin', 'ptpAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ptp_admin_nonce')
        ));
    }
    
    // Shortcode Renderers
    public function render_marketplace($atts) {
        ob_start();
        include PTP_TRAINING_PATH . 'templates/frontend/marketplace.php';
        return ob_get_clean();
    }
    
    public function render_trainer_profile($atts) {
        ob_start();
        include PTP_TRAINING_PATH . 'templates/frontend/trainer-profile.php';
        return ob_get_clean();
    }
    
    public function render_trainer_dashboard($atts) {
        if (!is_user_logged_in()) {
            return '<div class="ptp-login-required"><p>Please log in to access your dashboard.</p><a href="' . wp_login_url(get_permalink()) . '" class="ptp-btn">Log In</a></div>';
        }
        ob_start();
        include PTP_TRAINING_PATH . 'templates/frontend/trainer-dashboard.php';
        return ob_get_clean();
    }
    
    public function render_application($atts) {
        ob_start();
        include PTP_TRAINING_PATH . 'templates/frontend/application.php';
        return ob_get_clean();
    }
    
    public function render_my_training($atts) {
        if (!is_user_logged_in()) {
            return '<div class="ptp-login-required"><p>Please log in to view your training.</p><a href="' . wp_login_url(get_permalink()) . '" class="ptp-btn">Log In</a></div>';
        }
        ob_start();
        include PTP_TRAINING_PATH . 'templates/frontend/my-training.php';
        return ob_get_clean();
    }
    
    public function render_checkout($atts) {
        ob_start();
        include PTP_TRAINING_PATH . 'templates/frontend/checkout.php';
        return ob_get_clean();
    }

    /**
     * Render the private training landing page with hero, trainer list, booking form
     */
    public function render_private_training($atts) {
        ob_start();
        include PTP_TRAINING_PATH . 'templates/frontend/private-training.php';
        return ob_get_clean();
    }

    /**
     * Handle quick booking request from the private training page
     */
    public function handle_quick_booking_request() {
        // Verify nonce
        if (!isset($_POST['ptp_booking_nonce']) || !wp_verify_nonce($_POST['ptp_booking_nonce'], 'ptp_booking_request')) {
            wp_send_json_error(array('message' => 'Security check failed. Please refresh the page and try again.'));
        }

        // Validate required fields
        $required = array('parent_name', 'parent_email', 'parent_phone', 'player_name', 'player_age');
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(array('message' => 'Please fill in all required fields.'));
            }
        }

        // Validate email
        $email = sanitize_email($_POST['parent_email']);
        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Please enter a valid email address.'));
        }

        // Get or create customer user
        $user = get_user_by('email', $email);
        if (!$user) {
            // Create a new user for the customer
            $user_id = wp_create_user($email, wp_generate_password(), $email);
            if (is_wp_error($user_id)) {
                $user_id = 0; // Continue without user
            } else {
                wp_update_user(array(
                    'ID' => $user_id,
                    'display_name' => sanitize_text_field($_POST['parent_name']),
                    'first_name' => sanitize_text_field($_POST['parent_name'])
                ));
                update_user_meta($user_id, 'phone', sanitize_text_field($_POST['parent_phone']));
            }
        } else {
            $user_id = $user->ID;
        }

        // Parse preferred date/time
        $session_date = !empty($_POST['preferred_date']) ? sanitize_text_field($_POST['preferred_date']) : '0000-00-00';
        $start_time = '00:00:00';
        if (!empty($_POST['preferred_time'])) {
            switch ($_POST['preferred_time']) {
                case 'morning': $start_time = '09:00:00'; break;
                case 'afternoon': $start_time = '14:00:00'; break;
                case 'evening': $start_time = '17:00:00'; break;
            }
        }

        // Create session request
        $session_data = array(
            'trainer_id' => 0, // Will be assigned later
            'customer_id' => $user_id ?: 0,
            'player_name' => sanitize_text_field($_POST['player_name']),
            'player_age' => intval($_POST['player_age']),
            'session_date' => $session_date,
            'start_time' => $start_time,
            'end_time' => date('H:i:s', strtotime($start_time) + 3600),
            'location_text' => sanitize_text_field($_POST['location'] ?? ''),
            'session_type' => sanitize_text_field($_POST['session_type'] ?? '1on1'),
            'customer_notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
            'session_status' => PTP_Database::SESSION_STATUS_REQUESTED,
            'payment_status' => PTP_Database::PAYMENT_STATUS_UNPAID
        );

        $session_id = PTP_Database::create_session($session_data);

        if (!$session_id) {
            wp_send_json_error(array('message' => 'There was an error submitting your request. Please try again.'));
        }

        // Store additional contact info in session meta
        global $wpdb;
        $wpdb->update(
            "{$wpdb->prefix}ptp_sessions",
            array(
                'internal_notes' => sprintf(
                    "Quick Booking Request\nParent: %s\nEmail: %s\nPhone: %s\nPreferred Time: %s",
                    sanitize_text_field($_POST['parent_name']),
                    $email,
                    sanitize_text_field($_POST['parent_phone']),
                    sanitize_text_field($_POST['preferred_time'] ?? 'Flexible')
                )
            ),
            array('id' => $session_id)
        );

        // Send notification to admin
        $admin_email = get_option('ptp_notification_admin_email', get_option('ptp_admin_email', get_option('admin_email')));
        $subject = 'New Quick Booking Request - ' . sanitize_text_field($_POST['player_name']);
        $message = "A new training request has been submitted via the quick booking form.\n\n";
        $message .= "Parent: " . sanitize_text_field($_POST['parent_name']) . "\n";
        $message .= "Email: " . $email . "\n";
        $message .= "Phone: " . sanitize_text_field($_POST['parent_phone']) . "\n";
        $message .= "Player: " . sanitize_text_field($_POST['player_name']) . " (Age " . intval($_POST['player_age']) . ")\n";
        $message .= "Session Type: " . sanitize_text_field($_POST['session_type'] ?? '1on1') . "\n";
        $message .= "Preferred Date: " . ($session_date !== '0000-00-00' ? $session_date : 'Flexible') . "\n";
        $message .= "Preferred Time: " . sanitize_text_field($_POST['preferred_time'] ?? 'Flexible') . "\n";
        $message .= "Location: " . sanitize_text_field($_POST['location'] ?? 'Not specified') . "\n";
        $message .= "Notes: " . sanitize_textarea_field($_POST['notes'] ?? 'None') . "\n\n";
        $message .= "View in admin: " . admin_url('admin.php?page=ptp-training-sessions') . "\n";

        wp_mail($admin_email, $subject, $message);

        wp_send_json_success(array(
            'message' => 'Thank you! Your training request has been submitted. We\'ll be in touch within 24 hours.',
            'session_id' => $session_id
        ));
    }
}

function ptp_training_pro() {
    return PTP_Training_Pro::instance();
}

add_action('plugins_loaded', 'ptp_training_pro');
