jQuery(document).ready(function ($) {
    var api = window.npleadchat_api || {};
    var strings = $.extend({
        nameRequired: '',
        emailRequired: '',
        emailInvalid: '',
        phoneRequired: '',
        messageRequired: '',
        fallbackSuccess: '',
        fallbackError: ''
    }, api.i18n || {});

    /* -----------------------------------------------
       Helpers
    ----------------------------------------------- */
    function clearErrors($form) {
        $form.find('.wlc-error').text('');
        $form.find('.nlc-has-error').removeClass('nlc-has-error');
        $form.find('.wlc-response').removeClass('nlc-success nlc-error-msg').hide();
    }

    function getField($form, fieldName) {
        return $form.find('[data-nlc-field="' + fieldName + '"]');
    }

    function setError($form, fieldName, msg) {
        getField($form, fieldName).addClass('nlc-has-error');
        $form.find('[data-nlc-error="' + fieldName + '"]').text(msg);
    }

    function validateForm($form) {
        clearErrors($form);
        let isValid = true;

        var name    = getField($form, 'name').val().trim();
        var email   = getField($form, 'email').val().trim();
        var phone   = getField($form, 'phone').val().trim();
        var message = getField($form, 'message').val().trim();

        if (!name) {
            setError($form, 'name', strings.nameRequired);
            isValid = false;
        }
        if (!email) {
            setError($form, 'email', strings.emailRequired);
            isValid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            setError($form, 'email', strings.emailInvalid);
            isValid = false;
        }
        if (!phone) {
            setError($form, 'phone', strings.phoneRequired);
            isValid = false;
        }
        if (!message) {
            setError($form, 'message', strings.messageRequired);
            isValid = false;
        }

        return isValid;
    }

    function showResponse($form, msg, type) {
        var $r = $form.find('.wlc-response');
        var icon = type === 'success'
            ? '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink:0"><circle cx="10" cy="10" r="9" fill="#10b981" opacity=".2"/><path d="M6 10l3 3 5-5" stroke="#065f46" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>'
            : '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink:0"><circle cx="10" cy="10" r="9" fill="#ef4444" opacity=".2"/><path d="M10 6v5M10 14h.01" stroke="#991b1b" stroke-width="1.8" stroke-linecap="round"/></svg>';

        $r.removeClass('nlc-success nlc-error-msg')
          .addClass(type === 'success' ? 'nlc-success' : 'nlc-error-msg')
          .empty()
          .append(icon)
          .append($('<span>').text(msg))
          .css('display', 'flex');
    }

    /* -----------------------------------------------
       Form submission
    ----------------------------------------------- */
    $(document).on('click', '.wlc-submit', function (e) {
        e.preventDefault();

        var $form = $(this).closest('.nlc-chatbot');

        if (!validateForm($form)) { return; }

        var $btn = $(this);
        $btn.addClass('is-loading').prop('disabled', true);

        var data = {
            name:            getField($form, 'name').val(),
            email:           getField($form, 'email').val(),
            phone:           getField($form, 'phone').val(),
            message:         getField($form, 'message').val(),
            source_url:      window.location.href,
            website:         getField($form, 'website').val(),
            form_started_at: getField($form, 'form_started_at').val()
        };

        $.ajax({
            url:         api.url,
            method:      'POST',
            data:        JSON.stringify(data),
            contentType: 'application/json',
            beforeSend:  function (xhr) {
                if (api.nonce) {
                    xhr.setRequestHeader('X-WP-Nonce', api.nonce);
                }
            },
            success: function (res) {
                var isSuccess = !res || res.success !== false;
                showResponse($form, (res && res.message) || api.successMessage || strings.fallbackSuccess, isSuccess ? 'success' : 'error');

                if (isSuccess) {
                    getField($form, 'name').val('');
                    getField($form, 'email').val('');
                    getField($form, 'phone').val('');
                    getField($form, 'message').val('');
                    getField($form, 'website').val('');
                    getField($form, 'form_started_at').val(Math.floor(Date.now() / 1000));
                }
            },
            error: function () {
                showResponse($form, strings.fallbackError, 'error');
            },
            complete: function () {
                $btn.removeClass('is-loading').prop('disabled', false);
            }
        });
    });

    /* -----------------------------------------------
       Remove error state on input
    ----------------------------------------------- */
    $(document).on('input', '.nlc-chatbot input, .nlc-chatbot textarea', function () {
        $(this).removeClass('nlc-has-error');
        var fieldName = $(this).data('nlc-field');
        $(this).closest('.nlc-chatbot').find('[data-nlc-error="' + fieldName + '"]').text('');
    });

    /* -----------------------------------------------
       Floating widget toggle — with animation
    ----------------------------------------------- */
    var $popup  = $('#wlc-chat-popup');
    var $trigBtn = $('#wlc-floating-btn');

    $trigBtn.on('click', function () {
        if ($popup.is(':visible')) {
            closePopup();
        } else {
            openPopup();
        }
    });

    /* The trigger is a div with role="button", so give it button keyboard behaviour */
    $trigBtn.on('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            $trigBtn.trigger('click');
        }
    });

    $('#wlc-chat-close').on('click', function () {
        closePopup();
    });

    function openPopup() {
        $popup.removeClass('nlc-closing').show().addClass('nlc-open');
        $trigBtn.addClass('is-open').attr('aria-expanded', 'true');
        // Focus first input for accessibility
        setTimeout(function () { $popup.find('[data-nlc-field="name"]').trigger('focus'); }, 320);
    }

    function closePopup() {
        $popup.removeClass('nlc-open').addClass('nlc-closing');
        $trigBtn.removeClass('is-open').attr('aria-expanded', 'false');
        setTimeout(function () { $popup.hide().removeClass('nlc-closing'); }, 200);
    }

    /* Close on outside click */
    $(document).on('click', function (e) {
        if ($popup.is(':visible') &&
            !$(e.target).closest('#wlc-chat-popup').length &&
            !$(e.target).closest('#wlc-floating-btn').length) {
            closePopup();
        }
    });

    /* Close on Escape key */
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && $popup.is(':visible')) {
            closePopup();
        }
    });
});
