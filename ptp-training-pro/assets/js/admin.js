/**
 * PTP Training Pro - Admin JavaScript
 */

(function($) {
    'use strict';

    const PTPAdmin = {
        
        init: function() {
            this.bindEvents();
            this.initTabs();
        },
        
        bindEvents: function() {
            // Session status updates
            $(document).on('change', '.ptp-session-status-select', this.updateSessionStatus);
            $(document).on('change', '.ptp-payment-status-select', this.updatePaymentStatus);

            // Approve trainer
            $(document).on('click', '.ptp-approve-trainer', this.approveTrainer);
            $(document).on('click', '.ptp-reject-trainer', this.rejectTrainer);

            // Process payout
            $(document).on('click', '.ptp-process-payout', this.processPayout);
            $(document).on('click', '.ptp-bulk-payout', this.bulkPayout);

            // Confirm dangerous actions
            $(document).on('click', '.ptp-confirm-action', this.confirmAction);

            // Settings tabs
            $(document).on('click', '.ptp-settings-tab', this.switchTab);

            // Search/filter
            $(document).on('input', '.ptp-admin-search', this.debounce(this.filterTable, 300));
            $(document).on('change', '.ptp-admin-filter', this.filterTable);

            // Quick view session
            $(document).on('click', '.ptp-view-session', this.viewSession);
            $(document).on('click', '.ptp-modal-close, .ptp-modal-overlay', this.closeModal);
        },
        
        initTabs: function() {
            const hash = window.location.hash;
            if (hash) {
                const tab = $(`.ptp-settings-tab[data-tab="${hash.substring(1)}"]`);
                if (tab.length) {
                    tab.click();
                }
            }
        },

        updateSessionStatus: function(e) {
            const select = $(this);
            const sessionId = select.data('session-id');
            const newStatus = select.val();
            const originalValue = select.data('original');

            select.prop('disabled', true);

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'ptp_update_session_status',
                    session_id: sessionId,
                    status: newStatus,
                    nonce: ptpAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        PTPAdmin.showNotice('Session status updated!', 'success');
                        select.data('original', newStatus);
                        // Update the status badge in the row
                        const row = select.closest('tr');
                        row.attr('data-status', newStatus);
                        row.find('.ptp-status')
                            .removeClass('ptp-status-requested ptp-status-confirmed ptp-status-completed ptp-status-cancelled ptp-status-no_show')
                            .addClass('ptp-status-' + newStatus)
                            .text(newStatus.charAt(0).toUpperCase() + newStatus.slice(1).replace('_', ' '));
                    } else {
                        PTPAdmin.showNotice(response.data || 'Error updating status', 'error');
                        select.val(originalValue);
                    }
                    select.prop('disabled', false);
                },
                error: function() {
                    PTPAdmin.showNotice('Connection error', 'error');
                    select.val(originalValue);
                    select.prop('disabled', false);
                }
            });
        },

        updatePaymentStatus: function(e) {
            const select = $(this);
            const sessionId = select.data('session-id');
            const newStatus = select.val();
            const originalValue = select.data('original');

            select.prop('disabled', true);

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'ptp_update_payment_status',
                    session_id: sessionId,
                    payment_status: newStatus,
                    nonce: ptpAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        PTPAdmin.showNotice('Payment status updated!', 'success');
                        select.data('original', newStatus);
                    } else {
                        PTPAdmin.showNotice(response.data || 'Error updating payment status', 'error');
                        select.val(originalValue);
                    }
                    select.prop('disabled', false);
                },
                error: function() {
                    PTPAdmin.showNotice('Connection error', 'error');
                    select.val(originalValue);
                    select.prop('disabled', false);
                }
            });
        },

        viewSession: function(e) {
            e.preventDefault();
            const sessionId = $(this).data('session-id');

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'ptp_get_session_details',
                    session_id: sessionId,
                    nonce: ptpAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        PTPAdmin.showSessionModal(response.data);
                    } else {
                        PTPAdmin.showNotice(response.data || 'Error loading session', 'error');
                    }
                },
                error: function() {
                    PTPAdmin.showNotice('Connection error', 'error');
                }
            });
        },

        showSessionModal: function(session) {
            const modal = $(`
                <div class="ptp-modal-overlay">
                    <div class="ptp-modal">
                        <div class="ptp-modal-header">
                            <h2>Session #${session.id}</h2>
                            <button type="button" class="ptp-modal-close">&times;</button>
                        </div>
                        <div class="ptp-modal-body">
                            <div class="ptp-session-detail">
                                <strong>Customer:</strong> ${session.customer_name || 'N/A'}
                            </div>
                            <div class="ptp-session-detail">
                                <strong>Player:</strong> ${session.player_name || 'N/A'} (Age: ${session.player_age || 'N/A'})
                            </div>
                            <div class="ptp-session-detail">
                                <strong>Trainer:</strong> ${session.trainer_name || 'N/A'}
                            </div>
                            <div class="ptp-session-detail">
                                <strong>Date/Time:</strong> ${session.session_date || 'N/A'} at ${session.session_time || 'N/A'}
                            </div>
                            <div class="ptp-session-detail">
                                <strong>Duration:</strong> ${session.duration || 60} minutes
                            </div>
                            <div class="ptp-session-detail">
                                <strong>Location:</strong> ${session.location || 'TBD'}
                            </div>
                            <div class="ptp-session-detail">
                                <strong>Price:</strong> $${session.price || '0.00'}
                            </div>
                            <div class="ptp-session-detail">
                                <strong>Status:</strong> <span class="ptp-status ptp-status-${session.status}">${session.status}</span>
                            </div>
                            <div class="ptp-session-detail">
                                <strong>Payment:</strong> <span class="ptp-status ptp-status-${session.payment_status}">${session.payment_status}</span>
                            </div>
                            ${session.notes ? `<div class="ptp-session-detail"><strong>Notes:</strong> ${session.notes}</div>` : ''}
                        </div>
                    </div>
                </div>
            `);
            $('body').append(modal);
        },

        closeModal: function(e) {
            if ($(e.target).hasClass('ptp-modal-overlay') || $(e.target).hasClass('ptp-modal-close')) {
                $('.ptp-modal-overlay').remove();
            }
        },

        approveTrainer: function(e) {
            e.preventDefault();
            
            const btn = $(this);
            const applicationId = btn.data('id');
            
            if (!confirm('Approve this trainer application? They will receive an email with login instructions.')) {
                return;
            }
            
            btn.prop('disabled', true).text('Approving...');
            
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'ptp_approve_trainer',
                    application_id: applicationId,
                    nonce: ptpAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        PTPAdmin.showNotice('Trainer approved successfully!', 'success');
                        btn.closest('tr, .ptp-admin-trainer-card').fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        PTPAdmin.showNotice(response.data || 'Error approving trainer', 'error');
                        btn.prop('disabled', false).text('Approve');
                    }
                },
                error: function() {
                    PTPAdmin.showNotice('Connection error', 'error');
                    btn.prop('disabled', false).text('Approve');
                }
            });
        },
        
        rejectTrainer: function(e) {
            e.preventDefault();
            
            const btn = $(this);
            const applicationId = btn.data('id');
            
            const reason = prompt('Rejection reason (optional):');
            if (reason === null) return; // Cancelled
            
            btn.prop('disabled', true).text('Rejecting...');
            
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'ptp_reject_trainer',
                    application_id: applicationId,
                    reason: reason,
                    nonce: ptpAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        PTPAdmin.showNotice('Application rejected', 'success');
                        btn.closest('tr, .ptp-admin-trainer-card').fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        PTPAdmin.showNotice(response.data || 'Error rejecting application', 'error');
                        btn.prop('disabled', false).text('Reject');
                    }
                },
                error: function() {
                    PTPAdmin.showNotice('Connection error', 'error');
                    btn.prop('disabled', false).text('Reject');
                }
            });
        },
        
        processPayout: function(e) {
            e.preventDefault();
            
            const btn = $(this);
            const payoutId = btn.data('id');
            
            if (!confirm('Process this payout? This will transfer funds to the trainer.')) {
                return;
            }
            
            btn.prop('disabled', true).text('Processing...');
            
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'ptp_process_payout',
                    payout_id: payoutId,
                    nonce: ptpAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        PTPAdmin.showNotice('Payout processed!', 'success');
                        btn.closest('tr').find('.ptp-status')
                            .removeClass('ptp-status-pending')
                            .addClass('ptp-status-paid')
                            .text('Paid');
                        btn.remove();
                    } else {
                        PTPAdmin.showNotice(response.data || 'Error processing payout', 'error');
                        btn.prop('disabled', false).text('Process');
                    }
                },
                error: function() {
                    PTPAdmin.showNotice('Connection error', 'error');
                    btn.prop('disabled', false).text('Process');
                }
            });
        },
        
        bulkPayout: function(e) {
            e.preventDefault();
            
            const selected = $('.ptp-payout-checkbox:checked');
            if (selected.length === 0) {
                alert('Please select at least one payout to process');
                return;
            }
            
            if (!confirm(`Process ${selected.length} payout(s)?`)) {
                return;
            }
            
            const payoutIds = [];
            selected.each(function() {
                payoutIds.push($(this).val());
            });
            
            const btn = $(this);
            btn.prop('disabled', true).text('Processing...');
            
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'ptp_bulk_payout',
                    payout_ids: payoutIds,
                    nonce: ptpAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        PTPAdmin.showNotice(`${response.data.processed} payout(s) processed!`, 'success');
                        location.reload();
                    } else {
                        PTPAdmin.showNotice(response.data || 'Error processing payouts', 'error');
                        btn.prop('disabled', false).text('Process Selected');
                    }
                },
                error: function() {
                    PTPAdmin.showNotice('Connection error', 'error');
                    btn.prop('disabled', false).text('Process Selected');
                }
            });
        },
        
        confirmAction: function(e) {
            const message = $(this).data('confirm') || 'Are you sure?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        },
        
        switchTab: function(e) {
            e.preventDefault();
            
            const tab = $(this);
            const targetId = tab.data('tab');
            
            // Update tab states
            $('.ptp-settings-tab').removeClass('active');
            tab.addClass('active');
            
            // Show/hide content
            $('.ptp-settings-content').hide();
            $(`#${targetId}`).show();
            
            // Update URL hash
            window.location.hash = targetId;
        },
        
        filterTable: function() {
            const search = $('.ptp-admin-search').val().toLowerCase();
            const status = $('.ptp-admin-filter[name="status"]').val();
            
            $('.ptp-admin-table tbody tr').each(function() {
                const row = $(this);
                const text = row.text().toLowerCase();
                const rowStatus = row.data('status');
                
                let show = true;
                
                if (search && text.indexOf(search) === -1) {
                    show = false;
                }
                
                if (status && rowStatus !== status) {
                    show = false;
                }
                
                row.toggle(show);
            });
        },
        
        showNotice: function(message, type) {
            const notice = $(`
                <div class="ptp-admin-notice ptp-admin-notice-${type}">
                    ${message}
                </div>
            `);
            
            $('.ptp-admin-wrap').prepend(notice);
            
            setTimeout(function() {
                notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        debounce: function(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }
    };
    
    $(document).ready(function() {
        PTPAdmin.init();
    });

})(jQuery);
