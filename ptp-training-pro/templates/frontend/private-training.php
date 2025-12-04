<?php
/**
 * Private Training Landing Page
 * PTP-branded, mobile-first design with hero, trainer listing, and booking flow
 * Shortcode: [ptp_private_training]
 */

if (!defined('ABSPATH')) exit;

// Get trainers for display
$trainers = PTP_Database::get_trainers(array(
    'status' => 'approved',
    'limit' => 12
));

// Get filter options
global $wpdb;
$states = $wpdb->get_col(
    "SELECT DISTINCT primary_location_state FROM {$wpdb->prefix}ptp_trainers
     WHERE status = 'approved' AND primary_location_state != ''
     ORDER BY primary_location_state"
);
?>

<div class="ptp-private-training">

    <!-- Hero Section -->
    <section class="ptp-hero">
        <div class="ptp-hero__overlay"></div>
        <div class="ptp-hero__content">
            <span class="ptp-hero__tag">Private Training by Players Teaching Players</span>
            <h1 class="ptp-hero__title">1:1 &amp; Small Group Training With NCAA Mentors</h1>
            <p class="ptp-hero__subtitle">
                Train with current and former college athletes who know what it takes to reach the next level.
                Build confidence, refine your skills, and get mentorship from players who've been in your shoes.
            </p>
            <div class="ptp-hero__cta">
                <a href="#trainers" class="ptp-btn ptp-btn-primary ptp-btn-lg">Browse Trainers</a>
                <a href="<?php echo home_url('/become-a-trainer/'); ?>" class="ptp-btn ptp-btn-outline ptp-btn-lg ptp-btn-white-outline">Become a Trainer</a>
            </div>
        </div>
        <div class="ptp-hero__trust">
            <div class="ptp-hero__trust-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <span>Vetted Trainers</span>
            </div>
            <div class="ptp-hero__trust-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span>Secure Payments</span>
            </div>
            <div class="ptp-hero__trust-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <span>Train Anywhere</span>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="ptp-how-it-works">
        <div class="ptp-container">
            <h2 class="ptp-section-title">How It Works</h2>
            <p class="ptp-section-subtitle">Getting started is easy. Book your first session in minutes.</p>

            <div class="ptp-steps">
                <div class="ptp-step">
                    <div class="ptp-step__icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg>
                    </div>
                    <div class="ptp-step__number">1</div>
                    <h3 class="ptp-step__title">Choose a Trainer</h3>
                    <p class="ptp-step__desc">Browse profiles, watch intro videos, and read reviews to find the perfect match for your player.</p>
                </div>

                <div class="ptp-step">
                    <div class="ptp-step__icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                            <line x1="16" x2="16" y1="2" y2="6"/>
                            <line x1="8" x2="8" y1="2" y2="6"/>
                            <line x1="3" x2="21" y1="10" y2="10"/>
                        </svg>
                    </div>
                    <div class="ptp-step__number">2</div>
                    <h3 class="ptp-step__title">Pick Time &amp; Location</h3>
                    <p class="ptp-step__desc">Select a date, time, and training location that works for you. Flexibility is key.</p>
                </div>

                <div class="ptp-step">
                    <div class="ptp-step__icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect width="20" height="14" x="2" y="5" rx="2"/>
                            <line x1="2" x2="22" y1="10" y2="10"/>
                        </svg>
                    </div>
                    <div class="ptp-step__number">3</div>
                    <h3 class="ptp-step__title">Pay Securely</h3>
                    <p class="ptp-step__desc">Complete your booking with secure online payment. Save with multi-session packs.</p>
                </div>

                <div class="ptp-step">
                    <div class="ptp-step__icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polygon points="10 8 16 12 10 16 10 8"/>
                        </svg>
                    </div>
                    <div class="ptp-step__number">4</div>
                    <h3 class="ptp-step__title">Train &amp; Get Feedback</h3>
                    <p class="ptp-step__desc">Meet your trainer, work on your game, and receive personalized feedback and homework.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Trainers Section -->
    <section class="ptp-trainers-section" id="trainers">
        <div class="ptp-container">
            <h2 class="ptp-section-title">Meet Our Trainers</h2>
            <p class="ptp-section-subtitle">Current and former college athletes ready to help your player level up.</p>

            <!-- Quick Filters -->
            <div class="ptp-quick-filters">
                <select id="ptpStateFilter" class="ptp-filter-select">
                    <option value="">All States</option>
                    <?php foreach ($states as $state): ?>
                        <option value="<?php echo esc_attr($state); ?>"><?php echo esc_html($state); ?></option>
                    <?php endforeach; ?>
                </select>
                <a href="<?php echo home_url('/private-training/'); ?>" class="ptp-btn ptp-btn-outline ptp-btn-sm">
                    View All Trainers
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                    </svg>
                </a>
            </div>

            <!-- Trainer Grid -->
            <div class="ptp-trainer-grid" id="ptpTrainerGrid">
                <?php if ($trainers): ?>
                    <?php foreach ($trainers as $trainer): ?>
                        <article class="ptp-trainer-card" data-state="<?php echo esc_attr($trainer->primary_location_state); ?>">
                            <a href="<?php echo home_url('/trainer/' . $trainer->slug . '/'); ?>" class="ptp-trainer-card__link">
                                <div class="ptp-trainer-card__media">
                                    <?php if ($trainer->profile_photo): ?>
                                        <img src="<?php echo esc_url($trainer->profile_photo); ?>" alt="<?php echo esc_attr($trainer->display_name); ?>" class="ptp-trainer-card__image" loading="lazy">
                                    <?php else: ?>
                                        <div class="ptp-trainer-card__placeholder">
                                            <?php echo esc_html(strtoupper(substr($trainer->display_name, 0, 1))); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($trainer->intro_video_url): ?>
                                        <span class="ptp-trainer-card__video-badge">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                            Video
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($trainer->is_featured): ?>
                                        <span class="ptp-trainer-card__featured">Featured</span>
                                    <?php endif; ?>
                                </div>
                                <div class="ptp-trainer-card__body">
                                    <div class="ptp-trainer-card__header">
                                        <h3 class="ptp-trainer-card__name"><?php echo esc_html($trainer->display_name); ?></h3>
                                        <?php if ($trainer->avg_rating > 0): ?>
                                            <div class="ptp-trainer-card__rating">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="#FCB900"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                                <span><?php echo number_format($trainer->avg_rating, 1); ?></span>
                                                <span class="ptp-trainer-card__reviews">(<?php echo $trainer->total_reviews; ?>)</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($trainer->tagline): ?>
                                        <p class="ptp-trainer-card__tagline"><?php echo esc_html($trainer->tagline); ?></p>
                                    <?php endif; ?>
                                    <div class="ptp-trainer-card__location">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                        <span><?php echo esc_html($trainer->primary_location_city . ', ' . $trainer->primary_location_state); ?></span>
                                    </div>
                                    <?php
                                    $specialties = $trainer->specialties ? json_decode($trainer->specialties, true) : array();
                                    if (!empty($specialties)):
                                    ?>
                                        <div class="ptp-trainer-card__tags">
                                            <?php foreach (array_slice($specialties, 0, 3) as $specialty): ?>
                                                <span class="ptp-pill"><?php echo esc_html($specialty); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="ptp-trainer-card__footer">
                                        <div class="ptp-trainer-card__price">
                                            <span class="ptp-trainer-card__price-label">From</span>
                                            <span class="ptp-trainer-card__price-value">$<?php echo number_format($trainer->hourly_rate, 0); ?></span>
                                            <span class="ptp-trainer-card__price-unit">/ session</span>
                                        </div>
                                        <span class="ptp-trainer-card__cta">View Profile</span>
                                    </div>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="ptp-empty-state">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <h3>No trainers found</h3>
                        <p>Check back soon as we add more trainers to our network.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="ptp-trainers-cta">
                <a href="<?php echo home_url('/private-training/'); ?>" class="ptp-btn ptp-btn-primary ptp-btn-lg">
                    Browse All Trainers
                </a>
            </div>
        </div>
    </section>

    <!-- Booking Form Section (for quick booking) -->
    <section class="ptp-booking-section" id="book">
        <div class="ptp-container">
            <div class="ptp-booking-card">
                <div class="ptp-booking-card__content">
                    <h2>Ready to Book a Session?</h2>
                    <p>Fill out your details and we'll match you with the perfect trainer, or browse trainers above to book directly.</p>

                    <form id="ptpQuickBookingForm" class="ptp-booking-form">
                        <?php wp_nonce_field('ptp_booking_request', 'ptp_booking_nonce'); ?>

                        <div class="ptp-form-row">
                            <div class="ptp-form-group">
                                <label for="parent_name">Your Name <span class="required">*</span></label>
                                <input type="text" id="parent_name" name="parent_name" required placeholder="Parent/Guardian name">
                            </div>
                            <div class="ptp-form-group">
                                <label for="parent_email">Email <span class="required">*</span></label>
                                <input type="email" id="parent_email" name="parent_email" required placeholder="your@email.com">
                            </div>
                        </div>

                        <div class="ptp-form-row">
                            <div class="ptp-form-group">
                                <label for="parent_phone">Phone <span class="required">*</span></label>
                                <input type="tel" id="parent_phone" name="parent_phone" required placeholder="(555) 123-4567">
                            </div>
                            <div class="ptp-form-group">
                                <label for="player_name">Player Name <span class="required">*</span></label>
                                <input type="text" id="player_name" name="player_name" required placeholder="Player's name">
                            </div>
                        </div>

                        <div class="ptp-form-row">
                            <div class="ptp-form-group">
                                <label for="player_age">Player Age <span class="required">*</span></label>
                                <select id="player_age" name="player_age" required>
                                    <option value="">Select age</option>
                                    <?php for ($i = 5; $i <= 18; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?> years old</option>
                                    <?php endfor; ?>
                                    <option value="18+">18+</option>
                                </select>
                            </div>
                            <div class="ptp-form-group">
                                <label for="session_type">Session Type</label>
                                <select id="session_type" name="session_type">
                                    <option value="1on1">1:1 Training</option>
                                    <option value="small_group">Small Group (2-4 players)</option>
                                    <option value="evaluation">Skills Evaluation</option>
                                    <option value="goalkeeper">Goalkeeper Training</option>
                                </select>
                            </div>
                        </div>

                        <div class="ptp-form-row">
                            <div class="ptp-form-group">
                                <label for="preferred_date">Preferred Date</label>
                                <input type="date" id="preferred_date" name="preferred_date" min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="ptp-form-group">
                                <label for="preferred_time">Preferred Time</label>
                                <select id="preferred_time" name="preferred_time">
                                    <option value="">Flexible</option>
                                    <option value="morning">Morning (8am-12pm)</option>
                                    <option value="afternoon">Afternoon (12pm-5pm)</option>
                                    <option value="evening">Evening (5pm-8pm)</option>
                                </select>
                            </div>
                        </div>

                        <div class="ptp-form-group">
                            <label for="location">Training Location</label>
                            <input type="text" id="location" name="location" placeholder="Field, park, or address">
                        </div>

                        <div class="ptp-form-group">
                            <label for="notes">Additional Notes</label>
                            <textarea id="notes" name="notes" rows="3" placeholder="Tell us about your player's goals, position, skill level, etc."></textarea>
                        </div>

                        <button type="submit" class="ptp-btn ptp-btn-primary ptp-btn-lg ptp-btn-block">
                            Submit Booking Request
                        </button>

                        <p class="ptp-booking-form__note">
                            We'll reach out within 24 hours to confirm your session and match you with a trainer.
                        </p>
                    </form>

                    <div id="ptpBookingSuccess" class="ptp-booking-success" style="display: none;">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                        <h3>Request Submitted!</h3>
                        <p>Thank you for your booking request. We'll be in touch within 24 hours to confirm your session.</p>
                        <a href="<?php echo home_url('/private-training/'); ?>" class="ptp-btn ptp-btn-outline">Browse Trainers</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="ptp-final-cta">
        <div class="ptp-container">
            <div class="ptp-final-cta__content">
                <h2>Are You a Player or Coach?</h2>
                <p>Join our network of trainers and start earning money doing what you love. Share your experience with the next generation.</p>
                <a href="<?php echo home_url('/become-a-trainer/'); ?>" class="ptp-btn ptp-btn-secondary ptp-btn-lg">Apply to Become a Trainer</a>
            </div>
        </div>
    </section>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // State filter
    const stateFilter = document.getElementById('ptpStateFilter');
    const trainerCards = document.querySelectorAll('.ptp-trainer-card');

    if (stateFilter) {
        stateFilter.addEventListener('change', function() {
            const selectedState = this.value;
            trainerCards.forEach(card => {
                if (!selectedState || card.dataset.state === selectedState) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }

    // Smooth scroll to trainers
    document.querySelectorAll('a[href="#trainers"], a[href="#book"]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // Quick booking form
    const bookingForm = document.getElementById('ptpQuickBookingForm');
    const bookingSuccess = document.getElementById('ptpBookingSuccess');

    if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'ptp_quick_booking_request');

            fetch(ptpTraining.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bookingForm.style.display = 'none';
                    bookingSuccess.style.display = 'flex';
                } else {
                    alert(data.data?.message || 'Something went wrong. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Something went wrong. Please try again.');
            });
        });
    }
});
</script>

