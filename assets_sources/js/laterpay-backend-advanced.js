(function ($) {

    class LaterPayAdvanced {
        constructor() {
            this.$o = {
                form: $('#lp_js_advancedForm'),
                navigation: $('.lp_navigation'),
                unlimitedAccessNone: $('.lp_access-none'),
                unlimitedAccessAll: $('.lp_access-all'),
                unlimitedAccessInput: $('.lp_category-access-input'),
                proMerchant: $('#lp_js_proMerchant'),
                businessModel: $('#lp_js_businessModel'),
                businessModelPrevious: $('#lp_js_businessModel').val()
            };

            this
                .bindEvents()
                .prepareUnlimitedAccess();
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

            this.$o.businessModel
                .bind('change', () => {
                    this.toggleBusinessModel();
                });

            return this;
        }

        saveForm() {
            $.post(
                ajaxurl,
                this.$o.form.serializeArray(),
                (data) => {
                    this.$o.navigation.showMessage(data);
                },
                'json'
            );
        }

        toggleUnlimitedAccessNone(e) {
            let el = $(e),
                categories = el.closest('tr').find(this.$o.unlimitedAccessInput),
                all = el.closest('tr').find(this.$o.unlimitedAccessAll);

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
            let el = $(e),
                categories = el.closest('tr').find(this.$o.unlimitedAccessInput),
                none = el.closest('tr').find(this.$o.unlimitedAccessNone);

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
        }

        prepareUnlimitedAccess() {
            this.$o.unlimitedAccessNone.each((i, el) => {
                this.toggleUnlimitedAccessNone(el);
            });
            this.$o.unlimitedAccessAll.each((i, el) => {
                this.toggleUnlimitedAccessAll(el);
            });
        }

        toggleProMerchant() {
            let message = this.$o.proMerchant.data('confirm');

            if (this.$o.proMerchant.attr('checked') && false === confirm(message)) {
                this.$o.proMerchant.removeAttr('checked');

                return;
            }

            // reset business model to paid if it is pro account
            this.$o.businessModel.val('paid');
            this.$o.businessModelPrevious = 'paid';
        }

        checkCurrentBusinessModel() {
            // disable pro feature if non paid business model
            if (this.$o.businessModel.val() !== 'paid') {
                this.$o.proMerchant
                    .attr('disabled', 'disabled')
                    .removeAttr('checked');
            } else {
                // reset business model to paid
                this.$o.proMerchant
                    .removeAttr('disabled');
            }
        }

        toggleBusinessModel() {
            let selectedValue = this.$o.businessModel.val(),
                message = this.$o.businessModel.find(':selected').data('confirm');

            // do nothing for "paid" model
            if (selectedValue === 'paid') {
                this.$o.businessModelPrevious = selectedValue;
                this.checkCurrentBusinessModel();
                return;
            }

            // If option contains data-confirm attribute then show confirm box with that text.
            if (message && true === confirm(message)) {
                this.checkCurrentBusinessModel();
                this.$o.businessModelPrevious = selectedValue;
                return;
            }

            // or reset select to previous value
            this.$o.businessModel.val(this.$o.businessModelPrevious);
        }
    }

    new LaterPayAdvanced();
})(jQuery);