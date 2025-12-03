<?php
/**
 * My Training - Customer Portal
 * View booked sessions, schedule, leave reviews
 */

if (!defined('ABSPATH')) exit;

if (!is_user_logged_in()) {
    return;
}

$user_id = get_current_user_id();
$packs = PTP_Database::get_customer_packs($user_id);
$sessions = PTP_Database::get_customer_sessions($user_id);

// Group sessions by status
$upcoming = array_filter($sessions, function($s) {
    return $s->status === 'scheduled' && strtotime($s->session_date) >= strtotime('today');
});
$past = array_filter($sessions, function($s) {
    return $s->status === 'completed' || (strtotime($s->session_date) < strtotime('today') && $s->status !== 'cancelled');
});
$unscheduled = array_filter($sessions, function($s) {
    return $s->status === 'unscheduled';
});

// Check for booking success
$booking_success = isset($_GET['booking']) && $_GET['booking'] === 'success';
?>

<div class="ptp-my-training" id="myTraining">
    
    <?php if ($booking_success): ?>
        <div class="ptp-success-banner">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <div>
                <strong>Booking Confirmed!</strong>
                <p>Your training sessions have been purchased. Schedule your first session below.</p>
            </div>
            <button type="button" class="ptp-banner-close" onclick="this.parentElement.remove()">×</button>
        </div>
    <?php endif; ?>
    
    <header class="ptp-mt-header">
        <h1>My Training</h1>
        <a href="<?php echo home_url('/private-training/'); ?>" class="ptp-btn ptp-btn-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Book More Sessions
        </a>
    </header>
    
    <!-- Quick Stats -->
    <div class="ptp-mt-stats">
        <div class="ptp-mt-stat">
            <span class="ptp-mt-stat-value"><?php echo count($upcoming); ?></span>
            <span class="ptp-mt-stat-label">Upcoming</span>
        </div>
        <div class="ptp-mt-stat">
            <span class="ptp-mt-stat-value"><?php echo count($unscheduled); ?></span>
            <span class="ptp-mt-stat-label">To Schedule</span>
        </div>
        <div class="ptp-mt-stat">
            <span class="ptp-mt-stat-value"><?php echo count($past); ?></span>
            <span class="ptp-mt-stat-label">Completed</span>
        </div>
    </div>
    
    <!-- Tabs -->
    <div class="ptp-mt-tabs">
        <button type="button" class="ptp-mt-tab active" data-tab="upcoming">Upcoming</button>
        <button type="button" class="ptp-mt-tab" data-tab="schedule">Schedule Sessions</button>
        <button type="button" class="ptp-mt-tab" data-tab="past">Past Sessions</button>
        <button type="button" class="ptp-mt-tab" data-tab="packs">My Packs</button>
    </div>
    
    <!-- Upcoming Sessions -->
    <div class="ptp-mt-panel active" id="panel-upcoming">
        <?php if (empty($upcoming)): ?>
            <div class="ptp-mt-empty">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <h3>No Upcoming Sessions</h3>
                <p>Schedule your purchased sessions or book new ones.</p>
                <?php if (!empty($unscheduled)): ?>
                    <button type="button" class="ptp-btn ptp-btn-primary" onclick="document.querySelector('[data-tab=schedule]').click()">
                        Schedule Now
                    </button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="ptp-session-list">
                <?php foreach ($upcoming as $session): ?>
                    <div class="ptp-session-card">
                        <div class="ptp-session-date">
                            <span class="ptp-session-day"><?php echo date('d', strtotime($session->session_date)); ?></span>
                            <span class="ptp-session-month"><?php echo date('M', strtotime($session->session_date)); ?></span>
                        </div>
                        <div class="ptp-session-info">
                            <div class="ptp-session-time">
                                <?php echo date('l, F j', strtotime($session->session_date)); ?> at <?php echo date('g:i A', strtotime($session->start_time)); ?>
                            </div>
                            <div class="ptp-session-trainer">
                                <img src="<?php echo esc_url($session->trainer_photo ?: PTP_TRAINING_URL . 'assets/images/default-avatar.svg'); ?>" alt="" />
                                <span>with <?php echo esc_html($session->trainer_name); ?></span>
                            </div>
                            <div class="ptp-session-athlete">
                                Athlete: <?php echo esc_html($session->athlete_name); ?>
                            </div>
                        </div>
                        <div class="ptp-session-actions">
                            <button type="button" class="ptp-btn ptp-btn-outline ptp-btn-sm" onclick="cancelSession(<?php echo $session->id; ?>)">
                                Cancel
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Schedule Sessions -->
    <div class="ptp-mt-panel" id="panel-schedule">
        <?php if (empty($unscheduled)): ?>
            <div class="ptp-mt-empty">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="1.5">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <h3>All Sessions Scheduled!</h3>
                <p>All your purchased sessions have been scheduled.</p>
            </div>
        <?php else: ?>
            <p class="ptp-mt-panel-intro">You have <?php echo count($unscheduled); ?> session(s) ready to schedule.</p>
            
            <div class="ptp-unscheduled-list">
                <?php 
                $grouped = array();
                foreach ($unscheduled as $session) {
                    $key = $session->trainer_id . '-' . $session->pack_id;
                    if (!isset($grouped[$key])) {
                        $grouped[$key] = array(
                            'trainer' => $session,
                            'sessions' => array()
                        );
                    }
                    $grouped[$key]['sessions'][] = $session;
                }
                
                foreach ($grouped as $group): 
                    $trainer = $group['trainer'];
                ?>
                    <div class="ptp-schedule-group">
                        <div class="ptp-schedule-trainer">
                            <img src="<?php echo esc_url($trainer->trainer_photo ?: PTP_TRAINING_URL . 'assets/images/default-avatar.svg'); ?>" alt="" />
                            <div>
                                <strong><?php echo esc_html($trainer->trainer_name); ?></strong>
                                <span><?php echo count($group['sessions']); ?> session(s) to schedule</span>
                            </div>
                        </div>
                        
                        <?php foreach ($group['sessions'] as $index => $session): ?>
                            <div class="ptp-schedule-session" data-session-id="<?php echo $session->id; ?>">
                                <span class="ptp-schedule-label">Session <?php echo $index + 1; ?></span>
                                <button type="button" class="ptp-btn ptp-btn-primary ptp-btn-sm schedule-btn" 
                                        data-session="<?php echo $session->id; ?>" 
                                        data-trainer="<?php echo $session->trainer_id; ?>">
                                    Select Date & Time
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Past Sessions -->
    <div class="ptp-mt-panel" id="panel-past">
        <?php if (empty($past)): ?>
            <div class="ptp-mt-empty">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
                <h3>No Past Sessions</h3>
                <p>Your completed sessions will appear here.</p>
            </div>
        <?php else: ?>
            <div class="ptp-session-list">
                <?php foreach ($past as $session): ?>
                    <div class="ptp-session-card ptp-session-past">
                        <div class="ptp-session-date">
                            <span class="ptp-session-day"><?php echo date('d', strtotime($session->session_date)); ?></span>
                            <span class="ptp-session-month"><?php echo date('M', strtotime($session->session_date)); ?></span>
                        </div>
                        <div class="ptp-session-info">
                            <div class="ptp-session-time">
                                <?php echo date('l, F j', strtotime($session->session_date)); ?>
                            </div>
                            <div class="ptp-session-trainer">
                                <img src="<?php echo esc_url($session->trainer_photo ?: PTP_TRAINING_URL . 'assets/images/default-avatar.svg'); ?>" alt="" />
                                <span>with <?php echo esc_html($session->trainer_name); ?></span>
                            </div>
                            <?php if ($session->trainer_notes): ?>
                                <div class="ptp-session-notes">
                                    <strong>Notes:</strong> <?php echo esc_html($session->trainer_notes); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($session->homework): ?>
                                <div class="ptp-session-homework">
                                    <strong>Homework:</strong> <?php echo esc_html($session->homework); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="ptp-session-actions">
                            <button type="button" class="ptp-btn ptp-btn-outline ptp-btn-sm" 
                                    onclick="openReviewModal(<?php echo $session->trainer_id; ?>, <?php echo $session->id; ?>)">
                                Leave Review
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- My Packs -->
    <div class="ptp-mt-panel" id="panel-packs">
        <?php if (empty($packs)): ?>
            <div class="ptp-mt-empty">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                </svg>
                <h3>No Training Packs</h3>
                <p>Purchase a training pack to get started.</p>
                <a href="<?php echo home_url('/private-training/'); ?>" class="ptp-btn ptp-btn-primary">
                    Find a Trainer
                </a>
            </div>
        <?php else: ?>
            <div class="ptp-packs-grid">
                <?php foreach ($packs as $pack): ?>
                    <div class="ptp-pack-card">
                        <div class="ptp-pack-header">
                            <img src="<?php echo esc_url($pack->trainer_photo ?: PTP_TRAINING_URL . 'assets/images/default-avatar.svg'); ?>" alt="" />
                            <div>
                                <a href="<?php echo home_url('/trainer/' . $pack->trainer_slug); ?>" class="ptp-pack-trainer">
                                    <?php echo esc_html($pack->trainer_name); ?>
                                </a>
                                <span class="ptp-pack-athlete">For: <?php echo esc_html($pack->athlete_name); ?></span>
                            </div>
                            <span class="ptp-pack-status ptp-pack-status-<?php echo $pack->status; ?>">
                                <?php echo ucfirst($pack->status); ?>
                            </span>
                        </div>
                        
                        <div class="ptp-pack-progress">
                            <div class="ptp-pack-progress-bar">
                                <div class="ptp-pack-progress-fill" style="width: <?php echo ($pack->sessions_used / $pack->total_sessions) * 100; ?>%"></div>
                            </div>
                            <div class="ptp-pack-progress-text">
                                <span><?php echo $pack->sessions_used; ?> of <?php echo $pack->total_sessions; ?> sessions used</span>
                                <span><?php echo $pack->sessions_remaining; ?> remaining</span>
                            </div>
                        </div>
                        
                        <div class="ptp-pack-details">
                            <div class="ptp-pack-detail">
                                <span>Purchased</span>
                                <strong><?php echo date('M j, Y', strtotime($pack->created_at)); ?></strong>
                            </div>
                            <div class="ptp-pack-detail">
                                <span>Expires</span>
                                <strong><?php echo date('M j, Y', strtotime($pack->expires_at)); ?></strong>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
