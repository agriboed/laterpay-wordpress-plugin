jQuery.fn.showLoadingIndicator = function() {
    var $container = jQuery(this);

    // add a state class, indicating that the element will be showing a loading indicator after a delay
    $container.addClass('lp_is-delayed');

    setTimeout(function() {
        if ($container.hasClass('lp_is-delayed')) {
            // inject the loading indicator after a delay, if the element still has that state class
            $container.removeClass('lp_is-delayed');
            $container.empty().append('<div class="lp_js_loadingIndicator lp_loading-indicator"></div>');
        }
    }, 600);
};

jQuery.fn.removeLoadingIndicator = function() {
    var $container = jQuery(this);

    if ($container.hasClass('lp_is-delayed')) {
        // remove the state class, thus canceling adding the loading indicator
        $container.removeClass('lp_is-delayed');
    } else {
        // remove the loading indicator
        $container.find('.lp_js_loadingIndicator').remove();
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

    if (jQuery('.lp_flash-message').length > 0){
        jQuery('.lp_flash-message').remove();
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
    jQuery(this).velocity('slideUp', { duration: 250, complete: function(message) { jQuery(message).remove(); } });
};

jQuery.noConflict();

// Zendesk widget
window.zEmbed||function(e,t){var n,o,d,i,s,a=[],r=document.createElement("iframe");window.zEmbed=function(){a.push(arguments)},window.zE=window.zE||window.zEmbed,r.src="javascript:false",r.title="",r.role="presentation",(r.frameElement||r).style.cssText="display: none",d=document.getElementsByTagName("script"),d=d[d.length-1],d.parentNode.insertBefore(r,d),i=r.contentWindow,s=i.document;try{o=s}catch(e){n=document.domain,r.src='javascript:var d=document.open();d.domain="'+n+'";void(0);',o=s}o.open()._l=function(){var e=this.createElement("script");n&&(this.domain=n),e.id="js-iframe-async",e.src="https://assets.zendesk.com/embeddable_framework/main.js",this.t=+new Date,this.zendeskHost="laterpay.zendesk.com",this.zEQueue=a,this.body.appendChild(e)},o.write('<body onload="document._l();">'),o.close()}();