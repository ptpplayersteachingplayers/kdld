<?php
/**
 * Admin Dashboard & Settings - v4.1
 * Enhanced admin pages for PTP Private Training
 */

if (!defined('ABSPATH')) exit;

class PTP_Admin {

    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menus'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_ptp_update_session_status', array($this, 'ajax_update_session_status'));
        add_action('wp_ajax_ptp_approve_trainer', array($this, 'ajax_approve_trainer'));
        add_action('wp_ajax_ptp_reject_trainer', array($this, 'ajax_reject_trainer'));
        add_action('wp_ajax_ptp_process_payout', array($this, 'ajax_process_payout'));
    }

    /**
     * Add admin menus
     */
    public function add_admin_menus() {
        // Main menu
        add_menu_page(
            'PTP Private Training',
            'PTP Training',
            'manage_options',
            'ptp-training',
            array($this, 'render_dashboard_page'),
            'dashicons-groups',
            30
        );

        // Dashboard (same as main)
        add_submenu_page(
            'ptp-training',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'ptp-training',
            array($this, 'render_dashboard_page')
        );

        // Sessions
        add_submenu_page(
            'ptp-training',
            'Sessions',
            'Sessions',
            'manage_options',
            'ptp-training-sessions',
            array($this, 'render_sessions_page')
        );

        // Trainers
        add_submenu_page(
            'ptp-training',
            'Trainers',
            'Trainers',
            'manage_options',
            'ptp-training-trainers',
            array($this, 'render_trainers_page')
        );

        // Applications
        add_submenu_page(
            'ptp-training',
            'Applications',
            'Applications',
            'manage_options',
            'ptp-training-applications',
            array($this, 'render_applications_page')
        );

        // Payouts
        add_submenu_page(
            'ptp-training',
            'Payouts',
            'Payouts',
            'manage_options',
            'ptp-training-payouts',
            array($this, 'render_payouts_page')
        );

        // Settings
        add_submenu_page(
            'ptp-training',
            'Settings',
            'Settings',
            'manage_options',
            'ptp-training-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on PTP admin pages
        if (strpos($hook, 'ptp-training') === false) {
            return;
        }

        wp_enqueue_style(
            'ptp-admin-css',
            PTP_TRAINING_URL . 'assets/css/admin.css',
            array(),
            PTP_TRAINING_VERSION
        );

        wp_enqueue_script(
            'ptp-admin-js',
            PTP_TRAINING_URL . 'assets/js/admin.js',
            array('jquery'),
            PTP_TRAINING_VERSION,
            true
        );

        wp_localize_script('ptp-admin-js', 'ptpAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ptp_admin_nonce')
        ));
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // Platform Settings
        register_setting('ptp_training_settings', 'ptp_platform_fee_percent');
        register_setting('ptp_training_settings', 'ptp_admin_email');
        register_setting('ptp_training_settings', 'ptp_auto_confirm_on_payment');

        // Stripe Settings
        register_setting('ptp_training_settings', 'ptp_stripe_publishable_key');
        register_setting('ptp_training_settings', 'ptp_stripe_secret_key');
        register_setting('ptp_training_settings', 'ptp_stripe_webhook_secret');

        // Google Settings
        register_setting('ptp_training_settings', 'ptp_google_maps_key');
        register_setting('ptp_training_settings', 'ptp_google_client_id');
        register_setting('ptp_training_settings', 'ptp_google_client_secret');

        // SMS/Twilio Settings
        register_setting('ptp_training_settings', 'ptp_twilio_sid');
        register_setting('ptp_training_settings', 'ptp_twilio_token');
        register_setting('ptp_training_settings', 'ptp_twilio_phone');
        register_setting('ptp_training_settings', 'ptp_sms_enabled');

        // Notification Settings
        register_setting('ptp_training_settings', 'ptp_notification_admin_email');
        register_setting('ptp_training_settings', 'ptp_notification_from_name');
        register_setting('ptp_training_settings', 'ptp_notification_from_email');
        register_setting('ptp_training_settings', 'ptp_notify_parent_booking_request');
        register_setting('ptp_training_settings', 'ptp_notify_trainer_booking_request');
        register_setting('ptp_training_settings', 'ptp_notify_admin_booking_request');
        register_setting('ptp_training_settings', 'ptp_notify_parent_session_confirmed');
        register_setting('ptp_training_settings', 'ptp_notify_trainer_session_confirmed');
        register_setting('ptp_training_settings', 'ptp_notify_parent_session_cancelled');
        register_setting('ptp_training_settings', 'ptp_notify_trainer_session_cancelled');
        register_setting('ptp_training_settings', 'ptp_notify_parent_session_completed');
        register_setting('ptp_training_settings', 'ptp_notify_parent_payment_success');
        register_setting('ptp_training_settings', 'ptp_notify_admin_payment_success');

        // Email Templates
        register_setting('ptp_training_settings', 'ptp_email_booking_request_subject');
        register_setting('ptp_training_settings', 'ptp_email_booking_request_intro');
        register_setting('ptp_training_settings', 'ptp_email_session_confirmed_subject');
        register_setting('ptp_training_settings', 'ptp_email_session_confirmed_intro');
    }

    /**
     * Render Dashboard Page
     */
    public function render_dashboard_page() {
        $stats = PTP_Database::get_admin_stats();
        ?>
        <div class="wrap ptp-admin">
            <h1 class="ptp-admin-title">
                <span class="ptp-admin-logo">PTP</span>
                Private Training Dashboard
            </h1>

            <!-- Stats Grid -->
            <div class="ptp-stats-grid">
                <div class="ptp-stat-card">
                    <div class="ptp-stat-icon ptp-stat-icon--trainers">
                        <span class="dashicons dashicons-groups"></span>
                    </div>
                    <div class="ptp-stat-content">
                        <div class="ptp-stat-value"><?php echo number_format($stats['total_trainers']); ?></div>
                        <div class="ptp-stat-label">Active Trainers</div>
                    </div>
                </div>

                <div class="ptp-stat-card ptp-stat-card--highlight">
                    <div class="ptp-stat-icon ptp-stat-icon--pending">
                        <span class="dashicons dashicons-clock"></span>
                    </div>
                    <div class="ptp-stat-content">
                        <div class="ptp-stat-value"><?php echo number_format($stats['pending_applications']); ?></div>
                        <div class="ptp-stat-label">Pending Applications</div>
                    </div>
                    <?php if ($stats['pending_applications'] > 0): ?>
                        <a href="<?php echo admin_url('admin.php?page=ptp-training-applications'); ?>" class="ptp-stat-action">Review Now</a>
                    <?php endif; ?>
                </div>

                <div class="ptp-stat-card">
                    <div class="ptp-stat-icon ptp-stat-icon--sessions">
                        <span class="dashicons dashicons-calendar-alt"></span>
                    </div>
                    <div class="ptp-stat-content">
                        <div class="ptp-stat-value"><?php echo number_format($stats['upcoming_sessions']); ?></div>
                        <div class="ptp-stat-label">Upcoming Sessions</div>
                    </div>
                </div>

                <div class="ptp-stat-card">
                    <div class="ptp-stat-icon ptp-stat-icon--completed">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                    <div class="ptp-stat-content">
                        <div class="ptp-stat-value"><?php echo number_format($stats['completed_sessions']); ?></div>
                        <div class="ptp-stat-label">Completed Sessions</div>
                    </div>
                </div>

                <div class="ptp-stat-card ptp-stat-card--money">
                    <div class="ptp-stat-icon ptp-stat-icon--revenue">
                        <span class="dashicons dashicons-chart-bar"></span>
                    </div>
                    <div class="ptp-stat-content">
                        <div class="ptp-stat-value">$<?php echo number_format($stats['total_revenue'], 0); ?></div>
                        <div class="ptp-stat-label">Total Revenue</div>
                    </div>
                </div>

                <div class="ptp-stat-card ptp-stat-card--money">
                    <div class="ptp-stat-icon ptp-stat-icon--platform">
                        <span class="dashicons dashicons-money-alt"></span>
                    </div>
                    <div class="ptp-stat-content">
                        <div class="ptp-stat-value">$<?php echo number_format($stats['platform_revenue'], 0); ?></div>
                        <div class="ptp-stat-label">Platform Revenue</div>
                    </div>
                </div>

                <?php if ($stats['requested_sessions'] > 0): ?>
                <div class="ptp-stat-card ptp-stat-card--alert">
                    <div class="ptp-stat-icon ptp-stat-icon--requested">
                        <span class="dashicons dashicons-warning"></span>
                    </div>
                    <div class="ptp-stat-content">
                        <div class="ptp-stat-value"><?php echo number_format($stats['requested_sessions']); ?></div>
                        <div class="ptp-stat-label">Pending Session Requests</div>
                    </div>
                    <a href="<?php echo admin_url('admin.php?page=ptp-training-sessions&status=requested'); ?>" class="ptp-stat-action">Review</a>
                </div>
                <?php endif; ?>

                <?php if ($stats['pending_payouts'] > 0): ?>
                <div class="ptp-stat-card">
                    <div class="ptp-stat-icon ptp-stat-icon--payouts">
                        <span class="dashicons dashicons-update"></span>
                    </div>
                    <div class="ptp-stat-content">
                        <div class="ptp-stat-value"><?php echo number_format($stats['pending_payouts']); ?></div>
                        <div class="ptp-stat-label">Pending Payouts ($<?php echo number_format($stats['pending_payout_amount'], 0); ?>)</div>
                    </div>
                    <a href="<?php echo admin_url('admin.php?page=ptp-training-payouts'); ?>" class="ptp-stat-action">Process</a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="ptp-admin-section">
                <h2>Quick Actions</h2>
                <div class="ptp-quick-actions">
                    <a href="<?php echo admin_url('admin.php?page=ptp-training-sessions'); ?>" class="ptp-quick-action">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        View All Sessions
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=ptp-training-trainers'); ?>" class="ptp-quick-action">
                        <span class="dashicons dashicons-groups"></span>
                        Manage Trainers
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=ptp-training-applications'); ?>" class="ptp-quick-action">
                        <span class="dashicons dashicons-welcome-write-blog"></span>
                        Review Applications
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=ptp-training-settings'); ?>" class="ptp-quick-action">
                        <span class="dashicons dashicons-admin-settings"></span>
                        Plugin Settings
                    </a>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="ptp-admin-section">
                <h2>Recent Sessions</h2>
                <?php
                $recent_sessions = PTP_Database::get_sessions(array('limit' => 10));
                if ($recent_sessions):
                ?>
                <table class="ptp-admin-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Trainer</th>
                            <th>Player</th>
                            <th>Date/Time</th>
                            <th>Price</th>
                            <th>Session Status</th>
                            <th>Payment Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_sessions as $session): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($session->customer_name); ?></strong><br>
                                <small><?php echo esc_html($session->customer_email); ?></small>
                            </td>
                            <td><?php echo esc_html($session->trainer_name); ?></td>
                            <td>
                                <?php echo esc_html($session->player_name ?: 'N/A'); ?>
                                <?php if ($session->player_age): ?>
                                    <br><small>Age <?php echo esc_html($session->player_age); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($session->session_date !== '0000-00-00'): ?>
                                    <?php echo date('M j, Y', strtotime($session->session_date)); ?><br>
                                    <small><?php echo date('g:i A', strtotime($session->start_time)); ?></small>
                                <?php else: ?>
                                    <em>Not scheduled</em>
                                <?php endif; ?>
                            </td>
                            <td>$<?php echo number_format($session->price, 2); ?></td>
                            <td>
                                <span class="ptp-status ptp-status--<?php echo esc_attr($session->session_status); ?>">
                                    <?php echo esc_html(ucfirst($session->session_status)); ?>
                                </span>
                            </td>
                            <td>
                                <span class="ptp-status ptp-status--payment-<?php echo esc_attr($session->payment_status); ?>">
                                    <?php echo esc_html(ucfirst($session->payment_status)); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="ptp-empty">No sessions yet.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render Sessions Page
     */
    public function render_sessions_page() {
        // Get filters
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $payment_filter = isset($_GET['payment']) ? sanitize_text_field($_GET['payment']) : '';
        $trainer_filter = isset($_GET['trainer']) ? intval($_GET['trainer']) : 0;
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';

        $args = array(
            'session_status' => $status_filter,
            'payment_status' => $payment_filter,
            'trainer_id' => $trainer_filter,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'limit' => 50
        );

        $sessions = PTP_Database::get_sessions($args);
        $trainers = PTP_Database::get_trainers(array('status' => 'approved', 'limit' => 100));
        ?>
        <div class="wrap ptp-admin">
            <h1 class="ptp-admin-title">Sessions</h1>

            <!-- Filters -->
            <div class="ptp-filters">
                <form method="get" class="ptp-filter-form">
                    <input type="hidden" name="page" value="ptp-training-sessions">

                    <div class="ptp-filter-group">
                        <label>Session Status</label>
                        <select name="status">
                            <option value="">All Statuses</option>
                            <option value="requested" <?php selected($status_filter, 'requested'); ?>>Requested</option>
                            <option value="confirmed" <?php selected($status_filter, 'confirmed'); ?>>Confirmed</option>
                            <option value="scheduled" <?php selected($status_filter, 'scheduled'); ?>>Scheduled</option>
                            <option value="completed" <?php selected($status_filter, 'completed'); ?>>Completed</option>
                            <option value="cancelled" <?php selected($status_filter, 'cancelled'); ?>>Cancelled</option>
                            <option value="no_show" <?php selected($status_filter, 'no_show'); ?>>No Show</option>
                        </select>
                    </div>

                    <div class="ptp-filter-group">
                        <label>Payment Status</label>
                        <select name="payment">
                            <option value="">All Payments</option>
                            <option value="unpaid" <?php selected($payment_filter, 'unpaid'); ?>>Unpaid</option>
                            <option value="pending" <?php selected($payment_filter, 'pending'); ?>>Pending</option>
                            <option value="paid" <?php selected($payment_filter, 'paid'); ?>>Paid</option>
                            <option value="refunded" <?php selected($payment_filter, 'refunded'); ?>>Refunded</option>
                        </select>
                    </div>

                    <div class="ptp-filter-group">
                        <label>Trainer</label>
                        <select name="trainer">
                            <option value="">All Trainers</option>
                            <?php foreach ($trainers as $trainer): ?>
                                <option value="<?php echo $trainer->id; ?>" <?php selected($trainer_filter, $trainer->id); ?>>
                                    <?php echo esc_html($trainer->display_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="ptp-filter-group">
                        <label>Date From</label>
                        <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>">
                    </div>

                    <div class="ptp-filter-group">
                        <label>Date To</label>
                        <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>">
                    </div>

                    <button type="submit" class="button">Filter</button>
                    <a href="<?php echo admin_url('admin.php?page=ptp-training-sessions'); ?>" class="button">Reset</a>
                </form>
            </div>

            <!-- Sessions Table -->
            <?php if ($sessions): ?>
            <table class="ptp-admin-table widefat">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Parent/Customer</th>
                        <th>Trainer</th>
                        <th>Player</th>
                        <th>Date/Time</th>
                        <th>Price</th>
                        <th>Session Status</th>
                        <th>Payment Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sessions as $session): ?>
                    <tr data-session-id="<?php echo $session->id; ?>">
                        <td>#<?php echo $session->id; ?></td>
                        <td>
                            <strong><?php echo esc_html($session->customer_name); ?></strong><br>
                            <small><?php echo esc_html($session->customer_email); ?></small>
                        </td>
                        <td><?php echo esc_html($session->trainer_name); ?></td>
                        <td>
                            <?php echo esc_html($session->player_name ?: 'N/A'); ?>
                            <?php if ($session->player_age): ?>
                                <br><small>Age <?php echo esc_html($session->player_age); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($session->session_date !== '0000-00-00'): ?>
                                <?php echo date('M j, Y', strtotime($session->session_date)); ?><br>
                                <small><?php echo date('g:i A', strtotime($session->start_time)); ?></small>
                            <?php else: ?>
                                <em>Not scheduled</em>
                            <?php endif; ?>
                        </td>
                        <td>$<?php echo number_format($session->price, 2); ?></td>
                        <td>
                            <select class="ptp-session-status-select" data-session-id="<?php echo $session->id; ?>">
                                <option value="requested" <?php selected($session->session_status, 'requested'); ?>>Requested</option>
                                <option value="confirmed" <?php selected($session->session_status, 'confirmed'); ?>>Confirmed</option>
                                <option value="scheduled" <?php selected($session->session_status, 'scheduled'); ?>>Scheduled</option>
                                <option value="completed" <?php selected($session->session_status, 'completed'); ?>>Completed</option>
                                <option value="cancelled" <?php selected($session->session_status, 'cancelled'); ?>>Cancelled</option>
                                <option value="no_show" <?php selected($session->session_status, 'no_show'); ?>>No Show</option>
                            </select>
                        </td>
                        <td>
                            <span class="ptp-status ptp-status--payment-<?php echo esc_attr($session->payment_status); ?>">
                                <?php echo esc_html(ucfirst($session->payment_status)); ?>
                            </span>
                        </td>
                        <td>
                            <a href="#" class="ptp-view-session" data-session-id="<?php echo $session->id; ?>">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="ptp-empty">No sessions found matching your criteria.</p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render Trainers Page
     */
    public function render_trainers_page() {
        $trainers = PTP_Database::get_trainers(array('status' => 'approved', 'limit' => 100));
        ?>
        <div class="wrap ptp-admin">
            <h1 class="ptp-admin-title">Trainers</h1>

            <?php if ($trainers): ?>
            <table class="ptp-admin-table widefat">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>Location</th>
                        <th>Rate</th>
                        <th>Rating</th>
                        <th>Sessions</th>
                        <th>Stripe</th>
                        <th>Featured</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trainers as $trainer): ?>
                    <tr>
                        <td>
                            <?php if ($trainer->profile_photo): ?>
                                <img src="<?php echo esc_url($trainer->profile_photo); ?>" alt="" class="ptp-trainer-thumb">
                            <?php else: ?>
                                <div class="ptp-trainer-thumb ptp-trainer-thumb--placeholder">
                                    <?php echo esc_html(strtoupper(substr($trainer->display_name, 0, 1))); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo esc_html($trainer->display_name); ?></strong><br>
                            <small><?php echo esc_html($trainer->tagline ?: ''); ?></small>
                        </td>
                        <td>
                            <?php echo esc_html($trainer->primary_location_city . ', ' . $trainer->primary_location_state); ?>
                        </td>
                        <td>$<?php echo number_format($trainer->hourly_rate, 0); ?>/hr</td>
                        <td>
                            <?php if ($trainer->avg_rating > 0): ?>
                                <span class="ptp-rating">
                                    <?php echo number_format($trainer->avg_rating, 1); ?>
                                    <small>(<?php echo $trainer->total_reviews; ?>)</small>
                                </span>
                            <?php else: ?>
                                <em>No reviews</em>
                            <?php endif; ?>
                        </td>
                        <td><?php echo number_format($trainer->total_sessions); ?></td>
                        <td>
                            <?php if ($trainer->stripe_onboarding_complete): ?>
                                <span class="ptp-status ptp-status--confirmed">Connected</span>
                            <?php elseif ($trainer->stripe_account_id): ?>
                                <span class="ptp-status ptp-status--pending">Pending</span>
                            <?php else: ?>
                                <span class="ptp-status ptp-status--unpaid">Not Set</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($trainer->is_featured): ?>
                                <span class="ptp-badge ptp-badge--featured">Featured</span>
                            <?php else: ?>
                                <button class="button button-small ptp-feature-trainer" data-trainer-id="<?php echo $trainer->id; ?>">Feature</button>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo home_url('/trainer/' . $trainer->slug . '/'); ?>" target="_blank">View</a> |
                            <a href="<?php echo admin_url('admin.php?page=ptp-training-sessions&trainer=' . $trainer->id); ?>">Sessions</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="ptp-empty">No approved trainers yet.</p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render Applications Page
     */
    public function render_applications_page() {
        global $wpdb;
        $applications = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}ptp_applications ORDER BY status = 'pending' DESC, created_at DESC LIMIT 50"
        );
        ?>
        <div class="wrap ptp-admin">
            <h1 class="ptp-admin-title">Trainer Applications</h1>

            <?php if ($applications): ?>
            <div class="ptp-applications-grid">
                <?php foreach ($applications as $app): ?>
                <div class="ptp-application-card ptp-application-card--<?php echo esc_attr($app->status); ?>">
                    <div class="ptp-application-header">
                        <h3><?php echo esc_html($app->first_name . ' ' . $app->last_name); ?></h3>
                        <span class="ptp-status ptp-status--<?php echo esc_attr($app->status); ?>">
                            <?php echo esc_html(ucfirst($app->status)); ?>
                        </span>
                    </div>
                    <div class="ptp-application-body">
                        <p><strong>Email:</strong> <?php echo esc_html($app->email); ?></p>
                        <p><strong>Phone:</strong> <?php echo esc_html($app->phone); ?></p>
                        <p><strong>Location:</strong> <?php echo esc_html($app->location_city . ', ' . $app->location_state); ?></p>
                        <?php if ($app->experience_summary): ?>
                        <p><strong>Experience:</strong><br><?php echo esc_html(wp_trim_words($app->experience_summary, 30)); ?></p>
                        <?php endif; ?>
                        <?php if ($app->intro_video_url): ?>
                        <p><a href="<?php echo esc_url($app->intro_video_url); ?>" target="_blank">View Intro Video</a></p>
                        <?php endif; ?>
                        <p class="ptp-application-date">Applied <?php echo date('M j, Y', strtotime($app->created_at)); ?></p>
                    </div>
                    <?php if ($app->status === 'pending'): ?>
                    <div class="ptp-application-actions">
                        <button class="button button-primary ptp-approve-application" data-app-id="<?php echo $app->id; ?>">
                            Approve
                        </button>
                        <button class="button ptp-reject-application" data-app-id="<?php echo $app->id; ?>">
                            Reject
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="ptp-empty">No applications yet.</p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render Payouts Page
     */
    public function render_payouts_page() {
        global $wpdb;
        $payouts = $wpdb->get_results(
            "SELECT p.*, t.display_name as trainer_name
             FROM {$wpdb->prefix}ptp_payouts p
             LEFT JOIN {$wpdb->prefix}ptp_trainers t ON p.trainer_id = t.id
             ORDER BY p.status = 'pending' DESC, p.created_at DESC
             LIMIT 100"
        );
        ?>
        <div class="wrap ptp-admin">
            <h1 class="ptp-admin-title">Trainer Payouts</h1>

            <?php if ($payouts): ?>
            <table class="ptp-admin-table widefat">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Trainer</th>
                        <th>Gross</th>
                        <th>Platform Fee</th>
                        <th>Trainer Payout</th>
                        <th>Status</th>
                        <th>Paid At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payouts as $payout): ?>
                    <tr>
                        <td>#<?php echo $payout->id; ?></td>
                        <td><?php echo esc_html($payout->trainer_name); ?></td>
                        <td>$<?php echo number_format($payout->gross_amount, 2); ?></td>
                        <td>$<?php echo number_format($payout->platform_fee, 2); ?></td>
                        <td><strong>$<?php echo number_format($payout->trainer_payout, 2); ?></strong></td>
                        <td>
                            <span class="ptp-status ptp-status--<?php echo $payout->status === 'paid' ? 'confirmed' : 'pending'; ?>">
                                <?php echo esc_html(ucfirst($payout->status)); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo $payout->paid_at ? date('M j, Y', strtotime($payout->paid_at)) : '-'; ?>
                        </td>
                        <td>
                            <?php if ($payout->status === 'pending'): ?>
                                <button class="button button-primary button-small ptp-process-payout" data-payout-id="<?php echo $payout->id; ?>">
                                    Process
                                </button>
                            <?php elseif ($payout->stripe_transfer_id): ?>
                                <small><?php echo esc_html($payout->stripe_transfer_id); ?></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="ptp-empty">No payouts yet.</p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render Settings Page
     */
    public function render_settings_page() {
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        ?>
        <div class="wrap ptp-admin">
            <h1 class="ptp-admin-title">PTP Training Settings</h1>

            <nav class="ptp-settings-tabs">
                <a href="?page=ptp-training-settings&tab=general" class="<?php echo $current_tab === 'general' ? 'active' : ''; ?>">General</a>
                <a href="?page=ptp-training-settings&tab=stripe" class="<?php echo $current_tab === 'stripe' ? 'active' : ''; ?>">Stripe</a>
                <a href="?page=ptp-training-settings&tab=notifications" class="<?php echo $current_tab === 'notifications' ? 'active' : ''; ?>">Notifications</a>
                <a href="?page=ptp-training-settings&tab=integrations" class="<?php echo $current_tab === 'integrations' ? 'active' : ''; ?>">Integrations</a>
            </nav>

            <form method="post" action="options.php" class="ptp-settings-form">
                <?php settings_fields('ptp_training_settings'); ?>

                <?php if ($current_tab === 'general'): ?>
                    <div class="ptp-settings-section">
                        <h2>Platform Settings</h2>

                        <div class="ptp-form-row">
                            <label for="ptp_platform_fee_percent">Platform Fee (%)</label>
                            <input type="number" id="ptp_platform_fee_percent" name="ptp_platform_fee_percent"
                                   value="<?php echo esc_attr(get_option('ptp_platform_fee_percent', 20)); ?>"
                                   min="0" max="50" step="0.5">
                            <p class="description">Percentage of each transaction kept as platform fee (default: 20%)</p>
                        </div>

                        <div class="ptp-form-row">
                            <label for="ptp_admin_email">Admin Email</label>
                            <input type="email" id="ptp_admin_email" name="ptp_admin_email"
                                   value="<?php echo esc_attr(get_option('ptp_admin_email', get_option('admin_email'))); ?>">
                            <p class="description">Primary email for admin notifications</p>
                        </div>

                        <div class="ptp-form-row">
                            <label for="ptp_auto_confirm_on_payment">
                                <input type="checkbox" id="ptp_auto_confirm_on_payment" name="ptp_auto_confirm_on_payment"
                                       value="1" <?php checked(get_option('ptp_auto_confirm_on_payment'), 1); ?>>
                                Auto-confirm sessions when payment succeeds
                            </label>
                        </div>
                    </div>

                <?php elseif ($current_tab === 'stripe'): ?>
                    <div class="ptp-settings-section">
                        <h2>Stripe Configuration</h2>
                        <p class="ptp-settings-note">Configure your Stripe API keys. Get these from your <a href="https://dashboard.stripe.com/apikeys" target="_blank">Stripe Dashboard</a>.</p>

                        <div class="ptp-form-row">
                            <label for="ptp_stripe_publishable_key">Publishable Key</label>
                            <input type="text" id="ptp_stripe_publishable_key" name="ptp_stripe_publishable_key"
                                   value="<?php echo esc_attr(get_option('ptp_stripe_publishable_key')); ?>"
                                   placeholder="pk_live_..." class="widefat">
                        </div>

                        <div class="ptp-form-row">
                            <label for="ptp_stripe_secret_key">Secret Key</label>
                            <input type="password" id="ptp_stripe_secret_key" name="ptp_stripe_secret_key"
                                   value="<?php echo esc_attr(get_option('ptp_stripe_secret_key')); ?>"
                                   placeholder="sk_live_..." class="widefat">
                        </div>

                        <div class="ptp-form-row">
                            <label for="ptp_stripe_webhook_secret">Webhook Secret</label>
                            <input type="password" id="ptp_stripe_webhook_secret" name="ptp_stripe_webhook_secret"
                                   value="<?php echo esc_attr(get_option('ptp_stripe_webhook_secret')); ?>"
                                   placeholder="whsec_..." class="widefat">
                            <p class="description">
                                Webhook URL: <code><?php echo rest_url('ptp-training/v1/stripe-webhook'); ?></code><br>
                                Configure this in your Stripe Dashboard under Developers > Webhooks.
                            </p>
                        </div>
                    </div>

                <?php elseif ($current_tab === 'notifications'): ?>
                    <div class="ptp-settings-section">
                        <h2>Email Notifications</h2>

                        <div class="ptp-form-row">
                            <label for="ptp_notification_admin_email">Notification Admin Email</label>
                            <input type="email" id="ptp_notification_admin_email" name="ptp_notification_admin_email"
                                   value="<?php echo esc_attr(get_option('ptp_notification_admin_email')); ?>"
                                   placeholder="<?php echo esc_attr(get_option('admin_email')); ?>">
                        </div>

                        <div class="ptp-form-row">
                            <label for="ptp_notification_from_name">From Name</label>
                            <input type="text" id="ptp_notification_from_name" name="ptp_notification_from_name"
                                   value="<?php echo esc_attr(get_option('ptp_notification_from_name', 'Players Teaching Players')); ?>">
                        </div>

                        <div class="ptp-form-row">
                            <label for="ptp_notification_from_email">From Email</label>
                            <input type="email" id="ptp_notification_from_email" name="ptp_notification_from_email"
                                   value="<?php echo esc_attr(get_option('ptp_notification_from_email')); ?>"
                                   placeholder="<?php echo esc_attr(get_option('admin_email')); ?>">
                        </div>

                        <h3>Notification Toggles</h3>
                        <p class="ptp-settings-note">Enable or disable specific notification emails.</p>

                        <table class="ptp-toggle-table">
                            <thead>
                                <tr>
                                    <th>Notification</th>
                                    <th>Enabled</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Send booking request email to parent</td>
                                    <td><input type="checkbox" name="ptp_notify_parent_booking_request" value="1" <?php checked(get_option('ptp_notify_parent_booking_request', 1), 1); ?>></td>
                                </tr>
                                <tr>
                                    <td>Send booking request email to trainer</td>
                                    <td><input type="checkbox" name="ptp_notify_trainer_booking_request" value="1" <?php checked(get_option('ptp_notify_trainer_booking_request', 1), 1); ?>></td>
                                </tr>
                                <tr>
                                    <td>Send booking request email to admin</td>
                                    <td><input type="checkbox" name="ptp_notify_admin_booking_request" value="1" <?php checked(get_option('ptp_notify_admin_booking_request', 1), 1); ?>></td>
                                </tr>
                                <tr>
                                    <td>Send session confirmed email to parent</td>
                                    <td><input type="checkbox" name="ptp_notify_parent_session_confirmed" value="1" <?php checked(get_option('ptp_notify_parent_session_confirmed', 1), 1); ?>></td>
                                </tr>
                                <tr>
                                    <td>Send session confirmed email to trainer</td>
                                    <td><input type="checkbox" name="ptp_notify_trainer_session_confirmed" value="1" <?php checked(get_option('ptp_notify_trainer_session_confirmed', 1), 1); ?>></td>
                                </tr>
                                <tr>
                                    <td>Send cancellation email to parent</td>
                                    <td><input type="checkbox" name="ptp_notify_parent_session_cancelled" value="1" <?php checked(get_option('ptp_notify_parent_session_cancelled', 1), 1); ?>></td>
                                </tr>
                                <tr>
                                    <td>Send cancellation email to trainer</td>
                                    <td><input type="checkbox" name="ptp_notify_trainer_session_cancelled" value="1" <?php checked(get_option('ptp_notify_trainer_session_cancelled', 1), 1); ?>></td>
                                </tr>
                                <tr>
                                    <td>Send completion/feedback email to parent</td>
                                    <td><input type="checkbox" name="ptp_notify_parent_session_completed" value="1" <?php checked(get_option('ptp_notify_parent_session_completed', 1), 1); ?>></td>
                                </tr>
                                <tr>
                                    <td>Send payment receipt to parent</td>
                                    <td><input type="checkbox" name="ptp_notify_parent_payment_success" value="1" <?php checked(get_option('ptp_notify_parent_payment_success', 1), 1); ?>></td>
                                </tr>
                                <tr>
                                    <td>Send payment notification to admin</td>
                                    <td><input type="checkbox" name="ptp_notify_admin_payment_success" value="1" <?php checked(get_option('ptp_notify_admin_payment_success', 1), 1); ?>></td>
                                </tr>
                            </tbody>
                        </table>

                        <h3>Email Templates</h3>

                        <div class="ptp-form-row">
                            <label for="ptp_email_booking_request_subject">Booking Request - Subject</label>
                            <input type="text" id="ptp_email_booking_request_subject" name="ptp_email_booking_request_subject"
                                   value="<?php echo esc_attr(get_option('ptp_email_booking_request_subject', 'We received your PTP private training request')); ?>" class="widefat">
                        </div>

                        <div class="ptp-form-row">
                            <label for="ptp_email_booking_request_intro">Booking Request - Intro Text</label>
                            <textarea id="ptp_email_booking_request_intro" name="ptp_email_booking_request_intro" class="widefat" rows="2"><?php echo esc_textarea(get_option('ptp_email_booking_request_intro', 'Thank you for submitting your training request!')); ?></textarea>
                        </div>

                        <div class="ptp-form-row">
                            <label for="ptp_email_session_confirmed_subject">Session Confirmed - Subject</label>
                            <input type="text" id="ptp_email_session_confirmed_subject" name="ptp_email_session_confirmed_subject"
                                   value="<?php echo esc_attr(get_option('ptp_email_session_confirmed_subject', 'Your training session is confirmed!')); ?>" class="widefat">
                        </div>

                        <div class="ptp-form-row">
                            <label for="ptp_email_session_confirmed_intro">Session Confirmed - Intro Text</label>
                            <textarea id="ptp_email_session_confirmed_intro" name="ptp_email_session_confirmed_intro" class="widefat" rows="2"><?php echo esc_textarea(get_option('ptp_email_session_confirmed_intro', 'Great news! Your training session has been confirmed.')); ?></textarea>
                        </div>
                    </div>

                <?php elseif ($current_tab === 'integrations'): ?>
                    <div class="ptp-settings-section">
                        <h2>Google Maps</h2>

                        <div class="ptp-form-row">
                            <label for="ptp_google_maps_key">Google Maps API Key</label>
                            <input type="text" id="ptp_google_maps_key" name="ptp_google_maps_key"
                                   value="<?php echo esc_attr(get_option('ptp_google_maps_key')); ?>" class="widefat">
                        </div>
                    </div>

                    <div class="ptp-settings-section">
                        <h2>Google Calendar OAuth</h2>

                        <div class="ptp-form-row">
                            <label for="ptp_google_client_id">Google Client ID</label>
                            <input type="text" id="ptp_google_client_id" name="ptp_google_client_id"
                                   value="<?php echo esc_attr(get_option('ptp_google_client_id')); ?>" class="widefat">
                        </div>

                        <div class="ptp-form-row">
                            <label for="ptp_google_client_secret">Google Client Secret</label>
                            <input type="password" id="ptp_google_client_secret" name="ptp_google_client_secret"
                                   value="<?php echo esc_attr(get_option('ptp_google_client_secret')); ?>" class="widefat">
                        </div>
                    </div>

                    <div class="ptp-settings-section">
                        <h2>Twilio SMS</h2>

                        <div class="ptp-form-row">
                            <label for="ptp_sms_enabled">
                                <input type="checkbox" id="ptp_sms_enabled" name="ptp_sms_enabled"
                                       value="1" <?php checked(get_option('ptp_sms_enabled'), 1); ?>>
                                Enable SMS Notifications
                            </label>
                        </div>

                        <div class="ptp-form-row">
                            <label for="ptp_twilio_sid">Twilio Account SID</label>
                            <input type="text" id="ptp_twilio_sid" name="ptp_twilio_sid"
                                   value="<?php echo esc_attr(get_option('ptp_twilio_sid')); ?>" class="widefat">
                        </div>

                        <div class="ptp-form-row">
                            <label for="ptp_twilio_token">Twilio Auth Token</label>
                            <input type="password" id="ptp_twilio_token" name="ptp_twilio_token"
                                   value="<?php echo esc_attr(get_option('ptp_twilio_token')); ?>" class="widefat">
                        </div>

                        <div class="ptp-form-row">
                            <label for="ptp_twilio_phone">Twilio Phone Number</label>
                            <input type="text" id="ptp_twilio_phone" name="ptp_twilio_phone"
                                   value="<?php echo esc_attr(get_option('ptp_twilio_phone')); ?>"
                                   placeholder="+15551234567">
                        </div>
                    </div>

                <?php endif; ?>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    // ==========================================
    // AJAX HANDLERS
    // ==========================================

    /**
     * Update session status via AJAX
     */
    public function ajax_update_session_status() {
        check_ajax_referer('ptp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $session_id = intval($_POST['session_id']);
        $new_status = sanitize_text_field($_POST['status']);

        $valid_statuses = array('requested', 'confirmed', 'scheduled', 'completed', 'cancelled', 'no_show');
        if (!in_array($new_status, $valid_statuses)) {
            wp_send_json_error(array('message' => 'Invalid status'));
        }

        $result = PTP_Database::update_session_status($session_id, $new_status);

        if ($result !== false) {
            wp_send_json_success(array('message' => 'Status updated'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update status'));
        }
    }

    /**
     * Approve trainer application via AJAX
     */
    public function ajax_approve_trainer() {
        check_ajax_referer('ptp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        global $wpdb;
        $app_id = intval($_POST['app_id']);

        $app = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ptp_applications WHERE id = %d",
            $app_id
        ));

        if (!$app) {
            wp_send_json_error(array('message' => 'Application not found'));
        }

        // Create user account
        $user_id = wp_create_user($app->email, wp_generate_password(), $app->email);

        if (is_wp_error($user_id)) {
            // User might already exist
            $user = get_user_by('email', $app->email);
            if ($user) {
                $user_id = $user->ID;
            } else {
                wp_send_json_error(array('message' => $user_id->get_error_message()));
            }
        }

        // Set role
        $user = new WP_User($user_id);
        $user->set_role('ptp_trainer');
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $app->first_name,
            'last_name' => $app->last_name,
            'display_name' => $app->first_name . ' ' . $app->last_name
        ));

        // Create trainer profile
        $slug = sanitize_title($app->first_name . '-' . $app->last_name);
        $wpdb->insert(
            "{$wpdb->prefix}ptp_trainers",
            array(
                'user_id' => $user_id,
                'status' => 'approved',
                'display_name' => $app->first_name . ' ' . $app->last_name,
                'slug' => $slug,
                'bio' => $app->experience_summary,
                'primary_location_city' => $app->location_city,
                'primary_location_state' => $app->location_state,
                'intro_video_url' => $app->intro_video_url,
                'hourly_rate' => 75
            )
        );

        // Update application status
        $wpdb->update(
            "{$wpdb->prefix}ptp_applications",
            array(
                'status' => 'approved',
                'reviewed_by' => get_current_user_id(),
                'reviewed_at' => current_time('mysql')
            ),
            array('id' => $app_id)
        );

        // Send approval email
        $login_url = wp_login_url(home_url('/trainer-dashboard/'));
        $subject = 'Welcome to PTP! Your trainer application is approved';
        $message = "Hi {$app->first_name},\n\n";
        $message .= "Great news! Your application to become a PTP trainer has been approved.\n\n";
        $message .= "Here's how to get started:\n";
        $message .= "1. Log in to your account: {$login_url}\n";
        $message .= "2. Complete your trainer profile\n";
        $message .= "3. Set up your availability\n";
        $message .= "4. Connect your Stripe account to receive payments\n\n";
        $message .= "Welcome to the team!\nThe PTP Team";

        wp_mail($app->email, $subject, $message);

        wp_send_json_success(array('message' => 'Trainer approved and account created'));
    }

    /**
     * Reject trainer application via AJAX
     */
    public function ajax_reject_trainer() {
        check_ajax_referer('ptp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        global $wpdb;
        $app_id = intval($_POST['app_id']);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');

        $app = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ptp_applications WHERE id = %d",
            $app_id
        ));

        if (!$app) {
            wp_send_json_error(array('message' => 'Application not found'));
        }

        $wpdb->update(
            "{$wpdb->prefix}ptp_applications",
            array(
                'status' => 'rejected',
                'admin_notes' => $reason,
                'reviewed_by' => get_current_user_id(),
                'reviewed_at' => current_time('mysql')
            ),
            array('id' => $app_id)
        );

        // Send rejection email
        $subject = 'Update on your PTP trainer application';
        $message = "Hi {$app->first_name},\n\n";
        $message .= "Thank you for your interest in becoming a PTP trainer.\n\n";
        $message .= "After careful review, we've decided not to move forward with your application at this time.\n";
        if ($reason) {
            $message .= "\nFeedback: {$reason}\n";
        }
        $message .= "\nWe encourage you to reapply in the future if circumstances change.\n\n";
        $message .= "Best regards,\nThe PTP Team";

        wp_mail($app->email, $subject, $message);

        wp_send_json_success(array('message' => 'Application rejected'));
    }

    /**
     * Process payout via AJAX
     */
    public function ajax_process_payout() {
        check_ajax_referer('ptp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $payout_id = intval($_POST['payout_id']);

        $result = PTP_Stripe::process_payout($payout_id);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => 'Payout processed', 'transfer_id' => $result));
    }
}

PTP_Admin::instance();