<style>
/* Private Training Page Styles */
.ptp-private-training {
    font-family: var(--ptp-font, 'Inter', -apple-system, BlinkMacSystemFont, sans-serif);
    color: var(--ptp-ink, #0E0F11);
    --ptp-yellow: #FCB900;
    --ptp-ink: #0E0F11;
    --ptp-offwhite: #F4F3F0;
    --ptp-gray: #6B7280;
    --ptp-border: #E5E7EB;
}

/* Hero Section */
.ptp-hero {
    position: relative;
    background: linear-gradient(135deg, var(--ptp-ink) 0%, #1a1b1e 100%);
    background-image: url('https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1899.jpg');
    background-size: cover;
    background-position: center;
    padding: 100px 20px 120px;
    text-align: center;
    overflow: hidden;
}

.ptp-hero__overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(14, 15, 17, 0.75) 0%, rgba(14, 15, 17, 0.85) 100%);
}

.ptp-hero__content {
    position: relative;
    z-index: 2;
    max-width: 900px;
    margin: 0 auto;
}

.ptp-hero__tag {
    display: inline-block;
    background: rgba(252, 185, 0, 0.15);
    color: var(--ptp-yellow);
    font-size: 14px;
    font-weight: 600;
    padding: 8px 20px;
    border-radius: 50px;
    margin-bottom: 24px;
}

