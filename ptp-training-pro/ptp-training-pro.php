<?php
/**
 * Plugin Name: PTP Training Pro
 * Plugin URI: https://ptpsummercamps.com
 * Description: Professional private training marketplace with map-based discovery, trainer videos, lesson packs, Google Calendar sync, and mobile-first UX.
 * Version: 3.2.0
 * Author: PTP Soccer Camps
 * Text Domain: ptp-training-pro
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 */

if (!defined('ABSPATH')) exit;

define('PTP_TRAINING_VERSION', '3.2.0');
define('PTP_TRAINING_PATH', plugin_dir_path(__FILE__));
define('PTP_TRAINING_URL', plugin_dir_url(__FILE__));
define('PTP_TRAINING_BASENAME', plugin_basename(__FILE__));

class PTP_Training_Pro {
    
    private static $instance = null;
    
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
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
        
        // WooCommerce Integration
        require_once PTP_TRAINING_PATH . 'includes/woocommerce/class-ptp-woocommerce.php';
        
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
        
        // Shortcodes
        add_shortcode('ptp_trainer_marketplace', array($this, 'render_marketplace'));
        add_shortcode('ptp_trainer_profile', array($this, 'render_trainer_profile'));
        add_shortcode('ptp_trainer_dashboard', array($this, 'render_trainer_dashboard'));
        add_shortcode('ptp_trainer_application', array($this, 'render_application'));
        add_shortcode('ptp_my_training', array($this, 'render_my_training'));
        add_shortcode('ptp_checkout', array($this, 'render_checkout'));
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
}

function ptp_training_pro() {
    return PTP_Training_Pro::instance();
}

add_action('plugins_loaded', 'ptp_training_pro');
