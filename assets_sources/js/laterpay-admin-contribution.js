const $ = jQuery;

class LaterPayAdminContribution {
    constructor(options) {
        this.container = $(options.container);

        this.i18n = {
            confirm: options.i18n.confirm,
            cancel: options.i18n.cancel,
            confirmTitle: options.i18n.confirmTitle,
            confirmDelete: options.i18n.confirmDelete
        };

        // data from back-end
        this.data = {
            amountList: options.amountList,
            locale: options.locale,
            currency: {
                sis_min: options.currency.sis_min,
                sis_max: options.currency.sis_max,
                ppu_min: options.currency.ppu_min,
                ppu_max: options.currency.ppu_max,
            }
        };

        this.object = {
            body: $('body'),

            // Contribution/Donation Amounts
            amountForm: '.lp_js_amountForm',
            amountFormTemplate: $('#lp_js_amountFormTemplate', this.container),
            amountContainer: $('#lp_js_amountContainer', this.container),
            addButton: $('#lp_js_add', this.container),
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

        // strings cached for better compression
        this.string = {
            price: '0.00',
            payPerUse: 'ppu',
            singleSale: 'sis',
            hidden: 'lp_hidden',
            editing: 'lp_is-editing',
            unsaved: 'lp_is-unsaved',
            selected: 'lp_is-selected',
            disabled: 'lp_is-disabled'
        };

        this
            ._bindEvents()
            ._render()
            ._checkAmountForms();
    }

    _render() {
        this
            ._renderAmounts()
            ._renderAddButton();

        return this;
    }

    _renderAmounts() {
        this.object.amountContainer.html('');

        $.each(this.data.amountList, (index, amount) => {
            let template = this.object.amountFormTemplate.clone();

            template
                .removeAttr('id')
                .find('.lp_js_currency')
                .text(this.data.currency.code);

            template.find(this.object.priceInput).val(amount.localized_price);
            template.find(this.object.priceDisplay).text(amount.localized_price);
            template.find('.lp_badge').text(amount.revenue_model_label);

            template.fadeIn();

            this._bindClickDelete(template.find('.lp_js_delete'), index);
            this.object.amountContainer.append(template);

        });

        return this;
    }

    _bindClickDelete(button, index) {
        button.on('click', e => {
            e.preventDefault();

            let dialog = $('<div>' + this.i18n.confirmDelete + '</div>').dialog({
                resizable: false,
                title: this.i18n.confirmTitle,
                height: "auto",
                modal: true,
                buttons: [
                    {
                        text: this.i18n.confirm,
                        click: () => {
                            dialog.dialog("close");

                            this.data.amountList.splice(index, 1);
                            this._render();
                        },
                    },
                    {
                        text: this.i18n.cancel,
                        click: () => {
                            dialog.dialog("close");
                        }
                    },
                ]
            });
        });
    }

    _renderAddButton() {
        if (this.data.amountList.length > 3) {
            this.object.addButton.hide();
            return this;
        }

        this.object.addButton.fadeIn();
        return this;
    }

    _renderEditForm() {

    }

    _bindEvents() {
        // validate price and revenue model when entering a price
        // (function is only triggered 800ms after the keyup)
        this.object.body.on('keyup', this.object.priceInput, $().debounce(e => {
                this._validatePrice($(e.target).closest('form'));
            }, 800)
        );

        // validate price on revenue model changing
        this.object.body.on('change', this.object.revenueModelInput, e => {
            this._validatePrice($(e.target).closest('form'));
        });

        // add
        this.object.addButton
            .on('mousedown', () => {
                this._addAmount();
            })
            .click(e => {
                e.preventDefault();
            });

        // edit
        this.object.body
            .on('click', this.object.editButton, e => {
                e.preventDefault();
                let form = $(e.target).closest(this.object.amountForm);
                this._enterEditMode(form);
            });

        // cancel
        this.object.body
            .on('click', this.object.cancelButton, e => {
                e.preventDefault();
                let form = $(e.target).closest(this.object.amountForm);
                this._exitEditMode(form);
            });

        // save
        this.object.body
            .on('click', this.object.saveButton, e => {
                e.preventDefault();
                let form = $(e.target).closest(this.object.amountForm);
                this._saveAmount(form);
            });

        return this;
    }

    _enterEditMode(form) {
        let amountShow = form.find(this.object.amountShow),
            amountEdit = form.find(this.object.amountEdit);

        amountShow.slideUp({duration: 250});
        amountEdit.slideDown({
            duration: 250,
            complete: () => {
                setTimeout(() => {
                    amountEdit.find(this.object.priceInput).focus();
                }, 50);
            }
        });

        form.addClass(this.object.editing);
    }

    _saveAmount(form) {
        let amountShow = form.find(this.object.amountShow),
            priceInput = form.find(this.object.priceInput),
            priceDisplay = amountShow.find(this.object.priceDisplay),
            revenueModelDisplay = amountShow.find(this.object.revenueModelDisplay);

        // fix invalid prices
        this._validatePrice(form);

        if (priceInput.val() <= 0) {
            return;
        }

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
                    form.removeClass(this.object.unsaved);
                    form.find('input[name=id]').val(r.id);

                    this._exitEditMode(form);
                }
                this.object.navigation.showMessage(r);
            },
            'json'
        );
    }

    _exitEditMode(form) {
        let amountShow = form.find(this.object.amountShow),
            amountEdit = form.find(this.object.amountEdit);

        amountShow.slideDown({duration: 250});
        amountEdit.slideUp({duration: 250});

        if (form.hasClass(this.object.unsaved)) {
            form.remove();
        }

        this._checkAmountForms();
    }

    _deleteAmount(form) {
        let dialog = $('<div>' + this.i18n.confirmDelete + '</div>').dialog({
            resizable: false,
            title: this.i18n.confirmTitle,
            height: "auto",
            modal: true,
            buttons: [
                {
                    text: this.i18n.confirm,
                    click: () => {
                        dialog.dialog("close");

                        form.find('input[name=operation]').val('delete');

                        $.post(
                            ajaxurl,
                            form.serializeArray(),
                            (r) => {
                                if (r.success) {
                                    form.remove();
                                    this._checkAmountForms();
                                }
                                this.object.navigation.showMessage(r);
                            },
                            'json'
                        );


                    },
                },
                {
                    text: this.i18n.cancel,
                    click: () => {
                        dialog.dialog("close");
                    }
                },
            ]
        });
    }

    _addAmount() {
        // clone template
        const form = this.object.amountFormTemplate
            .clone()
            .insertBefore(this.object.amountFormTemplate)
            .slideDown({duration: 250});

        this.object.addButton.hide();
        $(this.object.amountEdit).hide();

        // mark form as new
        form.addClass(this.object.unsaved);
        this._enterEditMode(form);
    }

    _validatePrice(form) {
        let priceInput = $('.lp_number-input', form),
            price = priceInput.val();

        // strip non-number characters
        price = price.replace(/[^0-9,.]/g, '');

        // convert price to proper float value
        price = parseFloat(price.replace(',', '.')).toFixed(2);

        // prevent non-number prices
        if (isNaN(price)) {
            price = 0;
        }

        // prevent negative prices
        price = Math.abs(price);

        // correct prices outside the allowed range of 0.05 - 149.99
        if (price > this.data.currency.sis_max) {
            price = this.data.currency.sis_max;
        } else if (price > 0 && price < this.data.currency.ppu_min) {
            price = this.data.currency.ppu_min;
        }

        this._validateRevenueModel(price, form);

        // format price with two digits
        price = price.toFixed(2);

        // localize price
        if (this.data.locale.indexOf('de_DE') !== -1) {
            price = price.replace('.', ',');
        }

        // update price input
        priceInput.val(price);

        return price;
    }

    _validateRevenueModel(price, form) {
        let currentRevenueModel,
            input = this.object.revenueModelInput,
            payPerUse = $(input + '[value=' + this.object.payPerUse + ']', form),
            singleSale = $(input + '[value=' + this.object.singleSale + ']', form);

        currentRevenueModel = $('input:radio:checked', form).val();

        if (price === 0 || (price >= this.data.currency.ppu_min && price <= this.data.currency.ppu_max)) {
            // enable Pay-per-Use
            payPerUse.removeProp('disabled')
                .parent('label').removeClass(this.object.disabled);
        } else {
            // disable Pay-per-Use
            payPerUse.prop('disabled', 'disabled')
                .parent('label').addClass(this.object.disabled);
        }

        if (price >= this.data.currency.sis_min) {
            // enable Single Sale for prices
            // (prices > 149.99 Euro are fixed by validatePrice already)
            singleSale.removeProp('disabled')
                .parent('label').removeClass(this.object.disabled);
        } else {
            // disable Single Sale
            singleSale.prop('disabled', 'disabled')
                .parent('label').addClass(this.object.disabled);
        }

        // switch revenue model, if combination of price and revenue model is not allowed
        if (price > this.data.currency.ppu_max && currentRevenueModel === this.object.payPerUse) {
            // Pay-per-Use purchases are not allowed for prices > 5.00 Euro
            singleSale.prop('checked', 'checked');
        } else if (price < this.data.currency.sis_min && currentRevenueModel === this.object.singleSale) {
            // Single Sale purchases are not allowed for prices < 1.49 Euro
            payPerUse.prop('checked', 'checked');
        }

        // highlight current revenue model
        $('label', form).removeClass(this.object.selected);
        $(input + ':checked', form).parent('label').addClass(this.object.selected);
    }

    _checkAmountForms() {
        // Disallow to delete the latest form.
        // Two forms are here because delete button also is present in "Add Amount" template.
        if ($(this.object.amountForm).length === 2) {
            $(this.object.deleteButton).hide();
        } else {
            $(this.object.deleteButton).fadeIn();
        }

        if ($(this.object.amountForm).length > 3) {
            this.object.addButton.hide();
            return;
        }

        this.object.addButton.fadeIn();

        return this;
    }
}