.ptp-hero__title {
    color: #fff;
    font-size: 36px;
    font-weight: 800;
    line-height: 1.2;
    margin: 0 0 20px;
}

@media (min-width: 768px) {
    .ptp-hero__title { font-size: 56px; }
}

.ptp-hero__subtitle {
    color: rgba(255, 255, 255, 0.85);
    font-size: 18px;
    line-height: 1.6;
    margin: 0 0 36px;
    max-width: 700px;
    margin-left: auto;
    margin-right: auto;
}

.ptp-hero__cta {
    display: flex;
    flex-direction: column;
    gap: 12px;
    justify-content: center;
    align-items: center;
}

@media (min-width: 480px) {
    .ptp-hero__cta { flex-direction: row; }
}

.ptp-btn-white-outline {
    border-color: rgba(255, 255, 255, 0.5);
    color: #fff;
}

.ptp-btn-white-outline:hover {
    border-color: #fff;
    background: rgba(255, 255, 255, 0.1);
}

.ptp-hero__trust {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 32px;
    margin-top: 48px;
    position: relative;
    z-index: 2;
}

.ptp-hero__trust-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: rgba(255, 255, 255, 0.75);
    font-size: 14px;
    font-weight: 500;
}

.ptp-hero__trust-item svg {
    color: var(--ptp-yellow);
}

