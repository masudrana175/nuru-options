/* global jQuery, nuru_options_data */
jQuery(function ($) {
    'use strict';

    // ----------------------------------------------------------------
    // 1. Initialise Select2
    // ----------------------------------------------------------------
    function initSelect2() {
        if (!$.fn.select2) { return; }
        $('.nuru-post-select2').each(function () {
            if (!$(this).hasClass('select2-hidden-accessible')) {
                $(this).select2({
                    placeholder:  '— Select goddesses —',
                    allowClear:   true,
                    width:        '100%',
                    dropdownCssClass: 'nuru-select2-dropdown',
                });
            }
        });
    }
    initSelect2();

    // ----------------------------------------------------------------
    // 2. Collapsible slots
    // ----------------------------------------------------------------
    $(document).on('click', '.nuru-collapsible-header', function (e) {
        e.stopPropagation();
        var $header = $(this);
        var $body   = $header.next('.nuru-collapsible-body');
        var isOpen  = $header.hasClass('is-open');

        if (isOpen) {
            $header.removeClass('is-open');
            $body.slideUp(180);
        } else {
            $header.addClass('is-open');
            $body.slideDown(180, function () {
                // Re-init Select2 inside newly-visible area (width fix)
                $body.find('.nuru-post-select2').each(function () {
                    if ($.fn.select2 && $(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2('destroy');
                    }
                });
                initSelect2();
            });
        }

        // Keep the badge count up to date after expanding
        updateBadge($header);

        // Keep "Expand/Collapse All" button label in sync
        syncToggleBtn($header.closest('.nuru-location-card'));
    });

    // ----------------------------------------------------------------
    // 3. Expand / Collapse All (per location card)
    // ----------------------------------------------------------------
    $(document).on('click', '.nuru-toggle-all', function () {
        var $btn     = $(this);
        var $card    = $btn.closest('.nuru-location-card');
        var $headers = $card.find('.nuru-collapsible-header');
        var allOpen  = $headers.filter('.is-open').length === $headers.length;

        if (allOpen) {
            // Collapse all
            $headers.addClass('is-open').each(function () {
                $(this).removeClass('is-open')
                       .next('.nuru-collapsible-body').slideUp(150);
            });
            $btn.text('Expand All').attr('data-open', '0');
        } else {
            // Expand all
            $headers.not('.is-open').each(function () {
                $(this).addClass('is-open')
                       .next('.nuru-collapsible-body').slideDown(150, function () {
                           $(this).find('.nuru-post-select2').each(function () {
                               if ($.fn.select2 && $(this).hasClass('select2-hidden-accessible')) {
                                   $(this).select2('destroy');
                               }
                           });
                           initSelect2();
                       });
            });
            $btn.text('Collapse All').attr('data-open', '1');
        }
    });

    // ----------------------------------------------------------------
    // 4. Badge update helper (shows # selected on collapsed header)
    // ----------------------------------------------------------------
    function updateBadge($header) {
        var $body  = $header.next('.nuru-collapsible-body');
        var $badge = $header.find('.nuru-badge');
        var count  = 0;

        $body.find('select.nuru-post-select2').each(function () {
            var val = $(this).val();
            if (val && val.length) { count += val.length; }
        });

        if (count > 0) {
            if ($badge.length) {
                $badge.text(count);
            } else {
                $header.find('.nuru-slot-label').append('<span class="nuru-badge">' + count + '</span>');
            }
        } else {
            $badge.remove();
        }
    }

    // Refresh all badges on change
    $(document).on('change', '.nuru-post-select2', function () {
        var $header = $(this).closest('.nuru-collapsible-body').prev('.nuru-collapsible-header');
        if ($header.length) { updateBadge($header); }
    });

    // ----------------------------------------------------------------
    // 5. Sync "Expand/Collapse All" button text helper
    // ----------------------------------------------------------------
    function syncToggleBtn($card) {
        var $btn     = $card.find('.nuru-toggle-all');
        var $headers = $card.find('.nuru-collapsible-header');
        if (!$btn.length || !$headers.length) { return; }

        var openCount = $headers.filter('.is-open').length;
        if (openCount === $headers.length) {
            $btn.text('Collapse All').attr('data-open', '1');
        } else {
            $btn.text('Expand All').attr('data-open', '0');
        }
    }

    // ----------------------------------------------------------------
    // 6. AJAX form save
    // ----------------------------------------------------------------
    $(document).on('submit', '.nuru-ajax-form', function (e) {
        e.preventDefault();

        var $form      = $(this);
        var action     = $form.data('action');
        var optionName = $form.data('option');
        var $notice    = $($form.data('notice'));
        var $btn       = $($form.data('savebtn'));

        // Build POST data — include every select, even empty ones
        var postData         = {};
        postData[optionName] = {};

        $form.find('select.nuru-post-select2').each(function () {
            var nameAttr = $(this).attr('name');
            var match    = nameAttr.match(/\[([^\]]+)\]/);
            if (!match) { return; }
            var fieldId  = match[1];
            var vals     = $(this).val();
            postData[optionName][fieldId] = vals ? vals.join(',') : '';
        });

        postData['action']     = action;
        postData['nuru_nonce'] = $form.find('#nuru_nonce').val();

        // Loading state
        $btn.prop('disabled', true)
            .find('.nuru-btn-text').hide().end()
            .find('.nuru-btn-spinner').show();
        $notice.hide().removeClass('is-success is-error');

        $.ajax({
            url:  nuru_options_data.ajax_url,
            type: 'POST',
            data: postData,
            success: function (response) {
                if (response && response.success) {
                    $notice.addClass('is-success')
                           .html('<span class="dashicons dashicons-yes-alt"></span> <strong>' + response.data.message + '</strong>')
                           .show();
                    // Fade out after 4 s
                    setTimeout(function () { $notice.fadeOut(400); }, 4000);
                } else {
                    var msg = (response && response.data && response.data.message)
                        ? response.data.message : 'An unknown error occurred.';
                    $notice.addClass('is-error')
                           .html('<span class="dashicons dashicons-warning"></span> <strong>Error:</strong> ' + msg)
                           .show();
                }
            },
            error: function (xhr, status, error) {
                $notice.addClass('is-error')
                       .html('<span class="dashicons dashicons-warning"></span> <strong>Request failed</strong> (' + error + '). Please try again.')
                       .show();
            },
            complete: function () {
                $btn.prop('disabled', false)
                    .find('.nuru-btn-spinner').hide().end()
                    .find('.nuru-btn-text').show();

                // Scroll notice into view
                if ($notice.is(':visible')) {
                    $('html, body').animate({ scrollTop: $notice.offset().top - 80 }, 250);
                }
            },
        });
    });
});
