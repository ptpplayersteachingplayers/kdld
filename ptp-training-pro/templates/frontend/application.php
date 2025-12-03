<?php
/**
 * Become a Trainer - Application Form
 * Black theme with commission transparency
 */

if (!defined('ABSPATH')) exit;

$submitted = isset($_GET['submitted']) && $_GET['submitted'] === 'true';
$platform_fee = get_option('ptp_platform_fee_percent', 25);
$trainer_cut = 100 - $platform_fee;
$referral_commission = get_option('ptp_trainer_referral_commission', 10);
?>

<div class="ptp-application">
    <div class="ptp-app-container">
        
        <?php if ($submitted): ?>
        <!-- Success State -->
        <div class="ptp-app-success">
            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <h1>Application Submitted!</h1>
            <p>Thank you for your interest in becoming a PTP trainer. Our team will review your application and get back to you within 2-3 business days.</p>
            <a href="<?php echo home_url(); ?>" class="ptp-btn ptp-btn-primary ptp-btn-lg">Back to Home</a>
        </div>
        
        <?php else: ?>
        
        <!-- Hero Section -->
        <header class="ptp-app-hero" style="background-image: linear-gradient(rgba(14, 15, 17, 0.7), rgba(14, 15, 17, 0.9)), url('https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1874.jpg');">
            <div class="ptp-app-hero-content">
                <h1>Become a PTP Trainer</h1>
                <p>Join our network of elite soccer coaches and earn money doing what you love</p>
                
                <!-- Commission Breakdown Box -->
                <div class="ptp-commission-box">
                    <h3>💰 How You Earn</h3>
                    <div class="ptp-commission-item">
                        <span class="ptp-commission-label">Your Earnings (per session)</span>
                        <span class="ptp-commission-value earnings"><?php echo $trainer_cut; ?>%</span>
                    </div>
                    <div class="ptp-commission-item">
                        <span class="ptp-commission-label">PTP Platform Fee</span>
                        <span class="ptp-commission-value"><?php echo $platform_fee; ?>%</span>
                    </div>
                    <div class="ptp-commission-item">
                        <span class="ptp-commission-label">Camp Referral Bonus</span>
                        <span class="ptp-commission-value earnings">+<?php echo $referral_commission; ?>%</span>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Benefits Section -->
        <section class="ptp-app-benefits">
            <div class="ptp-app-benefit">
                <img src="https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1847.jpg" alt="Earn great money" class="ptp-app-benefit-img" loading="lazy" />
                <div class="ptp-app-benefit-content">
                    <div class="ptp-app-benefit-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                    <h3>$50-100+/hour</h3>
                    <p>Set your own rates. Keep <?php echo $trainer_cut; ?>% of every session.</p>
                </div>
            </div>
            
            <div class="ptp-app-benefit">
                <img src="https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1797.jpg" alt="Flexible schedule" class="ptp-app-benefit-img" loading="lazy" />
                <div class="ptp-app-benefit-content">
                    <div class="ptp-app-benefit-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>
                    <h3>Your Schedule</h3>
                    <p>Train when and where you want. Full control.</p>
                </div>
            </div>
            
            <div class="ptp-app-benefit">
                <img src="https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1790.jpg" alt="Built-in clients" class="ptp-app-benefit-img" loading="lazy" />
                <div class="ptp-app-benefit-content">
                    <div class="ptp-app-benefit-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <h3>Built-in Clients</h3>
                    <p>Access PTP's network of 5,000+ camp families.</p>
                </div>
            </div>
        </section>
        
        <!-- Application Form -->
        <section class="ptp-app-form-section">
            <h2>Apply Now</h2>
            <p>Takes about 5 minutes. We review applications within 2-3 business days.</p>
            
            <form class="ptp-app-form" id="trainerApplicationForm">
                <?php wp_nonce_field('ptp_application', 'ptp_nonce'); ?>
                
                <!-- Personal Info -->
                <fieldset>
                    <legend>Personal Information</legend>
                    
                    <div class="ptp-form-row">
                        <div class="ptp-form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" required autocomplete="given-name" />
                        </div>
                        <div class="ptp-form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" required autocomplete="family-name" />
                        </div>
                    </div>
                    
                    <div class="ptp-form-row">
                        <div class="ptp-form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" required autocomplete="email" />
                        </div>
                        <div class="ptp-form-group">
                            <label for="phone">Phone *</label>
                            <input type="tel" id="phone" name="phone" required autocomplete="tel" placeholder="(555) 123-4567" />
                        </div>
                    </div>
                </fieldset>
                
                <!-- Location -->
                <fieldset>
                    <legend>Location</legend>
                    
                    <div class="ptp-form-row">
                        <div class="ptp-form-group">
                            <label for="city">City *</label>
                            <input type="text" id="city" name="city" required autocomplete="address-level2" />
                        </div>
                        <div class="ptp-form-group">
                            <label for="state">State *</label>
                            <select id="state" name="state" required>
                                <option value="">Select State</option>
                                <option value="PA">Pennsylvania</option>
                                <option value="NJ">New Jersey</option>
                                <option value="DE">Delaware</option>
                                <option value="MD">Maryland</option>
                                <option value="NY">New York</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="ptp-form-group ptp-form-group-narrow">
                        <label for="zip">ZIP Code *</label>
                        <input type="text" id="zip" name="zip" required autocomplete="postal-code" maxlength="10" />
                    </div>
                </fieldset>
                
                <!-- Soccer Background -->
                <fieldset>
                    <legend>Soccer Background</legend>
                    
                    <div class="ptp-form-group">
                        <label for="playing_background">Playing Experience *</label>
                        <textarea id="playing_background" name="playing_background" required placeholder="Tell us about your playing background (club, college, professional, etc.)"></textarea>
                    </div>
                    
                    <div class="ptp-form-group">
                        <label for="coaching_experience">Coaching/Training Experience</label>
                        <textarea id="coaching_experience" name="coaching_experience" placeholder="Any previous coaching or training experience? (optional)"></textarea>
                    </div>
                    
                    <div class="ptp-form-group">
                        <label for="certifications">Certifications</label>
                        <input type="text" id="certifications" name="certifications" placeholder="USSF, USC, etc. (optional)" />
                    </div>
                    
                    <div class="ptp-form-group">
                        <label for="experience_summary">Brief Summary *</label>
                        <textarea id="experience_summary" name="experience_summary" required placeholder="In 2-3 sentences, why would you be a great PTP trainer?"></textarea>
                    </div>
                </fieldset>
                
                <!-- Media -->
                <fieldset>
                    <legend>Media (Important!)</legend>
                    
                    <div class="ptp-form-group">
                        <label for="intro_video_url">Intro Video URL *</label>
                        <input type="url" id="intro_video_url" name="intro_video_url" required placeholder="YouTube or Vimeo link" />
                        <p class="ptp-form-hint">Record a 30-60 second video introducing yourself. This helps families get to know you!</p>
                    </div>
                    
                    <div class="ptp-form-group">
                        <label for="instagram">Instagram Handle</label>
                        <input type="text" id="instagram" name="instagram" placeholder="@yourhandle" />
                    </div>
                </fieldset>
                
                <!-- Availability -->
                <fieldset>
                    <legend>Availability & More</legend>
                    
                    <div class="ptp-form-group">
                        <label for="availability">General Availability *</label>
                        <textarea id="availability" name="availability" required placeholder="When are you typically available to train? (e.g., Weekday evenings, Weekend mornings)"></textarea>
                    </div>
                    
                    <div class="ptp-form-group">
                        <label for="referral_source">How did you hear about us?</label>
                        <select id="referral_source" name="referral_source">
                            <option value="">Select one</option>
                            <option value="instagram">Instagram</option>
                            <option value="facebook">Facebook</option>
                            <option value="google">Google Search</option>
                            <option value="friend">Friend/Teammate</option>
                            <option value="ptp_camp">PTP Camp</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="ptp-form-group">
                        <label for="why_join">Why do you want to join PTP?</label>
                        <textarea id="why_join" name="why_join" placeholder="What excites you about training youth soccer players?"></textarea>
                    </div>
                </fieldset>
                
                <!-- Terms -->
                <div class="ptp-form-group ptp-form-terms">
                    <label class="ptp-checkbox-label">
                        <input type="checkbox" name="terms" required class="ptp-checkbox-input" />
                        <span class="ptp-checkbox-text">
                            I understand that PTP takes a <strong class="ptp-highlight"><?php echo $platform_fee; ?>% platform fee</strong> from each session, and I agree to the <a href="<?php echo home_url('/terms'); ?>" target="_blank" class="ptp-highlight-link">Terms of Service</a>.
                        </span>
                    </label>
                </div>
                
                <div class="ptp-form-submit">
                    <button type="submit" class="ptp-btn ptp-btn-primary ptp-btn-lg">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 2L11 13"/><path d="M22 2L15 22L11 13L2 9L22 2Z"/>
                        </svg>
                        Submit Application
                    </button>
                </div>
            </form>
        </section>
        
        <!-- FAQ Section -->
        <section class="ptp-app-faq">
            <h2>Frequently Asked Questions</h2>

            <div class="ptp-app-faq-grid">
                <div class="ptp-app-faq-item">
                    <h3>How much can I earn?</h3>
                    <p>You set your own rates (most trainers charge $50-100/hour). You keep <?php echo $trainer_cut; ?>% of each session. Train 10 hours/week at $75/hour = $<?php echo number_format(10 * 75 * ($trainer_cut/100), 0); ?>+/week.</p>
                </div>

                <div class="ptp-app-faq-item">
                    <h3>What does the <?php echo $platform_fee; ?>% cover?</h3>
                    <p>Marketing to find you clients, payment processing, scheduling tools, customer support, insurance, and our booking platform.</p>
                </div>

                <div class="ptp-app-faq-item">
                    <h3>How does the referral bonus work?</h3>
                    <p>You get a unique code. When families use it to register for PTP camps/clinics, you earn <?php echo $referral_commission; ?>% of their registration — on top of your training income!</p>
                </div>

                <div class="ptp-app-faq-item">
                    <h3>When do I get paid?</h3>
                    <p>Payments are processed weekly via Stripe direct deposit. You'll connect your bank account during onboarding.</p>
                </div>
            </div>
        </section>
        
        <?php endif; ?>
        
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('trainerApplicationForm');
    if (!form) return;
    
    // Phone formatting
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
            e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
        });
    }
    
    // Instagram handle formatting
    const instagramInput = document.getElementById('instagram');
    if (instagramInput) {
        instagramInput.addEventListener('blur', function(e) {
            let value = e.target.value.trim();
            if (value && !value.startsWith('@')) {
                e.target.value = '@' + value;
            }
        });
    }
    
    // Form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="ptp-spinner" style="width:20px;height:20px;border-width:2px;"></span> Submitting...';
        submitBtn.disabled = true;
        
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        try {
            const response = await fetch('<?php echo rest_url('ptp-training/v1/applications'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (response.ok) {
                window.location.href = '<?php echo home_url('/become-a-trainer/?submitted=true'); ?>';
            } else {
                throw new Error(result.message || 'Something went wrong');
            }
        } catch (error) {
            alert(error.message);
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });
});
</script>