/* Container */
.ptp-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Section Styles */
.ptp-section-title {
    font-size: 32px;
    font-weight: 800;
    text-align: center;
    margin: 0 0 12px;
}

.ptp-section-subtitle {
    font-size: 18px;
    color: var(--ptp-gray);
    text-align: center;
    margin: 0 0 48px;
}

/* How It Works */
.ptp-how-it-works {
    background: #fff;
    padding: 80px 20px;
}

.ptp-steps {
    display: grid;
    grid-template-columns: 1fr;
    gap: 32px;
}

@media (min-width: 640px) {
    .ptp-steps { grid-template-columns: repeat(2, 1fr); }
}

@media (min-width: 1024px) {
    .ptp-steps { grid-template-columns: repeat(4, 1fr); }
}

.ptp-step {
    text-align: center;
    position: relative;
    padding: 24px;
}

.ptp-step__icon {
    width: 72px;
    height: 72px;
    background: var(--ptp-offwhite);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    color: var(--ptp-ink);
}

.ptp-step__number {
    position: absolute;
    top: 16px;
    right: calc(50% - 52px);
    width: 28px;
    height: 28px;
    background: var(--ptp-yellow);
    color: var(--ptp-ink);
    border-radius: 50%;
    font-size: 14px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
}

.ptp-step__title {
    font-size: 18px;
    font-weight: 700;
    margin: 0 0 12px;
}

