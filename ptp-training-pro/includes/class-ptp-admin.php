<?php
/**
 * Admin Dashboard & Settings
 * Manage trainers, applications, payouts, and settings
 */

if (!defined('ABSPATH')) exit;

class PTP_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_ptp_approve_trainer', array($this, 'approve_trainer'));
        add_action('wp_ajax_ptp_reject_trainer', array($this, 'reject_trainer'));
        add_action('wp_ajax_ptp_process_payout', array($this, 'process_payout'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'PTP Training',
            'PTP Training',
            'manage_options',
            'ptp-training',
            array($this, 'render_dashboard'),
            'dashicons-groups',
            30
        );
        
        add_submenu_page(
            'ptp-training',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'ptp-training',
            array($this, 'render_dashboard')
        );
        
        add_submenu_page(
            'ptp-training',
            'Trainers',
            'Trainers',
            'manage_options',
            'ptp-training-trainers',
            array($this, 'render_trainers')
        );
        
        add_submenu_page(
            'ptp-training',
            'Applications',
            'Applications',
            'manage_options',
            'ptp-training-applications',
            array($this, 'render_applications')
        );
        
        add_submenu_page(
            'ptp-training',
            'Sessions',
            'Sessions',
            'manage_options',
            'ptp-training-sessions',
            array($this, 'render_sessions')
        );
        
        add_submenu_page(
            'ptp-training',
            'Payouts',
            'Payouts',
            'manage_options',
            'ptp-training-payouts',
            array($this, 'render_payouts')
        );
        
        add_submenu_page(
            'ptp-training',
            'Settings',
            'Settings',
            'manage_options',
            'ptp-training-settings',
            array($this, 'render_settings')
        );
    }
    
    public function register_settings() {
        // Platform settings
        register_setting('ptp_training_settings', 'ptp_platform_fee_percent', array(
            'type' => 'number',
            'default' => 25,
            'sanitize_callback' => 'absint'
        ));
        register_setting('ptp_training_settings', 'ptp_trainer_referral_commission', array(
            'type' => 'number',
            'default' => 10,
            'sanitize_callback' => 'absint'
        ));
        register_setting('ptp_training_settings', 'ptp_admin_email');
        
        // Stripe settings
        register_setting('ptp_training_settings', 'ptp_stripe_publishable_key');
        register_setting('ptp_training_settings', 'ptp_stripe_secret_key');
        register_setting('ptp_training_settings', 'ptp_stripe_webhook_secret');
        
        // Google settings
        register_setting('ptp_training_settings', 'ptp_google_maps_key');
        register_setting('ptp_training_settings', 'ptp_google_client_id');
        register_setting('ptp_training_settings', 'ptp_google_client_secret');
        register_setting('ptp_training_settings', 'ptp_google_review_url');
        
        // Twilio SMS settings
        register_setting('ptp_training_settings', 'ptp_twilio_sid');
        register_setting('ptp_training_settings', 'ptp_twilio_token');
        register_setting('ptp_training_settings', 'ptp_twilio_phone');
        register_setting('ptp_training_settings', 'ptp_sms_enabled', array(
            'type' => 'boolean',
            'default' => false
        ));
        register_setting('ptp_training_settings', 'ptp_sms_24hr_reminder', array(
            'type' => 'boolean',
            'default' => true
        ));
        register_setting('ptp_training_settings', 'ptp_sms_2hr_reminder', array(
            'type' => 'boolean',
            'default' => true
        ));
        register_setting('ptp_training_settings', 'ptp_sms_post_checkin', array(
            'type' => 'boolean',
            'default' => true
        ));
        register_setting('ptp_training_settings', 'ptp_sms_review_request', array(
            'type' => 'boolean',
            'default' => true
        ));
    }
    
    public function render_dashboard() {
        $stats = PTP_Database::get_admin_stats();
        ?>
        <div class="wrap ptp-admin">
            <h1>PTP Training Dashboard</h1>
            
            <div class="ptp-admin-stats">
                <div class="ptp-admin-stat">
                    <span class="ptp-admin-stat-value"><?php echo $stats['total_trainers']; ?></span>
                    <span class="ptp-admin-stat-label">Active Trainers</span>
                </div>
                <div class="ptp-admin-stat">
                    <span class="ptp-admin-stat-value"><?php echo $stats['pending_applications']; ?></span>
                    <span class="ptp-admin-stat-label">Pending Applications</span>
                </div>
                <div class="ptp-admin-stat">
                    <span class="ptp-admin-stat-value"><?php echo $stats['total_sessions']; ?></span>
                    <span class="ptp-admin-stat-label">Total Sessions</span>
                </div>
                <div class="ptp-admin-stat">
                    <span class="ptp-admin-stat-value">$<?php echo number_format($stats['total_revenue'], 0); ?></span>
                    <span class="ptp-admin-stat-label">Total Revenue</span>
                </div>
                <div class="ptp-admin-stat">
                    <span class="ptp-admin-stat-value">$<?php echo number_format($stats['platform_revenue'], 0); ?></span>
                    <span class="ptp-admin-stat-label">Platform Revenue</span>
                </div>
                <div class="ptp-admin-stat">
                    <span class="ptp-admin-stat-value"><?php echo $stats['active_packs']; ?></span>
                    <span class="ptp-admin-stat-label">Active Packs</span>
                </div>
            </div>
            
            <?php if ($stats['pending_applications'] > 0): ?>
                <div class="ptp-admin-notice">
                    <p><strong><?php echo $stats['pending_applications']; ?> trainer applications</strong> are waiting for review.</p>
                    <a href="<?php echo admin_url('admin.php?page=ptp-training-applications'); ?>" class="button button-primary">Review Applications</a>
                </div>
            <?php endif; ?>
            
            <h2>Quick Links</h2>
            <div class="ptp-admin-links">
                <a href="<?php echo admin_url('admin.php?page=ptp-training-trainers'); ?>" class="ptp-admin-link">
                    <span class="dashicons dashicons-admin-users"></span>
                    Manage Trainers
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-training-sessions'); ?>" class="ptp-admin-link">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    View Sessions
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-training-payouts'); ?>" class="ptp-admin-link">
                    <span class="dashicons dashicons-money-alt"></span>
                    Process Payouts
                </a>
                <a href="<?php echo admin_url('admin.php?page=ptp-training-settings'); ?>" class="ptp-admin-link">
                    <span class="dashicons dashicons-admin-settings"></span>
                    Settings
                </a>
            </div>
        </div>
        
        <style>
            .ptp-admin-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; margin: 24px 0; }
            .ptp-admin-stat { background: #fff; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
            .ptp-admin-stat-value { display: block; font-size: 32px; font-weight: 700; color: #1d2327; }
            .ptp-admin-stat-label { font-size: 13px; color: #646970; }
            .ptp-admin-notice { background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px 16px; margin: 24px 0; border-radius: 4px; }
            .ptp-admin-notice p { margin: 0 0 12px; }
            .ptp-admin-links { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 16px; }
            .ptp-admin-link { display: flex; align-items: center; gap: 12px; background: #fff; padding: 16px; border-radius: 8px; text-decoration: none; color: #1d2327; box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: all 0.2s; }
            .ptp-admin-link:hover { box-shadow: 0 4px 6px rgba(0,0,0,0.1); transform: translateY(-2px); }
            .ptp-admin-link .dashicons { font-size: 24px; width: 24px; height: 24px; color: #FCB900; }
        </style>
        <?php
    }
    
    public function render_trainers() {
        global $wpdb;
        
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'approved';
        
        $trainers = $wpdb->get_results($wpdb->prepare(
            "SELECT t.*, u.user_email FROM {$wpdb->prefix}ptp_trainers t
             LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID
             WHERE t.status = %s
             ORDER BY t.created_at DESC",
            $status
        ));
        ?>
        <div class="wrap">
            <h1>Trainers</h1>
            
            <ul class="subsubsub">
                <li><a href="?page=ptp-training-trainers&status=approved" <?php echo $status === 'approved' ? 'class="current"' : ''; ?>>Approved</a> |</li>
                <li><a href="?page=ptp-training-trainers&status=pending" <?php echo $status === 'pending' ? 'class="current"' : ''; ?>>Pending</a> |</li>
                <li><a href="?page=ptp-training-trainers&status=suspended" <?php echo $status === 'suspended' ? 'class="current"' : ''; ?>>Suspended</a></li>
            </ul>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Location</th>
                        <th>Rate</th>
                        <th>Sessions</th>
                        <th>Rating</th>
                        <th>Stripe</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trainers as $trainer): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($trainer->display_name); ?></strong>
                                <br><small><?php echo esc_html($trainer->slug); ?></small>
                            </td>
                            <td><?php echo esc_html($trainer->user_email); ?></td>
                            <td><?php echo esc_html($trainer->primary_location_city . ', ' . $trainer->primary_location_state); ?></td>
                            <td>$<?php echo number_format($trainer->hourly_rate, 0); ?>/hr</td>
                            <td><?php echo $trainer->total_sessions; ?></td>
                            <td>
                                <?php if ($trainer->total_reviews > 0): ?>
                                    ★ <?php echo number_format($trainer->avg_rating, 1); ?> (<?php echo $trainer->total_reviews; ?>)
                                <?php else: ?>
                                    <span style="color:#999">No reviews</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($trainer->stripe_onboarding_complete): ?>
                                    <span style="color:#10B981">✓ Connected</span>
                                <?php else: ?>
                                    <span style="color:#EF4444">Not connected</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo home_url('/trainer/' . $trainer->slug); ?>" target="_blank">View</a>
                                <?php if ($status === 'approved'): ?>
                                    | <a href="#" class="suspend-trainer" data-id="<?php echo $trainer->id; ?>">Suspend</a>
                                <?php elseif ($status === 'suspended'): ?>
                                    | <a href="#" class="activate-trainer" data-id="<?php echo $trainer->id; ?>">Activate</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($trainers)): ?>
                        <tr><td colspan="8">No trainers found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    public function render_applications() {
        global $wpdb;
        
        $applications = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}ptp_applications WHERE status = 'pending' ORDER BY created_at DESC"
        );
        ?>
        <div class="wrap">
            <h1>Trainer Applications</h1>
            
            <?php if (empty($applications)): ?>
                <p>No pending applications.</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Location</th>
                            <th>Experience</th>
                            <th>Applied</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($app->first_name . ' ' . $app->last_name); ?></strong>
                                    <?php if ($app->instagram_handle): ?>
                                        <br><small>@<?php echo esc_html($app->instagram_handle); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo esc_html($app->email); ?>
                                    <?php if ($app->phone): ?>
                                        <br><small><?php echo esc_html($app->phone); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($app->location_city . ', ' . $app->location_state); ?></td>
                                <td>
                                    <details>
                                        <summary>View Details</summary>
                                        <p><strong>Playing:</strong> <?php echo esc_html($app->playing_background); ?></p>
                                        <p><strong>Coaching:</strong> <?php echo esc_html($app->coaching_experience); ?></p>
                                        <p><strong>Why Join:</strong> <?php echo esc_html($app->why_join); ?></p>
                                        <?php if ($app->intro_video_url): ?>
                                            <p><a href="<?php echo esc_url($app->intro_video_url); ?>" target="_blank">Watch Video</a></p>
                                        <?php endif; ?>
                                    </details>
                                </td>
                                <td><?php echo human_time_diff(strtotime($app->created_at), current_time('timestamp')); ?> ago</td>
                                <td>
                                    <button class="button button-primary approve-app" data-id="<?php echo $app->id; ?>">Approve</button>
                                    <button class="button reject-app" data-id="<?php echo $app->id; ?>">Reject</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(function($) {
            $('.approve-app').click(function() {
                var id = $(this).data('id');
                if (!confirm('Approve this application?')) return;
                
                $.post(ajaxurl, { action: 'ptp_approve_trainer', id: id }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data || 'Error approving application');
                    }
                });
            });
            
            $('.reject-app').click(function() {
                var id = $(this).data('id');
                var reason = prompt('Rejection reason (optional):');
                if (reason === null) return;
                
                $.post(ajaxurl, { action: 'ptp_reject_trainer', id: id, reason: reason }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data || 'Error rejecting application');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    public function render_sessions() {
        global $wpdb;
        
        $sessions = $wpdb->get_results(
            "SELECT s.*, t.display_name as trainer_name, p.athlete_name, u.user_email as customer_email
             FROM {$wpdb->prefix}ptp_sessions s
             LEFT JOIN {$wpdb->prefix}ptp_trainers t ON s.trainer_id = t.id
             LEFT JOIN {$wpdb->prefix}ptp_lesson_packs p ON s.pack_id = p.id
             LEFT JOIN {$wpdb->users} u ON s.customer_id = u.ID
             WHERE s.session_date >= CURDATE() OR s.status IN ('scheduled', 'unscheduled')
             ORDER BY s.session_date ASC, s.start_time ASC
             LIMIT 100"
        );
        ?>
        <div class="wrap">
            <h1>Sessions</h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Trainer</th>
                        <th>Athlete</th>
                        <th>Customer</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sessions as $session): ?>
                        <tr>
                            <td>
                                <?php echo $session->session_date !== '0000-00-00' 
                                    ? date('M j, Y', strtotime($session->session_date)) 
                                    : '<em>Not scheduled</em>'; ?>
                            </td>
                            <td>
                                <?php echo $session->start_time !== '00:00:00'
                                    ? date('g:i A', strtotime($session->start_time))
                                    : '-'; ?>
                            </td>
                            <td><?php echo esc_html($session->trainer_name); ?></td>
                            <td><?php echo esc_html($session->athlete_name); ?></td>
                            <td><?php echo esc_html($session->customer_email); ?></td>
                            <td>
                                <span class="ptp-status ptp-status-<?php echo $session->status; ?>">
                                    <?php echo ucfirst($session->status); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <style>
            .ptp-status { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; }
            .ptp-status-scheduled { background: #dbeafe; color: #1e40af; }
            .ptp-status-completed { background: #d1fae5; color: #065f46; }
            .ptp-status-cancelled { background: #fee2e2; color: #991b1b; }
            .ptp-status-unscheduled { background: #f3f4f6; color: #4b5563; }
        </style>
        <?php
    }
    
    public function render_payouts() {
        global $wpdb;
        
        $pending = $wpdb->get_results(
            "SELECT p.*, t.display_name as trainer_name, t.stripe_account_id
             FROM {$wpdb->prefix}ptp_payouts p
             LEFT JOIN {$wpdb->prefix}ptp_trainers t ON p.trainer_id = t.id
             WHERE p.status = 'pending'
             ORDER BY p.created_at ASC"
        );
        ?>
        <div class="wrap">
            <h1>Pending Payouts</h1>
            
            <?php if (empty($pending)): ?>
                <p>No pending payouts.</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Trainer</th>
                            <th>Session</th>
                            <th>Gross</th>
                            <th>Platform Fee</th>
                            <th>Trainer Payout</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending as $payout): ?>
                            <tr>
                                <td><?php echo esc_html($payout->trainer_name); ?></td>
                                <td>#<?php echo $payout->session_id; ?></td>
                                <td>$<?php echo number_format($payout->gross_amount, 2); ?></td>
                                <td>$<?php echo number_format($payout->platform_fee, 2); ?></td>
                                <td><strong>$<?php echo number_format($payout->trainer_payout, 2); ?></strong></td>
                                <td><?php echo date('M j, Y', strtotime($payout->created_at)); ?></td>
                                <td>
                                    <?php if ($payout->stripe_account_id): ?>
                                        <button class="button process-payout" data-id="<?php echo $payout->id; ?>">Process</button>
                                    <?php else: ?>
                                        <span style="color:#999">No Stripe</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(function($) {
            $('.process-payout').click(function() {
                var id = $(this).data('id');
                if (!confirm('Process this payout?')) return;
                
                $(this).prop('disabled', true).text('Processing...');
                
                $.post(ajaxurl, { action: 'ptp_process_payout', id: id }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data || 'Error processing payout');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    public function render_settings() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'platform';
        ?>
        <div class="wrap">
            <h1>PTP Training Settings</h1>
            
            <nav class="nav-tab-wrapper">
                <a href="?page=ptp-training-settings&tab=platform" class="nav-tab <?php echo $active_tab === 'platform' ? 'nav-tab-active' : ''; ?>">Platform</a>
                <a href="?page=ptp-training-settings&tab=stripe" class="nav-tab <?php echo $active_tab === 'stripe' ? 'nav-tab-active' : ''; ?>">Stripe</a>
                <a href="?page=ptp-training-settings&tab=google" class="nav-tab <?php echo $active_tab === 'google' ? 'nav-tab-active' : ''; ?>">Google</a>
                <a href="?page=ptp-training-settings&tab=sms" class="nav-tab <?php echo $active_tab === 'sms' ? 'nav-tab-active' : ''; ?>">SMS / Twilio</a>
                <a href="?page=ptp-training-settings&tab=referrals" class="nav-tab <?php echo $active_tab === 'referrals' ? 'nav-tab-active' : ''; ?>">Referrals</a>
            </nav>
            
            <form method="post" action="options.php">
                <?php settings_fields('ptp_training_settings'); ?>
                
                <?php if ($active_tab === 'platform'): ?>
                <!-- Platform Settings -->
                <h2>Platform Settings</h2>
                <p>Configure core platform settings and commission rates.</p>
                
                <table class="form-table">
                    <tr>
                        <th>Admin Email</th>
                        <td>
                            <input type="email" name="ptp_admin_email" value="<?php echo esc_attr(get_option('ptp_admin_email', get_option('admin_email'))); ?>" class="regular-text" />
                            <p class="description">Email for notifications and alerts</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Platform Fee %</th>
                        <td>
                            <input type="number" name="ptp_platform_fee_percent" value="<?php echo esc_attr(get_option('ptp_platform_fee_percent', 25)); ?>" min="0" max="50" step="1" style="width:80px" /> %
                            <p class="description">Percentage PTP keeps from each booking (trainers receive the rest)</p>
                            <p class="description"><strong>Example:</strong> At 25%, a $100 session = $75 to trainer, $25 to PTP</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Trainer Referral Commission %</th>
                        <td>
                            <input type="number" name="ptp_trainer_referral_commission" value="<?php echo esc_attr(get_option('ptp_trainer_referral_commission', 10)); ?>" min="0" max="25" step="1" style="width:80px" /> %
                            <p class="description">Commission trainers earn when their referral code is used for camps/clinics</p>
                        </td>
                    </tr>
                </table>
                
                <?php elseif ($active_tab === 'stripe'): ?>
                <!-- Stripe Settings -->
                <h2>Stripe Connect Settings</h2>
                <p>Configure Stripe for payment processing and trainer payouts.</p>
                
                <table class="form-table">
                    <tr>
                        <th>Publishable Key</th>
                        <td>
                            <input type="text" name="ptp_stripe_publishable_key" value="<?php echo esc_attr(get_option('ptp_stripe_publishable_key')); ?>" class="regular-text" />
                            <p class="description">Starts with <code>pk_live_</code> or <code>pk_test_</code></p>
                        </td>
                    </tr>
                    <tr>
                        <th>Secret Key</th>
                        <td>
                            <input type="password" name="ptp_stripe_secret_key" value="<?php echo esc_attr(get_option('ptp_stripe_secret_key')); ?>" class="regular-text" />
                            <p class="description">Starts with <code>sk_live_</code> or <code>sk_test_</code></p>
                        </td>
                    </tr>
                    <tr>
                        <th>Webhook Secret</th>
                        <td>
                            <input type="password" name="ptp_stripe_webhook_secret" value="<?php echo esc_attr(get_option('ptp_stripe_webhook_secret')); ?>" class="regular-text" />
                            <p class="description">Starts with <code>whsec_</code> — get this from Stripe Dashboard → Webhooks</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Webhook URL</th>
                        <td>
                            <code><?php echo esc_html(rest_url('ptp-training/v1/stripe-webhook')); ?></code>
                            <p class="description">Add this URL to your Stripe webhook endpoints</p>
                        </td>
                    </tr>
                </table>
                
                <?php elseif ($active_tab === 'google'): ?>
                <!-- Google Settings -->
                <h2>Google API Settings</h2>
                <p>Configure Google Maps and Calendar integration.</p>
                
                <table class="form-table">
                    <tr>
                        <th>Maps API Key</th>
                        <td>
                            <input type="text" name="ptp_google_maps_key" value="<?php echo esc_attr(get_option('ptp_google_maps_key')); ?>" class="regular-text" />
                            <p class="description">Enable Maps JavaScript API and Geocoding API in Google Cloud Console</p>
                        </td>
                    </tr>
                    <tr>
                        <th>OAuth Client ID</th>
                        <td>
                            <input type="text" name="ptp_google_client_id" value="<?php echo esc_attr(get_option('ptp_google_client_id')); ?>" class="regular-text" />
                            <p class="description">For Google Calendar integration</p>
                        </td>
                    </tr>
                    <tr>
                        <th>OAuth Client Secret</th>
                        <td>
                            <input type="password" name="ptp_google_client_secret" value="<?php echo esc_attr(get_option('ptp_google_client_secret')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th>Google Review URL</th>
                        <td>
                            <input type="url" name="ptp_google_review_url" value="<?php echo esc_attr(get_option('ptp_google_review_url')); ?>" class="regular-text" placeholder="https://g.page/r/..." />
                            <p class="description">Direct link to leave a Google review (from Google Business Profile)</p>
                        </td>
                    </tr>
                </table>
                
                <?php elseif ($active_tab === 'sms'): ?>
                <!-- SMS / Twilio Settings -->
                <h2>SMS & Twilio Settings</h2>
                <p>Configure automated SMS notifications for session reminders and follow-ups.</p>
                
                <?php
                $twilio_configured = !empty(get_option('ptp_twilio_sid')) && !empty(get_option('ptp_twilio_token')) && !empty(get_option('ptp_twilio_phone'));
                ?>
                
                <?php if (!$twilio_configured): ?>
                <div class="notice notice-warning" style="margin: 15px 0;">
                    <p><strong>Twilio not configured.</strong> Enter your credentials below to enable SMS features.</p>
                </div>
                <?php else: ?>
                <div class="notice notice-success" style="margin: 15px 0;">
                    <p><strong>✓ Twilio configured.</strong> SMS features are active.</p>
                </div>
                <?php endif; ?>
                
                <h3>Twilio Credentials</h3>
                <table class="form-table">
                    <tr>
                        <th>Account SID</th>
                        <td>
                            <input type="text" name="ptp_twilio_sid" value="<?php echo esc_attr(get_option('ptp_twilio_sid')); ?>" class="regular-text" placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" />
                        </td>
                    </tr>
                    <tr>
                        <th>Auth Token</th>
                        <td>
                            <input type="password" name="ptp_twilio_token" value="<?php echo esc_attr(get_option('ptp_twilio_token')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th>Phone Number</th>
                        <td>
                            <input type="text" name="ptp_twilio_phone" value="<?php echo esc_attr(get_option('ptp_twilio_phone')); ?>" class="regular-text" placeholder="+1234567890" />
                            <p class="description">Your Twilio phone number in E.164 format</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Inbound Webhook URL</th>
                        <td>
                            <code><?php echo esc_html(rest_url('ptp-training/v1/twilio-webhook')); ?></code>
                            <p class="description">Add this URL to your Twilio phone number's webhook settings</p>
                        </td>
                    </tr>
                </table>
                
                <h3>Automated SMS Flows</h3>
                <table class="form-table">
                    <tr>
                        <th>Enable SMS</th>
                        <td>
                            <label>
                                <input type="checkbox" name="ptp_sms_enabled" value="1" <?php checked(get_option('ptp_sms_enabled', false)); ?> />
                                Enable all SMS notifications
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th>24-Hour Reminder</th>
                        <td>
                            <label>
                                <input type="checkbox" name="ptp_sms_24hr_reminder" value="1" <?php checked(get_option('ptp_sms_24hr_reminder', true)); ?> />
                                Send reminder 24 hours before session
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th>2-Hour Reminder</th>
                        <td>
                            <label>
                                <input type="checkbox" name="ptp_sms_2hr_reminder" value="1" <?php checked(get_option('ptp_sms_2hr_reminder', true)); ?> />
                                Send final reminder 2 hours before session (to parent AND trainer)
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th>Post-Training Check-in</th>
                        <td>
                            <label>
                                <input type="checkbox" name="ptp_sms_post_checkin" value="1" <?php checked(get_option('ptp_sms_post_checkin', true)); ?> />
                                Send feedback request 1 hour after session completes
                            </label>
                            <p class="description">Parents can reply GREAT, GOOD, or OK. "OK" responses trigger admin alert.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Google Review Request</th>
                        <td>
                            <label>
                                <input type="checkbox" name="ptp_sms_review_request" value="1" <?php checked(get_option('ptp_sms_review_request', true)); ?> />
                                Request Google review 24 hours after session
                            </label>
                            <p class="description">Requires Google Review URL to be set above</p>
                        </td>
                    </tr>
                </table>
                
                <h3>SMS Flow Timeline</h3>
                <div style="background:#f9f9f9; padding:20px; border-radius:8px; margin:20px 0;">
                    <ol style="margin:0; padding-left:20px;">
                        <li><strong>Booking confirmed</strong> → Immediate confirmation SMS to parent + trainer notification</li>
                        <li><strong>Session scheduled</strong> → Immediate confirmation with date/time/location</li>
                        <li><strong>24 hours before</strong> → Reminder to parent</li>
                        <li><strong>2 hours before</strong> → Final reminder to parent AND trainer</li>
                        <li><strong>1 hour after</strong> → "How was it?" check-in (GREAT/GOOD/OK responses)</li>
                        <li><strong>24 hours after</strong> → Google Review request</li>
                    </ol>
                </div>
                
                <?php elseif ($active_tab === 'referrals'): ?>
                <!-- Referral Settings -->
                <h2>Trainer Referral Program</h2>
                <p>Trainers can earn commission by referring families to PTP camps and clinics.</p>
                
                <h3>How It Works</h3>
                <div style="background:#f9f9f9; padding:20px; border-radius:8px; margin:20px 0;">
                    <ol style="margin:0; padding-left:20px;">
                        <li>Each trainer gets a unique referral code (e.g., <code>TRAINER-JOHN</code>)</li>
                        <li>Trainer shares their code with training families</li>
                        <li>Families use the code when registering for camps/clinics</li>
                        <li>Trainer earns <?php echo esc_html(get_option('ptp_trainer_referral_commission', 10)); ?>% commission on the registration</li>
                        <li>Commission is tracked and paid out monthly</li>
                    </ol>
                </div>
                
                <h3>Camp/Clinic Integration</h3>
                <table class="form-table">
                    <tr>
                        <th>Summer Camp URL</th>
                        <td>
                            <input type="url" name="ptp_camp_summer_url" value="<?php echo esc_attr(get_option('ptp_camp_summer_url', 'https://ptpsummercamps.com/product-category/summer-camps/')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th>Winter Camp URL</th>
                        <td>
                            <input type="url" name="ptp_camp_winter_url" value="<?php echo esc_attr(get_option('ptp_camp_winter_url')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th>Clinics URL</th>
                        <td>
                            <input type="url" name="ptp_clinics_url" value="<?php echo esc_attr(get_option('ptp_clinics_url')); ?>" class="regular-text" />
                        </td>
                    </tr>
                </table>
                
                <h3>Referral Stats</h3>
                <?php
                global $wpdb;
                $referral_stats = $wpdb->get_row(
                    "SELECT COUNT(*) as total_referrals, SUM(conversions) as total_conversions, SUM(revenue) as total_revenue, SUM(commission_earned) as total_commission 
                     FROM {$wpdb->prefix}ptp_trainer_referrals"
                );
                ?>
                <table class="form-table">
                    <tr>
                        <th>Total Referral Links</th>
                        <td><?php echo intval($referral_stats->total_referrals ?? 0); ?></td>
                    </tr>
                    <tr>
                        <th>Total Conversions</th>
                        <td><?php echo intval($referral_stats->total_conversions ?? 0); ?></td>
                    </tr>
                    <tr>
                        <th>Total Revenue</th>
                        <td>$<?php echo number_format($referral_stats->total_revenue ?? 0, 2); ?></td>
                    </tr>
                    <tr>
                        <th>Total Commission Paid</th>
                        <td>$<?php echo number_format($referral_stats->total_commission ?? 0, 2); ?></td>
                    </tr>
                </table>
                
                <?php endif; ?>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function approve_trainer() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $app_id = intval($_POST['id']);
        
        global $wpdb;
        $app = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ptp_applications WHERE id = %d",
            $app_id
        ));
        
        if (!$app) {
            wp_send_json_error('Application not found');
        }
        
        // Create WordPress user
        $username = sanitize_user(strtolower($app->first_name . '.' . $app->last_name));
        $password = wp_generate_password(12);
        
        $user_id = wp_create_user($username, $password, $app->email);
        
        if (is_wp_error($user_id)) {
            // User might already exist
            $user = get_user_by('email', $app->email);
            if ($user) {
                $user_id = $user->ID;
            } else {
                wp_send_json_error($user_id->get_error_message());
            }
        }
        
        // Add trainer role
        $user = new WP_User($user_id);
        $user->add_role('ptp_trainer');
        
        // Create trainer profile
        $slug = sanitize_title($app->first_name . '-' . $app->last_name);
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}ptp_trainers WHERE slug = %s",
            $slug
        ));
        if ($existing) {
            $slug .= '-' . $user_id;
        }
        
        $wpdb->insert("{$wpdb->prefix}ptp_trainers", array(
            'user_id' => $user_id,
            'status' => 'approved',
            'display_name' => $app->first_name . ' ' . $app->last_name,
            'slug' => $slug,
            'bio' => $app->experience_summary,
            'credentials' => $app->playing_background,
            'primary_location_city' => $app->location_city,
            'primary_location_state' => $app->location_state,
            'primary_location_zip' => $app->location_zip,
            'intro_video_url' => $app->intro_video_url,
            'hourly_rate' => 75 // Default rate
        ));
        
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
        
        // Send welcome email
        $subject = 'Welcome to PTP Training!';
        $message = "Hi {$app->first_name},\n\n";
        $message .= "Great news! Your trainer application has been approved.\n\n";
        $message .= "Your login credentials:\n";
        $message .= "Username: {$username}\n";
        $message .= "Password: {$password}\n";
        $message .= "Dashboard: " . home_url('/trainer-dashboard/') . "\n\n";
        $message .= "Next steps:\n";
        $message .= "1. Log in and complete your profile\n";
        $message .= "2. Connect your Stripe account for payments\n";
        $message .= "3. Set your availability\n";
        $message .= "4. Start accepting bookings!\n\n";
        $message .= "Welcome to the team!\nThe PTP Team";
        
        wp_mail($app->email, $subject, $message);
        
        wp_send_json_success();
    }
    
    public function reject_trainer() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $app_id = intval($_POST['id']);
        $reason = sanitize_textarea_field($_POST['reason']);
        
        global $wpdb;
        $app = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ptp_applications WHERE id = %d",
            $app_id
        ));
        
        if (!$app) {
            wp_send_json_error('Application not found');
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
        $subject = 'PTP Training Application Update';
        $message = "Hi {$app->first_name},\n\n";
        $message .= "Thank you for your interest in becoming a PTP trainer.\n\n";
        $message .= "After reviewing your application, we've decided not to move forward at this time.\n\n";
        if ($reason) {
            $message .= "Feedback: {$reason}\n\n";
        }
        $message .= "You're welcome to apply again in the future.\n\n";
        $message .= "Best,\nThe PTP Team";
        
        wp_mail($app->email, $subject, $message);
        
        wp_send_json_success();
    }
    
    public function process_payout() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $payout_id = intval($_POST['id']);
        
        $result = PTP_Stripe::process_payout($payout_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success();
    }
}

new PTP_Admin();
