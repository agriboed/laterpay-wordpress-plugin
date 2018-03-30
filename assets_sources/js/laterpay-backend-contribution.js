(function ($) {

    class LaterPayContribution {
        constructor() {
            this.$o = {
                body: $('body'),

                // Contribution/Donation Amounts
                amountForm: '.lp_js_amountForm',
                amountFormTemplate: $('#lp_js_amountFormTemplate'),
                addButton: $('#lp_js_add'),
                editButton: '.lp_js_edit',
                deleteButton: '.lp_js_delete',
                cancelButton: '.lp_js_cancel',
                saveButton: '.lp_js_save',
                priceInput: '.lp_js_priceInput',
                priceDisplay: '.lp_js_priceDisplay',
                revenueModelDisplay: '.lp_js_revenueModelDisplay',
                revenueModel: '.lp_js_revenueModel',
                revenueModelLabel: '.lp_js_revenueModelLabel',
                revenueModelLabelDisplay: '.lp_js_revenueModelLabelDisplay',
                revenueModelInput: '.lp_js_revenueModelInput',
                amountShow: '.lp_js_amountShow',
                amountEdit: '.lp_js_amountEdit',

                // strings cached for better compression
                editing: 'lp_is-editing',
                unsaved: 'lp_is-unsaved',
                payPerUse: 'ppu',
                singleSale: 'sis',
                selected: 'lp_is-selected',
                disabled: 'lp_is-disabled',
                hidden: 'lp_hidden',
                navigation: $('.lp_navigation'),
            };

            this
                .bindEvents()
                .checkAmountForms();
        }

        bindEvents() {
            // validate price and revenue model when entering a price
            // (function is only triggered 800ms after the keyup)
            this.$o.body.on('keyup', this.$o.priceInput, $().debounce((e) => {
                    this.validatePrice($(e.target).closest('form'));
                }, 800)
            );

            // validate price on revenue model changing
            this.$o.body.on('change', this.$o.revenueModelInput, (e) => {
                this.validatePrice($(e.target).closest('form'));
            });

            // add
            this.$o.addButton
                .on('mousedown', () => {
                    this.addAmount();
                })
                .click(e => {
                    e.preventDefault();
                });

            // edit
            this.$o.body
                .on('click', this.$o.editButton, e => {
                    let form = $(e.target).closest(this.$o.amountForm);
                    this.enterEditMode(form);
                });

            // cancel
            this.$o.body
                .on('click', this.$o.cancelButton, e => {
                    let form = $(e.target).closest(this.$o.amountForm);
                    this.exitEditMode(form);
                });

            // save
            this.$o.body
                .on('click', this.$o.saveButton, e => {
                    let form = $(e.target).closest(this.$o.amountForm);
                    this.saveAmount(form);
                });

            // delete
            this.$o.body
                .on('click', this.$o.deleteButton, e => {

                    if (!confirm(lpVars.i18n.confirmDelete)) {
                        return;
                    }

                    let form = $(e.target).closest(this.$o.amountForm);
                    this.deleteAmount(form);
                });

            return this;
        }

        /**
         * Entering in editing mode by clicking on button
         *
         * @param form target button
         */
        enterEditMode(form) {
            let amountShow = form.find(this.$o.amountShow),
                amountEdit = form.find(this.$o.amountEdit);

            amountShow.slideUp({duration: 250});
            amountEdit.slideDown({
                duration: 250,
                complete: () => {
                    setTimeout(() => {
                        amountEdit.find(this.$o.priceInput).focus();
                    }, 50);
                }
            });

            form.addClass(this.$o.editing);
        }

        /**
         * @param form target form
         */
        saveAmount(form) {
            let amountShow = form.find(this.$o.amountShow),
                amountEdit = form.find(this.$o.amountEdit),
                priceInput = form.find(this.$o.priceInput),
                priceDisplay = amountShow.find(this.$o.priceDisplay),
                revenueModelDisplay = amountShow.find(this.$o.revenueModelDisplay);

            console.log(form);

            // fix invalid prices
            this.validatePrice(form);

            $.post(
                ajaxurl,
                form.serializeArray(),
                (r) => {
                    if (r.success) {

                        priceDisplay
                            .text(r.localized_price)
                            .data('price', r.price);

                        revenueModelDisplay
                            .text(r.revenue_model_label)
                            .data('revenue', r.revenue_model);

                        priceInput.val(r.price);
                        form.removeClass(this.$o.unsaved);
                        form.find('input[name=id]').val(r.id);

                        this.exitEditMode(form);
                    }
                    this.$o.navigation.showMessage(r);
                },
                'json'
            );
        }

        /**
         * Method closest editing Amount
         *
         * @param form target form
         */
        exitEditMode(form) {
            let amountShow = form.find(this.$o.amountShow),
                amountEdit = form.find(this.$o.amountEdit);

            amountShow.slideDown({duration: 250});
            amountEdit.slideUp({duration: 250});

            if (form.hasClass(this.$o.unsaved)) {
                form.remove();
            }

            this.checkAmountForms();
        }

        /**
         * Method delete form with a Donation Amount
         *
         * @param form target form
         */
        deleteAmount(form) {
            form.find('input[name=operation]').val('delete');

            $.post(
                ajaxurl,
                form.serializeArray(),
                (r) => {
                    if (r.success) {
                        form.remove();
                        this.checkAmountForms();
                    }
                    this.$o.navigation.showMessage(r);
                },
                'json'
            );
        }

        /**
         * Method shows new one form for entering amount
         */
        addAmount() {
            // clone template
            const form = this.$o.amountFormTemplate
                .clone()
                .insertBefore(this.$o.amountFormTemplate)
                .slideDown({duration: 250});

            this.$o.addButton.hide();
            $(this.$o.amountEdit).hide();

            // mark form as new
            form.addClass(this.$o.unsaved);
            this.enterEditMode(form);
        }

        validatePrice(form) {
            let priceInput = $('.lp_number-input', form),
                price = priceInput.val();

            // strip non-number characters
            price = price.replace(/[^0-9\,\.]/g, '');

            // convert price to proper float value
            price = parseFloat(price.replace(',', '.')).toFixed(2);

            // prevent non-number prices
            if (isNaN(price)) {
                price = 0;
            }

            // prevent negative prices
            price = Math.abs(price);

            // correct prices outside the allowed range of 0.05 - 149.99
            if (price > lpVars.currency.sis_max) {
                price = lpVars.currency.sis_max;
            } else if (price > 0 && price < lpVars.currency.ppu_min) {
                price = lpVars.currency.ppu_min;
            }

            this.validateRevenueModel(price, form);

            // format price with two digits
            price = price.toFixed(2);

            // localize price
            if (lpVars.locale.indexOf('de_DE') !== -1) {
                price = price.replace('.', ',');
            }

            // update price input
            priceInput.val(price);

            return price;
        }

        validateRevenueModel(price, form) {
            let currentRevenueModel,
                input = this.$o.revenueModelInput,
                payPerUse = $(input + '[value=' + this.$o.payPerUse + ']', form),
                singleSale = $(input + '[value=' + this.$o.singleSale + ']', form);

            currentRevenueModel = $('input:radio:checked', form).val();

            if (price === 0 || (price >= lpVars.currency.ppu_min && price <= lpVars.currency.ppu_max)) {
                // enable Pay-per-Use
                payPerUse.removeProp('disabled')
                    .parent('label').removeClass(this.$o.disabled);
            } else {
                // disable Pay-per-Use
                payPerUse.prop('disabled', 'disabled')
                    .parent('label').addClass(this.$o.disabled);
            }

            if (price >= lpVars.currency.sis_min) {
                // enable Single Sale for prices
                // (prices > 149.99 Euro are fixed by validatePrice already)
                singleSale.removeProp('disabled')
                    .parent('label').removeClass(this.$o.disabled);
            } else {
                // disable Single Sale
                singleSale.prop('disabled', 'disabled')
                    .parent('label').addClass(this.$o.disabled);
            }

            // switch revenue model, if combination of price and revenue model is not allowed
            if (price > lpVars.currency.ppu_max && currentRevenueModel === this.$o.payPerUse) {
                // Pay-per-Use purchases are not allowed for prices > 5.00 Euro
                singleSale.prop('checked', 'checked');
            } else if (price < lpVars.currency.sis_min && currentRevenueModel === this.$o.singleSale) {
                // Single Sale purchases are not allowed for prices < 1.49 Euro
                payPerUse.prop('checked', 'checked');
            }

            // highlight current revenue model
            $('label', form).removeClass(this.$o.selected);
            $(input + ':checked', form).parent('label').addClass(this.$o.selected);
        }

        /**
         * Allowed creating maximum three forms.
         */
        checkAmountForms() {
            if ($(this.$o.amountForm).length > 3) {
                this.$o.addButton.hide();
                return;
            }

            this.$o.addButton.fadeIn();

            return this;
        }
    }

    new LaterPayContribution();
})(jQuery);