.ptp-step__desc {
    font-size: 15px;
    color: var(--ptp-gray);
    line-height: 1.6;
    margin: 0;
}

/* Trainers Section */
.ptp-trainers-section {
    background: var(--ptp-offwhite);
    padding: 80px 20px;
}

.ptp-quick-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    justify-content: center;
    align-items: center;
    margin-bottom: 32px;
}

.ptp-filter-select {
    padding: 12px 40px 12px 16px;
    border: 1px solid var(--ptp-border);
    border-radius: 8px;
    font-size: 15px;
    background: #fff;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236B7280' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
}

.ptp-trainer-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 24px;
}

@media (min-width: 640px) {
    .ptp-trainer-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (min-width: 1024px) {
    .ptp-trainer-grid { grid-template-columns: repeat(3, 1fr); }
}

@media (min-width: 1280px) {
    .ptp-trainer-grid { grid-template-columns: repeat(4, 1fr); }
}

.ptp-trainers-cta {
    text-align: center;
    margin-top: 48px;
}

/* Booking Section */
.ptp-booking-section {
    background: #fff;
    padding: 80px 20px;
}

.ptp-booking-card {
    max-width: 700px;
    margin: 0 auto;
    background: #fff;
    border: 1px solid var(--ptp-border);
    border-radius: 16px;
    padding: 40px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.ptp-booking-card h2 {
    font-size: 28px;
    font-weight: 700;
    margin: 0 0 12px;
    text-align: center;
}

.ptp-booking-card > .ptp-booking-card__content > p {
    color: var(--ptp-gray);
    text-align: center;
    margin: 0 0 32px;
}

.ptp-booking-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.ptp-form-row {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
}

@media (min-width: 480px) {
    .ptp-form-row { grid-template-columns: repeat(2, 1fr); }
}

.ptp-form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.ptp-form-group label {
    font-size: 14px;
    font-weight: 600;
    color: var(--ptp-ink);
}

.ptp-form-group label .required {
    color: #EF4444;
}

.ptp-form-group input,
.ptp-form-group select,
.ptp-form-group textarea {
    padding: 14px 16px;
    border: 1px solid var(--ptp-border);
    border-radius: 8px;
    font-size: 15px;
    font-family: inherit;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.ptp-form-group input:focus,
.ptp-form-group select:focus,
.ptp-form-group textarea:focus {
    outline: none;
    border-color: var(--ptp-yellow);
    box-shadow: 0 0 0 3px rgba(252, 185, 0, 0.15);
}

.ptp-form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.ptp-booking-form__note {
    font-size: 13px;
    color: var(--ptp-gray);
    text-align: center;
    margin: 8px 0 0;
}

.ptp-booking-success {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 40px 0;
}

.ptp-booking-success svg {
    margin-bottom: 24px;
}

.ptp-booking-success h3 {
    font-size: 24px;
    font-weight: 700;
    margin: 0 0 12px;
    color: #10B981;
}

.ptp-booking-success p {
    color: var(--ptp-gray);
    margin: 0 0 24px;
}

/* Final CTA */
.ptp-final-cta {
    background: var(--ptp-ink);
    padding: 80px 20px;
    text-align: center;
}

.ptp-final-cta__content h2 {
    color: #fff;
    font-size: 32px;
    font-weight: 800;
    margin: 0 0 16px;
}

.ptp-final-cta__content p {
    color: rgba(255, 255, 255, 0.75);
    font-size: 18px;
    margin: 0 0 32px;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

/* Empty State */
.ptp-empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    color: var(--ptp-gray);
}

.ptp-empty-state svg {
    margin-bottom: 16px;
    opacity: 0.5;
}

.ptp-empty-state h3 {
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 8px;
    color: var(--ptp-ink);
}

.ptp-empty-state p {
    margin: 0;
}
</style>
