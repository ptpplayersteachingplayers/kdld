<?php
/**
 * Upcoming Clinics Grid
 * Compact display of training clinics
 */

if (!defined('ABSPATH')) exit;

$limit = isset($atts['limit']) ? intval($atts['limit']) : 6;
$style = isset($atts['style']) ? $atts['style'] : 'grid';
?>

<div class="ptp-clinics-grid" id="ptpClinicsGrid">
    <div class="ptp-clinics-loading">
        <div class="ptp-spinner"></div>
    </div>
</div>

<style>
.ptp-clinics-grid {
    --cg-primary: #2563EB;
    --cg-yellow: #FCB900;
    --cg-ink: #0E0F11;
    --cg-gray-100: #F3F4F6;
    --cg-gray-200: #E5E7EB;
    --cg-gray-500: #6B7280;
}

.ptp-clinics-loading {
    text-align: center;
    padding: 32px;
}

.ptp-clinics-list {
    display: grid;
    gap: 16px;
}

.ptp-clinic-card {
    display: flex;
    background: white;
    border: 1px solid var(--cg-gray-200);
    border-radius: 12px;
    overflow: hidden;
    text-decoration: none;
    color: inherit;
    transition: box-shadow 0.2s, transform 0.2s;
}

.ptp-clinic-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transform: translateY(-2px);
}

.ptp-clinic-card-date {
    width: 70px;
    background: var(--cg-primary);
    color: white;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 12px;
    flex-shrink: 0;
}

.ptp-clinic-card-month {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
    opacity: 0.8;
}

.ptp-clinic-card-day {
    font-size: 28px;
    font-weight: 700;
    line-height: 1;
}

.ptp-clinic-card-content {
    flex: 1;
    padding: 12px 16px;
    min-width: 0;
}

.ptp-clinic-card-title {
    font-size: 15px;
    font-weight: 600;
    margin: 0 0 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.ptp-clinic-card-time {
    font-size: 13px;
    color: var(--cg-gray-500);
    margin-bottom: 4px;
}

.ptp-clinic-card-location {
    font-size: 13px;
    color: var(--cg-gray-500);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.ptp-clinic-card-price {
    padding: 12px 16px;
    display: flex;
    align-items: center;
    font-weight: 700;
    font-size: 16px;
    color: var(--cg-ink);
    flex-shrink: 0;
}

/* Grid Style */
.ptp-clinics-grid.style-grid .ptp-clinics-list {
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
}

.ptp-clinics-grid.style-grid .ptp-clinic-card {
    flex-direction: column;
}

.ptp-clinics-grid.style-grid .ptp-clinic-card-date {
    width: 100%;
    flex-direction: row;
    gap: 8px;
    padding: 12px 16px;
    justify-content: flex-start;
}

.ptp-clinics-grid.style-grid .ptp-clinic-card-content {
    padding: 16px;
}

.ptp-clinics-grid.style-grid .ptp-clinic-card-price {
    border-top: 1px solid var(--cg-gray-100);
    justify-content: space-between;
}

/* Empty State */
.ptp-clinics-empty {
    text-align: center;
    padding: 32px;
    background: var(--cg-gray-100);
    border-radius: 12px;
}

.ptp-clinics-empty h3 {
    margin: 0 0 8px;
    font-size: 16px;
}

.ptp-clinics-empty p {
    margin: 0;
    color: var(--cg-gray-500);
    font-size: 14px;
}

@media (max-width: 639px) {
    .ptp-clinic-card-date {
        width: 60px;
        padding: 8px;
    }
    
    .ptp-clinic-card-day {
        font-size: 22px;
    }
    
    .ptp-clinic-card-content {
        padding: 10px 12px;
    }
    
    .ptp-clinic-card-price {
        padding: 10px 12px;
        font-size: 14px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    const container = document.getElementById('ptpClinicsGrid');
    const limit = <?php echo $limit; ?>;
    const style = '<?php echo $style; ?>';
    
    container.classList.add('style-' + style);
    
    try {
        const response = await fetch(ptpTraining.rest_url + 'clinics?limit=' + limit);
        const clinics = await response.json();
        
        if (clinics.length === 0) {
            container.innerHTML = `
                <div class="ptp-clinics-empty">
                    <h3>No Upcoming Clinics</h3>
                    <p>Check back soon for new training clinics.</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = `
            <div class="ptp-clinics-list">
                ${clinics.map(clinic => {
                    const date = new Date(clinic.date + 'T00:00:00');
                    const month = date.toLocaleDateString('en-US', { month: 'short' });
                    const day = date.getDate();
                    
                    return `
                        <a href="${clinic.url}" class="ptp-clinic-card">
                            <div class="ptp-clinic-card-date">
                                <span class="ptp-clinic-card-month">${month}</span>
                                <span class="ptp-clinic-card-day">${day}</span>
                            </div>
                            <div class="ptp-clinic-card-content">
                                <h3 class="ptp-clinic-card-title">${clinic.name}</h3>
                                <div class="ptp-clinic-card-time">${clinic.time || ''} ${clinic.duration ? '(' + clinic.duration + ')' : ''}</div>
                                <div class="ptp-clinic-card-location">${clinic.location.city}, ${clinic.location.state}</div>
                            </div>
                            <div class="ptp-clinic-card-price">
                                $${parseFloat(clinic.price).toFixed(0)}
                            </div>
                        </a>
                    `;
                }).join('')}
            </div>
        `;
        
    } catch (error) {
        console.error('Error loading clinics:', error);
        container.innerHTML = '<p style="text-align: center; color: #666;">Error loading clinics.</p>';
    }
});
</script>
