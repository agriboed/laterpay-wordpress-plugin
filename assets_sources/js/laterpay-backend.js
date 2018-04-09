jQuery.fn.showLoadingIndicator = function () {
    let $container = jQuery(this);

    // add a state class, indicating that the element will be showing a loading indicator after a delay
    $container.addClass('lp_is-delayed');

    setTimeout(function () {
        if ($container.hasClass('lp_is-delayed')) {
            // inject the loading indicator after a delay, if the element still has that state class
            $container.removeClass('lp_is-delayed');
            $container.empty().append('<div class="lp_js_loadingIndicator lp_loading-indicator"></div>');
        }
    }, 600);
};

jQuery.fn.removeLoadingIndicator = function () {
    let $container = jQuery(this);

    if ($container.hasClass('lp_is-delayed')) {
        // remove the state class, thus canceling adding the loading indicator
        $container.removeClass('lp_is-delayed');
    } else {
        // remove the loading indicator
        $container.find('.lp_js_loadingIndicator').remove();
    }
};

jQuery.fn.showMessage = function (message, success) {
    let $container = jQuery(this);

    if ($container.find('.lp_flash-message').length > 0) {
        $container.find('.lp_flash-message').remove();
    }

    try {
        let m = JSON.parse(message);
        success = m.success;
        message = m.message;
    } catch (e) {
        if (typeof message !== 'string') {
            success = message.success;
            message = message.message;
        }
    }

    let $message = jQuery('<div class="lp_flash-message" style="display:none;"><p></p></div>'),
        messageClass = success ? 'updated' : 'error';

    $container.prepend($message);
    $message.addClass(messageClass).find('p').html(message);
    if (jQuery('p:hidden', $message)) {
        $message.slideDown({duration: 250});
    }
    setTimeout(function () {
        $message.clearMessage();
    }, 3000);
};

jQuery.fn.clearMessage = function () {
    jQuery(this).slideUp({
        duration: 250, complete: function (message) {
            jQuery(message).remove();
        }
    });
};

// throttle the execution of a function by a given delay
jQuery.fn.debounce = function (fn, delay) {
    let timer;
    return function () {
        let context = this,
            args    = arguments;

        clearTimeout(timer);

        timer = setTimeout(function() {
            fn.apply(context, args);
        }, delay);
    };
};

jQuery.noConflict();