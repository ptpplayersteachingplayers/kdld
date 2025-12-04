<?php
/**
 * Trainer Marketplace - TeachMe.to Style
 * Map-based discovery with coach cards
 */

if (!defined('ABSPATH')) exit;

// Get filter params
$current_state = isset($_GET['state']) ? sanitize_text_field($_GET['state']) : '';
$current_specialty = isset($_GET['specialty']) ? sanitize_text_field($_GET['specialty']) : '';
$current_location = isset($_GET['location']) ? sanitize_text_field($_GET['location']) : 'Pennsylvania';

// Get filters
global $wpdb;
$states = $wpdb->get_col(
    "SELECT DISTINCT primary_location_state FROM {$wpdb->prefix}ptp_trainers
     WHERE status = 'approved' AND primary_location_state != ''
     ORDER BY primary_location_state"
);

$specialties = array(
    'Ball Control', 'Finishing', '1v1 Moves', 'Defending',
    'Goalkeeping', 'Speed & Agility', 'Game IQ', 'Passing & Vision'
);
?>

<div class="ptp-marketplace" id="ptpMarketplace">

    <!-- Header Bar -->
    <header class="ptp-mp-header">
        <a href="<?php echo home_url(); ?>" class="ptp-mp-logo">⚽ PTP</a>

        <nav class="ptp-mp-nav">
            <div class="ptp-mp-dropdown">
                <span class="ptp-mp-dropdown-label">Learn</span>
                <span class="ptp-mp-dropdown-value">
                    Soccer ⚡
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </span>
            </div>

            <div class="ptp-mp-dropdown">
                <span class="ptp-mp-dropdown-label">Near</span>
                <span class="ptp-mp-dropdown-value" id="locationDisplay">
                    <?php echo esc_html($current_location); ?>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </span>
            </div>
        </nav>

        <div class="ptp-mp-actions">
            <a href="<?php echo home_url('/become-a-trainer/'); ?>" class="ptp-btn ptp-btn-outline ptp-btn-sm ptp-hide-mobile">
                🎁 Become a Trainer
            </a>
            <button class="ptp-btn-icon ptp-btn-outline" id="mobileMenuBtn">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
        </div>
    </header>

    <!-- Filter Bar -->
    <div class="ptp-mp-filters-bar">
        <span class="ptp-mp-filter-label">FILTER</span>

        <button class="ptp-filter-btn" data-filter="coach">
            Coach
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </button>

        <button class="ptp-filter-btn" data-filter="location">
            Location
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </button>

        <button class="ptp-filter-btn" data-filter="availability">
            Availability
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </button>

        <div class="ptp-mp-sort">
            <span>SORT</span>
            <button class="ptp-filter-btn active">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="4" y1="21" x2="4" y2="14"></line>
                    <line x1="4" y1="10" x2="4" y2="3"></line>
                    <line x1="12" y1="21" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12" y2="3"></line>
                    <line x1="20" y1="21" x2="20" y2="16"></line>
                    <line x1="20" y1="12" x2="20" y2="3"></line>
                </svg>
                Recommended
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="ptp-mp-main">

        <!-- Coaches List -->
        <div class="ptp-mp-coaches">
            <h1 class="ptp-mp-title">Top trainers for soccer lessons near <span id="locationTitle"><?php echo esc_html($current_location); ?></span></h1>
            <p class="ptp-mp-subtitle">These trainers teach at top ranked spots and have great lesson fulfillment & student reviews.</p>

            <!-- Coach Grid -->
            <div class="ptp-coach-grid" id="coachGrid">
                <!-- Cards will be loaded via JS -->
                <div class="ptp-loading">
                    <div class="ptp-spinner"></div>
                    <p>Finding trainers near you...</p>
                </div>
            </div>

            <!-- Load More -->
            <div id="loadMoreSection" style="display: none; text-align: center; padding: 32px;">
                <button class="ptp-btn ptp-btn-outline" id="loadMoreBtn">Load More Trainers</button>
            </div>
        </div>

        <!-- Map Section (Desktop) -->
        <div class="ptp-mp-map">
            <div class="ptp-mp-map-container" id="trainersMap"></div>
        </div>

    </div>

</div>

