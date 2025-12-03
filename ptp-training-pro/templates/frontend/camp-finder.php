<?php
/**
 * Camp Finder - Interactive Map & List
 * Mobile-first with ZIP code search
 */

if (!defined('ABSPATH')) exit;

// Get attributes from shortcode
$show_map = isset($atts['show_map']) && $atts['show_map'] === 'true';
$initial_state = isset($atts['state']) ? $atts['state'] : '';
$limit = isset($atts['limit']) ? intval($atts['limit']) : 20;

// Get available states from camps
global $wpdb;
$states = $wpdb->get_col(
    "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} 
     WHERE meta_key = '_ptp_camp_state' AND meta_value != ''
     ORDER BY meta_value"
);
?>

<div class="ptp-camp-finder" id="ptpCampFinder">
    
    <!-- Search Header -->
    <div class="ptp-cf-header">
        <h2>Find a Camp Near You</h2>
        <p>Summer 2026 locations now open for registration</p>
        
        <div class="ptp-cf-search">
            <div class="ptp-cf-search-input">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                    <circle cx="12" cy="10" r="3"/>
                </svg>
                <input type="text" id="campZipSearch" placeholder="Enter your ZIP code" maxlength="5" pattern="[0-9]{5}" inputmode="numeric" />
                <button type="button" id="useMyLocationCamp" class="ptp-cf-location-btn" title="Use my location">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <circle cx="12" cy="12" r="3"/>
                        <line x1="12" y1="2" x2="12" y2="6"/>
                        <line x1="12" y1="18" x2="12" y2="22"/>
                    </svg>
                </button>
            </div>
            
            <div class="ptp-cf-radius">
                <select id="campRadius">
                    <option value="10">10 mi</option>
                    <option value="25" selected>25 mi</option>
                    <option value="50">50 mi</option>
                    <option value="75">75 mi</option>
                </select>
            </div>
            
            <button type="button" id="searchCamps" class="ptp-btn ptp-btn-primary">Find Camps</button>
        </div>
        
        <div class="ptp-cf-filters">
            <select id="filterCampState">
                <option value="">All States</option>
                <?php foreach ($states as $state): ?>
                    <option value="<?php echo esc_attr($state); ?>" <?php selected($initial_state, $state); ?>>
                        <?php echo esc_html($state); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select id="filterCampTime">
                <option value="">All Time Slots</option>
                <option value="morning">Morning Only</option>
                <option value="afternoon">Afternoon Only</option>
                <option value="full_day">Full Day</option>
            </select>
            
            <select id="filterCampMonth">
                <option value="">All Dates</option>
                <option value="2026-06">June 2026</option>
                <option value="2026-07">July 2026</option>
                <option value="2026-08">August 2026</option>
            </select>
        </div>
    </div>
    
    <!-- Results -->
    <div class="ptp-cf-results">
        
        <!-- Map (toggleable on mobile) -->
        <?php if ($show_map): ?>
        <div class="ptp-cf-map-container" id="campMapContainer">
            <div id="campMap"></div>
            <button type="button" class="ptp-cf-map-close" id="closeMapBtn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        
        <button type="button" class="ptp-cf-map-toggle" id="showMapBtn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/>
            </svg>
            Show Map
        </button>
        <?php endif; ?>
        
        <!-- Camp List -->
        <div class="ptp-cf-list" id="campList">
            <div class="ptp-cf-loading">
                <div class="ptp-spinner"></div>
                <p>Finding camps near you...</p>
            </div>
        </div>
        
    </div>
    
</div>

