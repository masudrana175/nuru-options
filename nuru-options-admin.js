/* global jQuery, nuru_options_data */
jQuery(function ($) {
    'use strict';

    // ----------------------------------------------------------------
    // 1. Initialise Select2 on all goddess dropdowns
    // ----------------------------------------------------------------
    if ($.fn.select2) {
        $('.nuru-post-select2').select2({
            placeholder: '— Select goddesses —',
            allowClear: true,
            width: '100%',
        });
    }

    // ----------------------------------------------------------------
    // 2. AJAX form save
    // ----------------------------------------------------------------
    $(document).on('submit', '.nuru-ajax-form', function (e) {
        e.preventDefault();

        var $form      = $(this);
        var action     = $form.data('action');
        var optionName = $form.data('option');
        var $btn       = $form.find('.nuru-save-btn');
        var $noticeId  = '#' + ($form.attr('id') === 'nuru-schedule-form'
                            ? 'nuru-schedule-notice'
                            : 'nuru-who-on-now-notice');
        var $notice    = $($noticeId);

        // ------ Build POST data, including empty selects ------
        var postData   = {};
        postData[optionName] = {};

        $form.find('select.nuru-post-select2').each(function () {
            var $select  = $(this);
            // Name pattern: optionName[field_id][]
            var nameAttr = $select.attr('name');
            var match    = nameAttr.match(/\[([^\]]+)\]/);
            if (!match) { return; }
            var fieldId  = match[1];
            var vals     = $select.val(); // array or null
            postData[optionName][fieldId] = vals ? vals.join(',') : '';
        });

        postData['action']     = action;
        postData['nuru_nonce'] = $form.find('#nuru_nonce').val();

        // ------ UI: loading state ------
        $btn.prop('disabled', true);
        $btn.find('.nuru-btn-text').hide();
        $btn.find('.nuru-btn-spinner').show();
        $notice.hide().removeClass('notice-success notice-error updated');

        // ------ AJAX call ------
        $.ajax({
            url:  nuru_options_data.ajax_url,
            type: 'POST',
            data: postData,
            success: function (response) {
                if (response && response.success) {
                    $notice
                        .addClass('notice-success updated')
                        .html('<p><strong>' + response.data.message + '</strong></p>')
                        .show();
                } else {
                    var errMsg = (response && response.data && response.data.message)
                        ? response.data.message
                        : 'An unknown error occurred.';
                    $notice
                        .addClass('notice-error')
                        .html('<p><strong>Error:</strong> ' + errMsg + '</p>')
                        .show();
                }
            },
            error: function (xhr, status, error) {
                $notice
                    .addClass('notice-error')
                    .html('<p><strong>Error:</strong> The request failed (' + error + '). Please try again.</p>')
                    .show();
            },
            complete: function () {
                // Restore button
                $btn.prop('disabled', false);
                $btn.find('.nuru-btn-spinner').hide();
                $btn.find('.nuru-btn-text').show();

                // Auto-dismiss success notice after 4 s
                if ($notice.hasClass('notice-success')) {
                    setTimeout(function () {
                        $notice.fadeOut(400);
                    }, 4000);
                }

                // Scroll notice into view
                $('html, body').animate({
                    scrollTop: $notice.offset().top - 60
                }, 300);
            },
        });
    });
});
