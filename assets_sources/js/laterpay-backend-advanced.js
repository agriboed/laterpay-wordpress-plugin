(function ($) {

    // encapsulate all LaterPay Javascript in function laterPayBackendAccount
    function laterPayBackendAdvanced() {
        var $o = {
                form: $('#lp_js_advancedForm'),
                navigation: $('.lp_navigation'),
                unlimitedAccessNone: $('.lp_access-none'),
                unlimitedAccessAll: $('.lp_access-all'),
                unlimitedAccessInput: $('.lp_category-access-input'),
                proMerchant: $('#lp_js_proMerchant')
            },

            bindEvents = function () {
                $o.form
                    .bind('submit', function (e) {
                        e.preventDefault();
                        saveForm();
                    });

                $o.unlimitedAccessNone
                    .bind('change', function (e) {
                        toggleUnlimitedAccessNone(e.target);
                    });

                $o.unlimitedAccessAll
                    .bind('change', function (e) {
                        toggleUnlimitedAccessAll(e.target);
                    });

                $o.proMerchant
                    .bind('change', function () {
                        toggleProMerchant();
                    })
            },

            saveForm = function () {
                $.post(
                    ajaxurl,
                    $o.form.serializeArray(),
                    function (data) {
                        $o.navigation.showMessage(data);
                    },
                    'json'
                );
            },

            toggleUnlimitedAccessNone = function (e) {
                var el = $(e);
                var categories = el.closest('tr').find($o.unlimitedAccessInput);
                var all = el.closest('tr').find($o.unlimitedAccessAll);

                if (el.attr('checked') === 'checked') {
                    all.removeAttr('checked');
                    categories.each(function (i, category) {
                        $(category).removeAttr('checked');
                        $(category).closest('label').hide();
                    });
                } else if (all.attr('checked') !== 'checked') {
                    categories.each(function (i, category) {
                        $(category).closest('label').fadeIn();
                    });
                }
            },

            toggleUnlimitedAccessAll = function (e) {
                var el = $(e);
                var categories = el.closest('tr').find($o.unlimitedAccessInput);
                var none = el.closest('tr').find($o.unlimitedAccessNone);

                if (el.attr('checked') === 'checked') {
                    none.removeAttr('checked');
                    categories.each(function (i, category) {
                        $(category).removeAttr('checked');
                        $(category).closest('label').hide();
                    });
                } else if (none.attr('checked') !== 'checked') {
                    categories.each(function (i, category) {
                        $(category).closest('label').fadeIn();
                    });
                }
            },

            prepareUnlimitedAccess = function () {
                $o.unlimitedAccessNone.each(function (i, el) {
                    toggleUnlimitedAccessNone(el);
                });
                $o.unlimitedAccessAll.each(function (i, el) {
                    toggleUnlimitedAccessAll(el);
                });
            },

            toggleProMerchant = function () {
                var message = $o.proMerchant.data('confirm');
                if ($o.proMerchant.attr('checked') && false === confirm(message)) {
                    $o.proMerchant.removeAttr('checked')
                }
            },
            initializePage = function () {
                bindEvents();
                prepareUnlimitedAccess();
            };

        initializePage();
    }

    // initialize page
    laterPayBackendAdvanced();

})(jQuery);