/**
 * WP Fetch dashboard widget JavaScript.
 *
 * @package WP_Fetch
 */

/* global jQuery, wpFetchWidget */
(function ($) {
    'use strict';

    $(document).on('click', '.wp-fetch-widget-refresh', function () {
        var $btn = $(this);
        var name = $btn.data('name');
        var $msg = $('#wp-fetch-widget-message');

        $btn.prop('disabled', true).text('...');

        $.post(wpFetchWidget.ajaxUrl, {
            action: 'wp_fetch_widget_refresh',
            nonce: wpFetchWidget.nonce,
            name: name
        }, function (response) {
            $btn.prop('disabled', false).text('Refresh');
            if (response.success) {
                $msg.html('<p style="color:#46b450;">' + response.data + '</p>');
            } else {
                $msg.html('<p style="color:#dc3232;">' + (response.data || 'Refresh failed.') + '</p>');
            }
            setTimeout(function () { $msg.empty(); }, 5000);
        });
    });
})(jQuery);
