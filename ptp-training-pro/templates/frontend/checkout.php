<?php
/**
 * Booking Checkout Flow
 * Step-by-step booking with athlete info collection
 */

if (!defined('ABSPATH')) exit;

$trainer_id = isset($_GET['trainer']) ? intval($_GET['trainer']) : 0;
$pack_type = isset($_GET['pack']) ? sanitize_text_field($_GET['pack']) : 'single';
$selected_date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : '';
$selected_time = isset($_GET['time']) ? sanitize_text_field($_GET['time']) : '';

if (!$trainer_id) {
    echo '<div class="ptp-error"><h2>No Trainer Selected</h2><p>Please select a trainer first.</p><a href="' . home_url('/private-training/') . '" class="ptp-btn">Find a Trainer</a></div>';
    return;
}

$trainer = PTP_Database::get_trainer($trainer_id);
if (!$trainer || $trainer->status !== 'approved') {
    echo '<div class="ptp-error"><h2>Trainer Not Found</h2><p>This trainer is no longer available.</p><a href="' . home_url('/private-training/') . '" class="ptp-btn">Find a Trainer</a></div>';
    return;
}

// Calculate pricing
$hourly = floatval($trainer->hourly_rate);
$pricing = array(
    'single' => array('sessions' => 1, 'price' => $hourly, 'name' => 'Single Session'),
    'pack_4' => array('sessions' => 4, 'price' => floatval($trainer->pack_4_rate) ?: $hourly * 4 * (1 - $trainer->pack_4_discount / 100), 'name' => '4-Pack', 'discount' => $trainer->pack_4_discount),
    'pack_8' => array('sessions' => 8, 'price' => floatval($trainer->pack_8_rate) ?: $hourly * 8 * (1 - $trainer->pack_8_discount / 100), 'name' => '8-Pack', 'discount' => $trainer->pack_8_discount)
);

$selected_pack = $pricing[$pack_type] ?? $pricing['single'];
$current_user = wp_get_current_user();
?>

