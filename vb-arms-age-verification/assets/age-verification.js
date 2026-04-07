/**
 * VB Arms Age Verification JavaScript
 * Ad-blocker resistant version
 */

(function($) {
    'use strict';
    
    // Wait for DOM and jQuery
    if (typeof jQuery === 'undefined') {
        console.error('VB Arms Age Verification: jQuery is required');
        return;
    }
    
    $(document).ready(function() {
        // Use more specific selectors to avoid ad blocker filters
        const modal = $('#vb-arms-age-verification');
        const confirmBtn = $('#vb-arms-confirm-age');
        const exitBtn = $('#vb-arms-exit-site');
        
        // Check if modal exists
        if (modal.length === 0) {
            console.error('VB Arms Age Verification: Modal not found in DOM');
            return;
        }
        
        // Check if already verified (cookie lasts 24h in same browser; new browser = verify again)
        if (typeof vbArmsAgeVerify !== 'undefined' && getCookie(vbArmsAgeVerify.cookie_name) === 'yes') {
            return;
        }
        
        // Show modal immediately with fallback
        try {
            modal.fadeIn(300);
            $('body').addClass('vb-arms-age-modal-open');
        } catch (e) {
            // Fallback if fadeIn fails
            modal.css('display', 'flex');
            $('body').addClass('vb-arms-age-modal-open');
        }
        
        // Prevent closing modal by clicking outside or ESC key
        $(document).on('keydown', function(e) {
            if (e.keyCode === 27) { // ESC key
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });
        
        // Confirm age button — with timeout, retry, and "still verifying" feedback
        confirmBtn.on('click', function(e) {
            e.preventDefault();

            var isRetry = false;

            function doVerify() {
                confirmBtn.prop('disabled', true).text('Verifying...');

                var stillVerifyingTimer = setTimeout(function() {
                    confirmBtn.text('Still verifying…');
                }, 2000);

                $.ajax({
                    url: vbArmsAgeVerify.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'vb_arms_verify_age',
                        nonce: vbArmsAgeVerify.nonce,
                        action_type: 'confirm'
                    },
                    timeout: 5000,
                    success: function(response) {
                        clearTimeout(stillVerifyingTimer);
                        if (response.success) {
                            var days = (vbArmsAgeVerify.cookie_duration_days !== undefined) ? vbArmsAgeVerify.cookie_duration_days : 1;
                            setCookie(vbArmsAgeVerify.cookie_name, 'yes', days);
                            modal.fadeOut(300, function() {
                                $('body').removeClass('vb-arms-age-modal-open');
                            });
                        } else {
                            if (!isRetry) {
                                isRetry = true;
                                confirmBtn.text('Retrying…');
                                setTimeout(doVerify, 1500);
                            } else {
                                alert('Error verifying age. Please try again.');
                                confirmBtn.prop('disabled', false).text('Yes');
                            }
                        }
                    },
                    error: function(xhr, status) {
                        clearTimeout(stillVerifyingTimer);
                        if (!isRetry) {
                            isRetry = true;
                            confirmBtn.text('Retrying…');
                            setTimeout(doVerify, 1500);
                        } else {
                            var msg = (status === 'timeout') ? 'Verification is taking longer than usual. Please try again.' : 'Error verifying age. Please try again.';
                            alert(msg);
                            confirmBtn.prop('disabled', false).text('Yes');
                        }
                    }
                });
            }

            doVerify();
        });
        
        // Exit site button
        exitBtn.on('click', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: vbArmsAgeVerify.ajax_url,
                type: 'POST',
                data: {
                    action: 'vb_arms_verify_age',
                    nonce: vbArmsAgeVerify.nonce,
                    action_type: 'exit'
                },
                success: function(response) {
                    if (response.success && response.data.redirect) {
                        window.location.href = response.data.redirect;
                    } else {
                        // Fallback: redirect to Google
                        window.location.href = 'https://www.google.com';
                    }
                },
                error: function() {
                    // Fallback: redirect to Google
                    window.location.href = 'https://www.google.com';
                }
            });
        });
        
        // Prevent any clicks outside modal from closing it
        $('.vb-arms-age-modal-overlay').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        });
    });
    
    /**
     * Set cookie
     */
    function setCookie(name, value, days) {
        let expires = '';
        if (days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = '; expires=' + date.toUTCString();
        }
        document.cookie = name + '=' + (value || '') + expires + '; path=/; SameSite=Strict' + (location.protocol === 'https:' ? '; Secure' : '');
    }
    
    /**
     * Get cookie
     */
    function getCookie(name) {
        const nameEQ = name + '=';
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }
    
})(jQuery);