<!-- Coach Card Template -->
<template id="coachCardTemplate">
    <article class="ptp-coach-card">
        <a href="" class="ptp-coach-card-link">
            <div class="ptp-coach-card-media">
                <img src="" alt="" class="ptp-coach-photo" loading="lazy" />

                <!-- Score Badge -->
                <div class="ptp-score-badge">
                    <div class="ptp-score-ring">
                        <svg viewBox="0 0 36 36">
                            <circle class="ptp-score-ring-bg" cx="18" cy="18" r="15.9"/>
                            <circle class="ptp-score-ring-fill" cx="18" cy="18" r="15.9"
                                    stroke-dasharray="100" stroke-dashoffset="0"/>
                        </svg>
                        <span class="ptp-score-value">99</span>
                    </div>
                    <span class="ptp-score-label">Score</span>
                </div>

                <!-- SuperCoach Badge (conditional) -->
                <div class="ptp-supercoach-badge" style="display: none;">
                    SuperCoach ⭐
                </div>

                <!-- Content Overlay -->
                <div class="ptp-coach-card-overlay">
                    <h3 class="ptp-coach-name"></h3>
                    <div class="ptp-coach-price-row">
                        <div class="ptp-coach-price">
                            <strong class="coach-price-value"></strong>
                            <span>for weekly lessons</span>
                        </div>
                        <button class="ptp-btn-arrow">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="ptp-coach-card-body">
                <div class="ptp-coach-sport-row">
                    <div class="ptp-coach-sport">
                        <span class="ptp-coach-sport-icon">⚽</span>
                        Soccer
                    </div>
                    <div class="ptp-coach-rating">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="#F59E0B">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                        <span class="coach-rating-value">5.0</span>
                        <span class="coach-review-count">(0)</span>
                    </div>
                </div>

                <div class="ptp-coach-location-row">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3" fill="white"/>
                    </svg>
                    <span class="coach-location-type">Closest</span>
                    <span>•</span>
                    <span class="coach-location-name"></span>
                    <span>•</span>
                    <span class="coach-distance"></span>
                </div>

                <div class="ptp-coach-availability">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <span class="coach-availability-text">Great availability</span>
                    <span>•</span>
                    <span class="coach-hours-week">-- hours this week</span>
                </div>
            </div>
        </a>
    </article>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const marketplace = {
        trainers: [],
        map: null,
        markers: [],
        userLocation: null,
        currentPage: 0,
        perPage: 12,
        hasMore: true,

        init: function() {
            this.checkUserLocation();
            this.loadTrainers();
            this.initMap();
            this.bindEvents();
        },

        bindEvents: function() {
            document.getElementById('loadMoreBtn')?.addEventListener('click', () => this.loadMore());
        },

        checkUserLocation: function() {
            const urlParams = new URLSearchParams(window.location.search);
            const lat = urlParams.get('lat');
            const lng = urlParams.get('lng');

            if (lat && lng) {
                this.userLocation = { lat: parseFloat(lat), lng: parseFloat(lng) };
            } else {
                // Default to PA area
                this.userLocation = { lat: 40.0, lng: -75.5 };
            }
        },

        loadTrainers: async function() {
            const grid = document.getElementById('coachGrid');

            if (this.currentPage === 0) {
                grid.innerHTML = '<div class="ptp-loading"><div class="ptp-spinner"></div><p>Finding trainers near you...</p></div>';
            }

            try {
                const params = new URLSearchParams({
                    lat: this.userLocation?.lat || 40.0,
                    lng: this.userLocation?.lng || -75.5,
                    limit: this.perPage,
                    offset: this.currentPage * this.perPage
                });

                const response = await fetch(ptpTraining.rest_url + 'trainers?' + params.toString());
                const data = await response.json();

                if (this.currentPage === 0) {
                    this.trainers = data.trainers || [];
                    grid.innerHTML = '';
                } else {
                    this.trainers = [...this.trainers, ...(data.trainers || [])];
                }

                this.hasMore = data.has_more || false;
                this.renderTrainers(data.trainers || [], this.currentPage === 0);

                document.getElementById('loadMoreSection').style.display = this.hasMore ? 'block' : 'none';

                this.updateMapMarkers();

            } catch (error) {
                console.error('Error loading trainers:', error);
                grid.innerHTML = '<div class="ptp-empty-state"><h3>Unable to load trainers</h3><p>Please try again later.</p></div>';
            }
        },

        loadMore: function() {
            this.currentPage++;
            this.loadTrainers();
        },

        renderTrainers: function(trainers, clear = false) {
            const grid = document.getElementById('coachGrid');
            const template = document.getElementById('coachCardTemplate');

            if (clear) grid.innerHTML = '';

            if (trainers.length === 0 && this.currentPage === 0) {
                grid.innerHTML = `
                    <div class="ptp-empty-state">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                        <h3>No trainers found</h3>
                        <p>Try searching in a different area or adjusting your filters.</p>
                    </div>
                `;
                return;
            }

            trainers.forEach(trainer => {
                const card = template.content.cloneNode(true);
                const score = Math.min(99, Math.round(trainer.avg_rating * 20) || 90);

                // Set link
                card.querySelector('.ptp-coach-card-link').href = '<?php echo home_url('/trainer/'); ?>' + trainer.slug;

                // Photo
                card.querySelector('.ptp-coach-photo').src = trainer.profile_photo || '<?php echo PTP_TRAINING_URL; ?>assets/images/default-avatar.svg';
                card.querySelector('.ptp-coach-photo').alt = trainer.display_name;

                // Score badge
                card.querySelector('.ptp-score-value').textContent = score;
                const scoreOffset = 100 - score;
                card.querySelector('.ptp-score-ring-fill').style.strokeDashoffset = scoreOffset;

                // SuperCoach badge
                if (trainer.is_featured || trainer.total_sessions >= 50) {
                    card.querySelector('.ptp-supercoach-badge').style.display = 'flex';
                }

                // Name and price
                card.querySelector('.ptp-coach-name').textContent = trainer.display_name;
                card.querySelector('.coach-price-value').textContent = '$' + (trainer.hourly_rate || 75);

                // Rating
                card.querySelector('.coach-rating-value').textContent = (trainer.avg_rating || 5.0).toFixed(1);
                card.querySelector('.coach-review-count').textContent = '(' + (trainer.total_reviews || 0) + ')';

                // Location
                const city = trainer.location?.city || trainer.primary_location_city || 'Local';
                card.querySelector('.coach-location-name').textContent = city;
                card.querySelector('.coach-distance').textContent = (trainer.distance || 0).toFixed(1) + ' mi';

                // Availability
                const weeklyHours = trainer.weekly_availability || Math.floor(Math.random() * 30) + 40;
                card.querySelector('.coach-hours-week').textContent = weeklyHours + ' hours this week';

                grid.appendChild(card);
            });
        },

        initMap: function() {
            if (!window.google || !google.maps) {
                console.log('Google Maps not available');
                return;
            }

            const mapContainer = document.getElementById('trainersMap');
            if (!mapContainer) return;

            this.map = new google.maps.Map(mapContainer, {
                center: this.userLocation || { lat: 40.0, lng: -75.5 },
                zoom: 10,
                styles: [
                    { featureType: 'poi', stylers: [{ visibility: 'off' }] },
                    { featureType: 'transit', stylers: [{ visibility: 'off' }] }
                ],
                mapTypeControl: false,
                streetViewControl: false
            });
        },

        updateMapMarkers: function() {
            if (!this.map) return;

            // Clear existing markers
            this.markers.forEach(marker => marker.setMap(null));
            this.markers = [];

            const bounds = new google.maps.LatLngBounds();

            this.trainers.forEach(trainer => {
                const lat = trainer.location?.lat || trainer.primary_location_lat;
                const lng = trainer.location?.lng || trainer.primary_location_lng;

                if (!lat || !lng) return;

                const marker = new google.maps.Marker({
                    position: { lat: parseFloat(lat), lng: parseFloat(lng) },
                    map: this.map,
                    title: trainer.display_name,
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 12,
                        fillColor: '#2563EB',
                        fillOpacity: 1,
                        strokeColor: '#FFFFFF',
                        strokeWeight: 2
                    }
                });

                const infoWindow = new google.maps.InfoWindow({
                    content: `
                        <div style="padding: 8px; min-width: 150px;">
                            <strong style="font-size: 14px;">${trainer.display_name}</strong>
                            <div style="font-size: 13px; color: #666; margin: 4px 0;">
                                ⭐ ${(trainer.avg_rating || 5.0).toFixed(1)} (${trainer.total_reviews || 0})
                            </div>
                            <div style="font-size: 14px; font-weight: 600; color: #2563EB;">
                                $${trainer.hourly_rate || 75}/session
                            </div>
                            <a href="<?php echo home_url('/trainer/'); ?>${trainer.slug}"
                               style="display: inline-block; margin-top: 8px; padding: 6px 12px;
                                      background: #2563EB; color: white; border-radius: 20px;
                                      font-size: 12px; text-decoration: none;">
                                View Profile
                            </a>
                        </div>
                    `
                });

                marker.addListener('click', () => {
                    infoWindow.open(this.map, marker);
                });

                this.markers.push(marker);
                bounds.extend(marker.getPosition());
            });

            if (this.markers.length > 0) {
                this.map.fitBounds(bounds);
                if (this.markers.length === 1) {
                    this.map.setZoom(13);
                }
            }
        }
    };

    marketplace.init();
});
</script>