<div class="ptp-checkout" id="ptpCheckout">
    
    <div class="ptp-checkout-header">
        <a href="<?php echo home_url('/trainer/' . $trainer->slug); ?>" class="ptp-checkout-back">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
            </svg>
            Back to Profile
        </a>
        <h1>Complete Your Booking</h1>
    </div>
    
    <div class="ptp-checkout-main">
        
        <!-- Checkout Form -->
        <div class="ptp-checkout-form">
            <form id="bookingForm">
                <input type="hidden" name="trainer_id" value="<?php echo esc_attr($trainer_id); ?>" />
                <input type="hidden" name="pack_type" value="<?php echo esc_attr($pack_type); ?>" />
                
                <!-- Step 1: Athlete Info -->
                <section class="ptp-checkout-section">
                    <div class="ptp-checkout-section-header">
                        <span class="ptp-checkout-step">1</span>
                        <h2>Athlete Information</h2>
                    </div>
                    
                    <div class="ptp-form-row">
                        <div class="ptp-form-group">
                            <label for="athlete_name">Athlete's Name *</label>
                            <input type="text" id="athlete_name" name="athlete_name" required placeholder="Enter athlete's name" />
                        </div>
                        <div class="ptp-form-group">
                            <label for="athlete_age">Age *</label>
                            <select id="athlete_age" name="athlete_age" required>
                                <option value="">Select age</option>
                                <?php for ($i = 5; $i <= 18; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?> years old</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="ptp-form-group">
                        <label for="athlete_skill">Current Skill Level *</label>
                        <select id="athlete_skill" name="athlete_skill" required>
                            <option value="">Select skill level</option>
                            <option value="beginner">Beginner - Just starting out</option>
                            <option value="recreational">Recreational - Plays for fun</option>
                            <option value="competitive">Competitive - Plays travel/club</option>
                            <option value="advanced">Advanced - High-level competitor</option>
                        </select>
                    </div>
                    
                    <div class="ptp-form-group">
                        <label for="athlete_goals">Training Goals</label>
                        <textarea id="athlete_goals" name="athlete_goals" rows="3" placeholder="What would you like to focus on? (e.g., ball control, shooting accuracy, speed)"></textarea>
                    </div>
                </section>
                
                <!-- Step 2: Contact Info -->
                <section class="ptp-checkout-section">
                    <div class="ptp-checkout-section-header">
                        <span class="ptp-checkout-step">2</span>
                        <h2>Contact Information</h2>
                    </div>
                    
                    <div class="ptp-form-row">
                        <div class="ptp-form-group">
                            <label for="parent_name">Your Name *</label>
                            <input type="text" id="parent_name" name="parent_name" required 
                                   value="<?php echo esc_attr($current_user->display_name); ?>" />
                        </div>
                        <div class="ptp-form-group">
                            <label for="parent_email">Email *</label>
                            <input type="email" id="parent_email" name="parent_email" required 
                                   value="<?php echo esc_attr($current_user->user_email); ?>" />
                        </div>
                    </div>
                    
                    <div class="ptp-form-group">
                        <label for="parent_phone">Phone Number *</label>
                        <input type="tel" id="parent_phone" name="parent_phone" required 
                               placeholder="(555) 555-5555" />
                    </div>
                    
                    <div class="ptp-form-group ptp-form-checkbox">
                        <label>
                            <input type="checkbox" name="sms_opt_in" value="1" />
                            <span>Send me SMS reminders about sessions</span>
                        </label>
                    </div>
                </section>
                
                <!-- Step 3: Schedule First Session (Optional) -->
                <?php if ($pack_type === 'single' || true): ?>
                <section class="ptp-checkout-section">
                    <div class="ptp-checkout-section-header">
                        <span class="ptp-checkout-step">3</span>
                        <h2>Schedule First Session <span class="ptp-optional">(Optional)</span></h2>
                    </div>
                    
                    <p class="ptp-checkout-note">You can schedule now or later from your dashboard.</p>
                    
                    <?php if ($selected_date && $selected_time): ?>
                        <div class="ptp-selected-slot">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            <div>
                                <strong><?php echo date('l, F j, Y', strtotime($selected_date)); ?></strong>
                                <span><?php echo date('g:i A', strtotime($selected_time)); ?></span>
                            </div>
                            <button type="button" class="ptp-change-slot" id="changeSlotBtn">Change</button>
                        </div>
                        <input type="hidden" name="session_date" value="<?php echo esc_attr($selected_date); ?>" />
                        <input type="hidden" name="session_time" value="<?php echo esc_attr($selected_time); ?>" />
                    <?php else: ?>
                        <button type="button" class="ptp-btn ptp-btn-outline" id="selectSlotBtn">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            Select a Date & Time
                        </button>
                    <?php endif; ?>
                </section>
                <?php endif; ?>
                
                <!-- Policies -->
                <section class="ptp-checkout-section ptp-checkout-policies">
                    <div class="ptp-form-group ptp-form-checkbox">
                        <label>
                            <input type="checkbox" name="accept_terms" required />
                            <span>I agree to the <a href="#" target="_blank">Terms of Service</a> and <a href="#" target="_blank">Cancellation Policy</a></span>
                        </label>
                    </div>
                    
                    <div class="ptp-policy-highlights">
                        <div class="ptp-policy-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                            Free cancellation up to 24 hours before
                        </div>
                        <div class="ptp-policy-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                            Sessions expire 6 months from purchase
                        </div>
                        <div class="ptp-policy-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                            Secure payment via Stripe
                        </div>
                    </div>
                </section>
                
                <!-- Submit -->
                <button type="submit" class="ptp-btn ptp-btn-primary ptp-btn-lg ptp-btn-block" id="submitBooking">
                    <span class="ptp-btn-text">Continue to Payment</span>
                    <span class="ptp-btn-loading" style="display: none;">
                        <svg class="ptp-spinner-icon" width="20" height="20" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" opacity="0.3"/>
                            <path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" fill="none"/>
                        </svg>
                        Processing...
                    </span>
                </button>
            </form>
        </div>
        
        <!-- Order Summary -->
        <aside class="ptp-checkout-summary">
            <div class="ptp-summary-card">
                <h3>Order Summary</h3>
                
                <div class="ptp-summary-trainer">
                    <img src="<?php echo esc_url($trainer->profile_photo ?: PTP_TRAINING_URL . 'assets/images/default-avatar.svg'); ?>" 
                         alt="<?php echo esc_attr($trainer->display_name); ?>" />
                    <div>
                        <strong><?php echo esc_html($trainer->display_name); ?></strong>
                        <span><?php echo esc_html($trainer->primary_location_city . ', ' . $trainer->primary_location_state); ?></span>
                    </div>
                </div>
                
                <div class="ptp-summary-divider"></div>
                
                <div class="ptp-summary-package">
                    <div class="ptp-summary-row">
                        <span><?php echo esc_html($selected_pack['name']); ?></span>
                        <span><?php echo $selected_pack['sessions']; ?> session<?php echo $selected_pack['sessions'] > 1 ? 's' : ''; ?></span>
                    </div>
                    <?php if (isset($selected_pack['discount']) && $selected_pack['discount'] > 0): ?>
                        <div class="ptp-summary-row ptp-summary-discount">
                            <span>Volume discount</span>
                            <span>-<?php echo $selected_pack['discount']; ?>%</span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="ptp-summary-divider"></div>
                
                <div class="ptp-summary-total">
                    <span>Total</span>
                    <strong>$<?php echo number_format($selected_pack['price'], 2); ?></strong>
                </div>
                
                <?php if ($selected_pack['sessions'] > 1): ?>
                    <div class="ptp-summary-per-session">
                        $<?php echo number_format($selected_pack['price'] / $selected_pack['sessions'], 2); ?> per session
                    </div>
                <?php endif; ?>
                
                <div class="ptp-summary-secure">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    Secure checkout powered by Stripe
                </div>
            </div>
            
            <!-- Change Package -->
            <div class="ptp-summary-change">
                <p>Need a different package?</p>
                <div class="ptp-package-options">
                    <?php foreach ($pricing as $key => $pkg): ?>
                        <a href="?trainer=<?php echo $trainer_id; ?>&pack=<?php echo $key; ?>" 
                           class="ptp-package-option <?php echo $key === $pack_type ? 'active' : ''; ?>">
                            <span class="ptp-package-name"><?php echo $pkg['name']; ?></span>
                            <span class="ptp-package-price">$<?php echo number_format($pkg['price'], 0); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('bookingForm');
    const submitBtn = document.getElementById('submitBooking');
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Validate
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        // Show loading
        submitBtn.disabled = true;
        submitBtn.querySelector('.ptp-btn-text').style.display = 'none';
        submitBtn.querySelector('.ptp-btn-loading').style.display = 'flex';
        
        try {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            
            const response = await fetch(ptpTraining.rest_url + 'book', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': ptpTraining.nonce
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.checkout_url) {
                window.location.href = result.checkout_url;
            } else if (result.message) {
                alert(result.message);
                submitBtn.disabled = false;
                submitBtn.querySelector('.ptp-btn-text').style.display = 'flex';
                submitBtn.querySelector('.ptp-btn-loading').style.display = 'none';
            }
        } catch (error) {
            console.error('Booking error:', error);
            alert('An error occurred. Please try again.');
            submitBtn.disabled = false;
            submitBtn.querySelector('.ptp-btn-text').style.display = 'flex';
            submitBtn.querySelector('.ptp-btn-loading').style.display = 'none';
        }
    });
});
</script>
