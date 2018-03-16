(function ($) {

    class Advanced {
        constructor() {
            this.$o = {
                form: $('#lp_js_advancedForm'),
                navigation: $('.lp_navigation'),
                unlimitedAccessNone: $('.lp_access-none'),
                unlimitedAccessAll: $('.lp_access-all'),
                unlimitedAccessInput: $('.lp_category-access-input'),
                proMerchant: $('#lp_js_proMerchant')
            };

            this.bindEvents();
            this.prepareUnlimitedAccess();
        }

        bindEvents() {
            this.$o.form
                .bind('submit', (e) => {
                    e.preventDefault();
                    this.saveForm();
                });

            this.$o.unlimitedAccessNone
                .bind('change', (e) => {
                    this.toggleUnlimitedAccessNone(e.target);
                });

            this.$o.unlimitedAccessAll
                .bind('change', (e) => {
                    this.toggleUnlimitedAccessAll(e.target);
                });

            this.$o.proMerchant
                .bind('change', () => {
                    this.toggleProMerchant();
                });
        };

        saveForm() {
            $.post(
                ajaxurl,
                this.$o.form.serializeArray(),
                function (data) {
                    this.$o.navigation.showMessage(data);
                },
                'json'
            );
        }

        toggleUnlimitedAccessNone(e) {
            let el = $(e);
            let categories = el.closest('tr').find(this.$o.unlimitedAccessInput);
            let all = el.closest('tr').find(this.$o.unlimitedAccessAll);

            if (el.attr('checked') === 'checked') {
                all.removeAttr('checked');
                categories.each((i, category) => {
                    $(category).removeAttr('checked');
                    $(category).closest('label').hide();
                });
            } else if (all.attr('checked') !== 'checked') {
                categories.each(function (i, category) {
                    $(category).closest('label').fadeIn();
                });
            }
        }

        toggleUnlimitedAccessAll(e) {
            let el = $(e);
            let categories = el.closest('tr').find(this.$o.unlimitedAccessInput);
            let none = el.closest('tr').find(this.$o.unlimitedAccessNone);

            if (el.attr('checked') === 'checked') {
                none.removeAttr('checked');
                categories.each((i, category) => {
                    $(category).removeAttr('checked');
                    $(category).closest('label').hide();
                });
            } else if (none.attr('checked') !== 'checked') {
                categories.each((i, category) => {
                    $(category).closest('label').fadeIn();
                });
            }
        };

        prepareUnlimitedAccess() {
            this.$o.unlimitedAccessNone.each((i, el) => {
                this.toggleUnlimitedAccessNone(el);
            });
            this.$o.unlimitedAccessAll.each((i, el) => {
                this.toggleUnlimitedAccessAll(el);
            });
        };

        toggleProMerchant() {
            let message = this.$o.proMerchant.data('confirm');

            if (this.$o.proMerchant.attr('checked') && false === confirm(message)) {
                this.$o.proMerchant.removeAttr('checked');
            }
        }
    }

    new Advanced();
})(jQuery);