jQuery.fn.showLoadingIndicator = function() {
    // add a state class, indicating that the element will be showing a loading indicator after a delay
    jQuery(this).addClass('lp_is-delayed');

    setTimeout(function() {
        if (jQuery(this).hasClass('lp_is-delayed')) {
            // inject the loading indicator after a delay, if the element still has that state class
            jQuery(this).removeClass('lp_is-delayed');
            jQuery(this).html('<div class="lp_js_loadingIndicator lp_loading-indicator"></div>');
        }
    }, 600);
};

jQuery.fn.removeLoadingIndicator = function() {
    if (jQuery(this).hasClass('lp_is-delayed')) {
        // remove the state class, thus canceling adding the loading indicator
        jQuery(this).removeClass('lp_is-delayed');
    } else {
        // remove the loading indicator
        jQuery(this).find('.lp_js_loadingIndicator').remove();
    }
};

jQuery.fn.showMessage = function(message, success) {
    var $container  = jQuery(this);

    try {
        var m = JSON.parse(message);
        success = m.success;
        message = m.message;
    } catch(e) {
        if (typeof message !== 'string') {
            success = message.success;
            message = message.message;
        }
    }

    var $message     = jQuery('<div class="lp_flash-message" style="display:none;"><p></p></div>'),
        messageClass = success ? 'updated' : 'error';

    $container.prepend($message);
    $message.addClass(messageClass).find('p').html(message);
    if (jQuery('p:hidden', $message)) {
        $message.velocity('slideDown', { duration: 250 });
    }
    setTimeout(function() { $message.clearMessage(); }, 3000);
};

jQuery.fn.clearMessage = function() {
    jQuery(this).velocity('slideUp', { duration: 250 });
};

jQuery.noConflict();


// TODO: remove with release 1.0
// show deprecated features
jQuery('#lp_js_showDeprecatedFeatures')
.click(function(e) {
    jQuery('.lp_js_deprecated-feature').removeClass('lp_js_deprecated-feature');
    jQuery(this).remove();
    e.preventDefault();
});