<style>
/* Camp Finder - Mobile First */
.ptp-camp-finder {
    --cf-primary: #2563EB;
    --cf-yellow: #FCB900;
    --cf-ink: #0E0F11;
    --cf-gray-100: #F3F4F6;
    --cf-gray-200: #E5E7EB;
    --cf-gray-500: #6B7280;
    --cf-radius: 12px;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

.ptp-cf-header {
    text-align: center;
    padding: 32px 16px;
    background: linear-gradient(135deg, var(--cf-ink) 0%, #1a1b1e 100%);
    color: white;
    border-radius: var(--cf-radius);
    margin-bottom: 24px;
}

.ptp-cf-header h2 {
    font-size: 24px;
    font-weight: 700;
    margin: 0 0 8px;
}

.ptp-cf-header p {
    color: rgba(255,255,255,0.7);
    margin: 0 0 24px;
    font-size: 15px;
}

/* Search Bar */
.ptp-cf-search {
    display: flex;
    flex-direction: column;
    gap: 12px;
    max-width: 600px;
    margin: 0 auto 20px;
}

.ptp-cf-search-input {
    display: flex;
    align-items: center;
    background: white;
    border-radius: 8px;
    padding: 0 12px;
    gap: 8px;
}

.ptp-cf-search-input svg {
    color: var(--cf-gray-500);
    flex-shrink: 0;
}

.ptp-cf-search-input input {
    flex: 1;
    border: none;
    padding: 14px 0;
    font-size: 16px;
    background: transparent;
    min-width: 0;
}

.ptp-cf-search-input input:focus {
    outline: none;
}

.ptp-cf-location-btn {
    background: var(--cf-gray-100);
    border: none;
    width: 36px;
    height: 36px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: var(--cf-gray-500);
    flex-shrink: 0;
}

.ptp-cf-location-btn:hover {
    background: var(--cf-gray-200);
    color: var(--cf-primary);
}

.ptp-cf-radius {
    display: none;
}

.ptp-cf-radius select {
    width: 100%;
    padding: 12px 16px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    background: white;
    cursor: pointer;
}

.ptp-cf-search .ptp-btn {
    padding: 14px 24px;
    font-size: 16px;
    width: 100%;
}

/* Filters */
.ptp-cf-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: center;
}

.ptp-cf-filters select {
    padding: 8px 12px;
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 6px;
    background: rgba(255,255,255,0.1);
    color: white;
    font-size: 14px;
    cursor: pointer;
}

.ptp-cf-filters select:focus {
    outline: none;
    border-color: var(--cf-yellow);
}

.ptp-cf-filters select option {
    background: var(--cf-ink);
    color: white;
}

/* Results */
.ptp-cf-results {
    position: relative;
}

/* Map */
.ptp-cf-map-container {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 9999;
    background: white;
}

.ptp-cf-map-container.active {
    display: block;
}

#campMap {
    width: 100%;
    height: 100%;
}

.ptp-cf-map-close {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 44px;
    height: 44px;
    background: white;
    border: none;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
}

.ptp-cf-map-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 14px;
    background: var(--cf-gray-100);
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
    margin-bottom: 16px;
}

.ptp-cf-map-toggle:hover {
    background: var(--cf-gray-200);
}

/* Camp List */
.ptp-cf-list {
    display: grid;
    gap: 16px;
}

.ptp-cf-loading {
    text-align: center;
    padding: 48px 16px;
    color: var(--cf-gray-500);
}

.ptp-cf-loading .ptp-spinner {
    width: 32px;
    height: 32px;
    border: 3px solid var(--cf-gray-200);
    border-top-color: var(--cf-primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 12px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Camp Card */
.ptp-camp-card {
    display: flex;
    flex-direction: column;
    background: white;
    border: 1px solid var(--cf-gray-200);
    border-radius: var(--cf-radius);
    overflow: hidden;
    text-decoration: none;
    color: inherit;
    transition: box-shadow 0.2s, transform 0.2s;
}

.ptp-camp-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.ptp-camp-card-image {
    position: relative;
    height: 140px;
    overflow: hidden;
}

.ptp-camp-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.ptp-camp-card-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: var(--cf-yellow);
    color: var(--cf-ink);
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.ptp-camp-card-distance {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
}

.ptp-camp-card-content {
    padding: 16px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.ptp-camp-card-dates {
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--cf-primary);
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 8px;
}

.ptp-camp-card-title {
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 8px;
    line-height: 1.3;
}

.ptp-camp-card-location {
    display: flex;
    align-items: flex-start;
    gap: 6px;
    color: var(--cf-gray-500);
    font-size: 14px;
    margin-bottom: 12px;
}

.ptp-camp-card-location svg {
    flex-shrink: 0;
    margin-top: 2px;
}

.ptp-camp-card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 12px;
}

.ptp-camp-card-tag {
    background: var(--cf-gray-100);
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    color: var(--cf-gray-500);
}

.ptp-camp-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: auto;
    padding-top: 12px;
    border-top: 1px solid var(--cf-gray-100);
}

.ptp-camp-card-price {
    font-size: 18px;
    font-weight: 700;
    color: var(--cf-ink);
}

.ptp-camp-card-price span {
    font-size: 13px;
    font-weight: 400;
    color: var(--cf-gray-500);
}

.ptp-camp-card-spots {
    font-size: 13px;
    color: var(--cf-gray-500);
}

.ptp-camp-card-spots.low {
    color: #DC2626;
    font-weight: 500;
}

/* No Results */
.ptp-cf-no-results {
    text-align: center;
    padding: 48px 16px;
    background: var(--cf-gray-100);
    border-radius: var(--cf-radius);
}

.ptp-cf-no-results svg {
    margin-bottom: 16px;
    color: var(--cf-gray-500);
}

.ptp-cf-no-results h3 {
    font-size: 18px;
    margin: 0 0 8px;
}

.ptp-cf-no-results p {
    color: var(--cf-gray-500);
    margin: 0;
}