</div>

<!-- Schedule Modal -->
<div class="ptp-modal" id="scheduleModal" style="display: none;">
    <div class="ptp-modal-backdrop"></div>
    <div class="ptp-modal-content">
        <div class="ptp-modal-header">
            <h3>Schedule Session</h3>
            <button type="button" class="ptp-modal-close">&times;</button>
        </div>
        <div class="ptp-modal-body">
            <div id="scheduleCalendar"></div>
            <div id="scheduleSlots" style="display: none;"></div>
        </div>
        <div class="ptp-modal-footer">
            <button type="button" class="ptp-btn ptp-btn-outline" onclick="closeModal('scheduleModal')">Cancel</button>
            <button type="button" class="ptp-btn ptp-btn-primary" id="confirmSchedule" disabled>Confirm</button>
        </div>
    </div>
</div>

<!-- Review Modal -->
<div class="ptp-modal" id="reviewModal" style="display: none;">
    <div class="ptp-modal-backdrop"></div>
    <div class="ptp-modal-content">
        <div class="ptp-modal-header">
            <h3>Leave a Review</h3>
            <button type="button" class="ptp-modal-close">&times;</button>
        </div>
        <div class="ptp-modal-body">
            <form id="reviewForm">
                <input type="hidden" name="trainer_id" id="reviewTrainerId" />
                <input type="hidden" name="session_id" id="reviewSessionId" />
                
                <div class="ptp-rating-select">
                    <label>Rating</label>
                    <div class="ptp-stars-select" id="ratingStars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button type="button" class="ptp-star-btn" data-rating="<?php echo $i; ?>">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="#E5E7EB">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                </svg>
                            </button>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="rating" id="ratingValue" required />
                </div>
                
                <div class="ptp-form-group">
                    <label>Your Experience Level</label>
                    <select name="experience">
                        <option value="">Select level</option>
                        <option value="Beginner Parent">Beginner Parent</option>
                        <option value="Experienced Sports Parent">Experienced Sports Parent</option>
                        <option value="Former Player">Former Player</option>
                    </select>
                </div>
                
                <div class="ptp-form-group">
                    <label>Your Review</label>
                    <textarea name="review" rows="4" placeholder="How was your experience? What improved?"></textarea>
                </div>
            </form>
        </div>
        <div class="ptp-modal-footer">
            <button type="button" class="ptp-btn ptp-btn-outline" onclick="closeModal('reviewModal')">Cancel</button>
            <button type="button" class="ptp-btn ptp-btn-primary" id="submitReview">Submit Review</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tabs
    document.querySelectorAll('.ptp-mt-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.ptp-mt-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.ptp-mt-panel').forEach(p => p.classList.remove('active'));
            
            this.classList.add('active');
            document.getElementById('panel-' + this.dataset.tab).classList.add('active');
        });
    });
    
    // Schedule buttons
    document.querySelectorAll('.schedule-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            openScheduleModal(this.dataset.session, this.dataset.trainer);
        });
    });
    
    // Rating stars
    document.querySelectorAll('.ptp-star-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const rating = this.dataset.rating;
            document.getElementById('ratingValue').value = rating;
            
            document.querySelectorAll('.ptp-star-btn').forEach((star, index) => {
                star.classList.toggle('active', index < rating);
            });
        });
    });
    
    // Modal close
    document.querySelectorAll('.ptp-modal-backdrop, .ptp-modal-close').forEach(el => {
        el.addEventListener('click', function() {
            this.closest('.ptp-modal').style.display = 'none';
        });
    });
});

function openScheduleModal(sessionId, trainerId) {
    document.getElementById('scheduleModal').style.display = 'flex';
    // Load calendar for trainer
    // Implementation would go here
}

function openReviewModal(trainerId, sessionId) {
    document.getElementById('reviewTrainerId').value = trainerId;
    document.getElementById('reviewSessionId').value = sessionId;
    document.getElementById('reviewModal').style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

async function cancelSession(sessionId) {
    if (!confirm('Are you sure you want to cancel this session?')) return;
    
    try {
        const response = await fetch(ptpTraining.rest_url + 'sessions/' + sessionId + '/cancel', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': ptpTraining.nonce
            }
        });
        
        const result = await response.json();
        if (result.success) {
            location.reload();
        } else {
            alert(result.message || 'Failed to cancel session');
        }
    } catch (error) {
        alert('Error cancelling session');
    }
}
</script>
