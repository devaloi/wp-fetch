/**
 * WP Fetch admin JavaScript.
 *
 * @package WP_Fetch
 */

/* global jQuery, wpFetchAdmin */
(function ($) {
    'use strict';

    var $form = $('#wp-fetch-source-form');
    var $message = $('#wp-fetch-form-message');
    var $formTitle = $('#wp-fetch-form-title');
    var $cancelBtn = $('.wp-fetch-cancel-edit');

    function showMessage(text, type) {
        var cls = type === 'error' ? 'notice-error' : 'notice-success';
        $message.html('<div class="notice ' + cls + ' is-dismissible"><p>' + text + '</p></div>');
    }

    $form.on('submit', function (e) {
        e.preventDefault();

        var data = {
            action: 'wp_fetch_save_source',
            nonce: wpFetchAdmin.nonce,
            name: $('#wp-fetch-name').val(),
            url: $('#wp-fetch-url').val(),
            method: $('#wp-fetch-method').val(),
            headers: $('#wp-fetch-headers').val(),
            auth_type: $('#wp-fetch-auth-type').val(),
            auth_value: $('#wp-fetch-auth-value').val(),
            cache_ttl: $('#wp-fetch-cache-ttl').val(),
            transform: $('#wp-fetch-transform').val(),
            fallback: $('#wp-fetch-fallback').val()
        };

        $.post(wpFetchAdmin.ajaxUrl, data, function (response) {
            if (response.success) {
                showMessage(response.data, 'success');
                setTimeout(function () { location.reload(); }, 1000);
            } else {
                showMessage(response.data, 'error');
            }
        });
    });

    $(document).on('click', '.wp-fetch-delete-source', function () {
        var name = $(this).data('name');
        if (!confirm('Delete source "' + name + '"?')) {
            return;
        }

        $.post(wpFetchAdmin.ajaxUrl, {
            action: 'wp_fetch_delete_source',
            nonce: wpFetchAdmin.nonce,
            name: name
        }, function (response) {
            if (response.success) {
                showMessage(response.data, 'success');
                setTimeout(function () { location.reload(); }, 1000);
            } else {
                showMessage(response.data, 'error');
            }
        });
    });

    $(document).on('click', '.wp-fetch-test-source', function () {
        var $btn = $(this);
        var name = $btn.data('name');
        $btn.prop('disabled', true).text('Testing...');

        $.post(wpFetchAdmin.ajaxUrl, {
            action: 'wp_fetch_test_source',
            nonce: wpFetchAdmin.nonce,
            name: name
        }, function (response) {
            $btn.prop('disabled', false).text('Test');
            if (response.success) {
                showMessage(response.data.message + ' (HTTP ' + response.data.status_code + ')', 'success');
            } else {
                showMessage(response.data.message || 'Connection failed.', 'error');
            }
        });
    });

    $(document).on('click', '.wp-fetch-edit-source', function () {
        var $btn = $(this);
        $formTitle.text('Edit Source');
        $cancelBtn.show();
        $('#wp-fetch-name').val($btn.data('name')).prop('readonly', true);
        $('#wp-fetch-url').val($btn.data('url'));
        $('#wp-fetch-method').val($btn.data('method'));
        $('#wp-fetch-headers').val($btn.data('headers'));
        $('#wp-fetch-auth-type').val($btn.data('auth-type'));
        $('#wp-fetch-auth-value').val('');
        $('#wp-fetch-cache-ttl').val($btn.data('cache-ttl'));
        $('#wp-fetch-transform').val($btn.data('transform'));
        $('#wp-fetch-fallback').val($btn.data('fallback'));
        $('html, body').animate({ scrollTop: $form.offset().top - 50 }, 300);
    });

    $cancelBtn.on('click', function () {
        $form[0].reset();
        $formTitle.text('Add New Source');
        $cancelBtn.hide();
        $('#wp-fetch-name').prop('readonly', false);
        $message.empty();
    });
})(jQuery);