/* TABLET & UP */
@media (min-width: 640px) {
    .ptp-cf-header {
        padding: 48px 24px;
    }
    
    .ptp-cf-header h2 {
        font-size: 32px;
    }
    
    .ptp-cf-search {
        flex-direction: row;
    }
    
    .ptp-cf-search-input {
        flex: 1;
    }
    
    .ptp-cf-radius {
        display: block;
    }
    
    .ptp-cf-radius select {
        width: auto;
    }
    
    .ptp-cf-search .ptp-btn {
        width: auto;
    }
    
    .ptp-cf-list {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .ptp-camp-card {
        flex-direction: row;
    }
    
    .ptp-camp-card-image {
        width: 200px;
        height: auto;
        flex-shrink: 0;
    }
}

/* DESKTOP */
@media (min-width: 1024px) {
    .ptp-cf-results {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 24px;
    }
    
    .ptp-cf-map-container {
        display: block;
        position: sticky;
        top: 24px;
        height: 600px;
        border-radius: var(--cf-radius);
        overflow: hidden;
    }
    
    .ptp-cf-map-close {
        display: none;
    }
    
    .ptp-cf-map-toggle {
        display: none;
    }
    
    .ptp-cf-list {
        grid-template-columns: 1fr;
        order: -1;
    }
    
    .ptp-camp-card-image {
        width: 180px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const campFinder = {
        camps: [],
        map: null,
        markers: [],
        userLocation: null,
        
        init: function() {
            this.bindEvents();
            this.loadCamps();
        },
        
        bindEvents: function() {
            // Search button
            document.getElementById('searchCamps')?.addEventListener('click', () => this.searchByZip());
            
            // Enter key on ZIP input
            document.getElementById('campZipSearch')?.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') this.searchByZip();
            });
            
            // Use my location
            document.getElementById('useMyLocationCamp')?.addEventListener('click', () => this.useGeolocation());
            
            // Filters
            ['filterCampState', 'filterCampTime', 'filterCampMonth'].forEach(id => {
                document.getElementById(id)?.addEventListener('change', () => this.loadCamps());
            });
            
            // Map toggle (mobile)
            document.getElementById('showMapBtn')?.addEventListener('click', () => {
                document.getElementById('campMapContainer')?.classList.add('active');
            });
            
            document.getElementById('closeMapBtn')?.addEventListener('click', () => {
                document.getElementById('campMapContainer')?.classList.remove('active');
            });
        },
        
        searchByZip: function() {
            const zip = document.getElementById('campZipSearch')?.value;
            if (!zip || zip.length !== 5) {
                alert('Please enter a valid 5-digit ZIP code');
                return;
            }
            
            // Geocode ZIP to lat/lng
            this.geocodeZip(zip);
        },
        
        geocodeZip: function(zip) {
            if (!window.google || !google.maps) {
                console.error('Google Maps not loaded');
                this.loadCamps();
                return;
            }
            
            const geocoder = new google.maps.Geocoder();
            geocoder.geocode({ address: zip + ', USA' }, (results, status) => {
                if (status === 'OK' && results[0]) {
                    this.userLocation = {
                        lat: results[0].geometry.location.lat(),
                        lng: results[0].geometry.location.lng()
                    };
                    this.loadCamps();
                } else {
                    alert('Could not find location for this ZIP code');
                }
            });
        },
        
        useGeolocation: function() {
            if (!navigator.geolocation) {
                alert('Geolocation is not supported by your browser');
                return;
            }
            
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    this.loadCamps();
                },
                () => {
                    alert('Unable to get your location');
                }
            );
        },
        
        async loadCamps() {
            const list = document.getElementById('campList');
            list.innerHTML = '<div class="ptp-cf-loading"><div class="ptp-spinner"></div><p>Finding camps...</p></div>';
            
            const params = new URLSearchParams();
            
            const state = document.getElementById('filterCampState')?.value;
            if (state) params.set('state', state);
            
            const month = document.getElementById('filterCampMonth')?.value;
            if (month) params.set('start_date', month + '-01');
            
            if (this.userLocation) {
                params.set('lat', this.userLocation.lat);
                params.set('lng', this.userLocation.lng);
                params.set('radius', document.getElementById('campRadius')?.value || 25);
            }
            
            try {
                const response = await fetch(ptpTraining.rest_url + 'camps?' + params.toString());
                this.camps = await response.json();
                
                // Filter by time slot client-side
                const timeSlot = document.getElementById('filterCampTime')?.value;
                if (timeSlot) {
                    this.camps = this.camps.filter(c => c.time_slot === timeSlot);
                }
                
                this.renderCamps();
                this.updateMap();
                
            } catch (error) {
                console.error('Error loading camps:', error);
                list.innerHTML = '<div class="ptp-cf-no-results"><p>Error loading camps. Please try again.</p></div>';
            }
        },
        
        renderCamps: function() {
            const list = document.getElementById('campList');
            
            if (this.camps.length === 0) {
                list.innerHTML = `
                    <div class="ptp-cf-no-results">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                        <h3>No camps found</h3>
                        <p>Try adjusting your search filters or expanding your radius.</p>
                    </div>
                `;
                return;
            }
            
            list.innerHTML = this.camps.map(camp => this.renderCampCard(camp)).join('');
        },
        
        renderCampCard: function(camp) {
            const startDate = new Date(camp.start_date + 'T00:00:00');
            const endDate = camp.end_date ? new Date(camp.end_date + 'T00:00:00') : startDate;
            
            const dateStr = startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + 
                (camp.end_date ? ' - ' + endDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : '');
            
            const timeLabels = {
                'morning': 'Morning',
                'afternoon': 'Afternoon',
                'full_day': 'Full Day',
                'evening': 'Evening'
            };
            
            let spotsClass = '';
            let spotsText = 'Spots available';
            if (camp.spots_remaining !== null) {
                if (camp.spots_remaining <= 5) {
                    spotsClass = 'low';
                    spotsText = `Only ${camp.spots_remaining} spots left!`;
                } else {
                    spotsText = `${camp.spots_remaining} spots left`;
                }
            }
            
            return `
                <a href="${camp.url}" class="ptp-camp-card">
                    <div class="ptp-camp-card-image">
                        <img src="${camp.image || 'https://ptpsummercamps.com/wp-content/uploads/2025/12/BG7A1899.jpg'}" alt="${camp.name}" loading="lazy" />
                        ${camp.is_on_sale ? '<span class="ptp-camp-card-badge">SALE</span>' : ''}
                        ${camp.distance ? `<span class="ptp-camp-card-distance">${camp.distance.toFixed(1)} mi</span>` : ''}
                    </div>
                    <div class="ptp-camp-card-content">
                        <div class="ptp-camp-card-dates">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            ${dateStr}
                        </div>
                        <h3 class="ptp-camp-card-title">${camp.name}</h3>
                        <div class="ptp-camp-card-location">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                            </svg>
                            <span>${camp.location.name || ''} ${camp.location.city}, ${camp.location.state}</span>
                        </div>
                        <div class="ptp-camp-card-meta">
                            ${camp.time_slot ? `<span class="ptp-camp-card-tag">${timeLabels[camp.time_slot] || camp.time_slot}</span>` : ''}
                            ${camp.age_groups ? `<span class="ptp-camp-card-tag">${camp.age_groups}</span>` : ''}
                        </div>
                        <div class="ptp-camp-card-footer">
                            <div class="ptp-camp-card-price">
                                $${parseFloat(camp.price).toFixed(0)}
                                ${camp.sale_price ? `<span><s>$${parseFloat(camp.regular_price).toFixed(0)}</s></span>` : ''}
                            </div>
                            <span class="ptp-camp-card-spots ${spotsClass}">${spotsText}</span>
                        </div>
                    </div>
                </a>
            `;
        },
        
        updateMap: function() {
            if (!window.google || !google.maps) return;
            
            const container = document.getElementById('campMapContainer');
            if (!container) return;
            
            if (!this.map) {
                const center = this.userLocation || { lat: 40.0, lng: -75.5 };
                this.map = new google.maps.Map(document.getElementById('campMap'), {
                    center: center,
                    zoom: 9,
                    styles: [{ featureType: 'poi', stylers: [{ visibility: 'off' }] }]
                });
            }
            
            // Clear existing markers
            this.markers.forEach(m => m.setMap(null));
            this.markers = [];
            
            const bounds = new google.maps.LatLngBounds();
            
            this.camps.forEach(camp => {
                if (!camp.location.lat || !camp.location.lng) return;
                
                const marker = new google.maps.Marker({
                    position: { lat: parseFloat(camp.location.lat), lng: parseFloat(camp.location.lng) },
                    map: this.map,
                    title: camp.name
                });
                
                const infoWindow = new google.maps.InfoWindow({
                    content: `
                        <div style="max-width: 200px;">
                            <strong>${camp.name}</strong><br/>
                            <span style="color: #666;">${camp.location.city}, ${camp.location.state}</span><br/>
                            <strong style="color: #2563EB;">$${parseFloat(camp.price).toFixed(0)}</strong>
                            <br/><a href="${camp.url}" style="color: #2563EB;">View Details →</a>
                        </div>
                    `
                });
                
                marker.addListener('click', () => infoWindow.open(this.map, marker));
                
                this.markers.push(marker);
                bounds.extend(marker.getPosition());
            });
            
            if (this.markers.length > 0) {
                this.map.fitBounds(bounds);
            }
        }
    };
    
    campFinder.init();
});
</script>
