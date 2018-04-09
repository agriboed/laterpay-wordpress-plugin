const $ = jQuery;

class LaterPayPostEditContribution {
    constructor(options) {
        this.container = $(options.container);

        // data from back-end
        this.data = {
            type: options.type,
            locale: options.locale,
            currency: options.currency,
            individualList: options.individualList
        };

        // translations
        this.i18n = {
            confirmDelete: options.i18nConfirmDelete
        };

        // edit form data
        this.edit = {
            active: false,
            index: null, // null for new amount
            price: 0.00,
            localized_price: 0.00,
            revenue_model: 'sis'
        };

        // list of elements in current container
        this.object = {
            spinner: $('.lp_loading-indicator', this.container),
            contentContainer: $('.lp_js_container', this.container),

            addButton: $('#lp_js_add', this.container),

            // change type buttons
            typeButtons: $('.lp_js_typeButton', this.container),
            typeIndividualButton: $('#lp_js_typeIndividual', this.container),
            typeGlobalButton: $('#lp_js_typeGlobal', this.container),

            // amounts details
            individualContainer: $('#lp_js_individualContainer', this.container),
            globalContainer: $('#lp_js_globalContainer', this.container),
            amountTemplate: $('#lp_js_amountTemplate', this.container),

            // amount edit form
            editForm: $('#lp_js_editForm', this.container),
            priceInput: $('#lp_js_postPriceInput', this.container),
            revenueModel: $('#lp_js_revenueModel', this.container),
            confirmButton: $('#lp_js_confirm', this.container),
            cancelButton: $('#lp_js_cancel', this.container),
            payPerUse: $('#lp_js_payPerUse', this.container),
            singleSale: $('#lp_js_singleSale', this.container),
        };

        // amount element for parsing
        this.amount = {
            price: '.lp_js_price',
            localizedPrice: '.lp_price',
            revenueModel: '.lp_js_revenueModel',
            revenueModelLabel: '.lp_badge',
            editButton: '.lp_js_edit',
            deleteButton: '.lp_js_delete',
            priceDisplay: '.lp_js_priceDisplay',
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
            ._bindEditForm()
            ._bindChangeType()
            ._render();
    }

    _render() {
        this
            ._renderTypeButtons()
            ._renderAddButton()
            ._renderEditMode()
            ._renderIndividualList()
            ._renderGlobalList();

        this.object.contentContainer.fadeIn();
        this.object.spinner.hide();
    }

    _renderTypeButtons() {
        let individualListCount = this.data.individualList.length,
            typeButtons = this.object.typeButtons,
            individualButton = this.object.typeIndividualButton,
            globalButton = this.object.typeGlobalButton;

        // make disabled if list is empty
        if (individualListCount === 0) {
            this.data.type = 'global';

            $('a', individualButton).parent().addClass(this.string.disabled);
        } else {
            $('a', individualButton).parent().removeClass(this.string.disabled);
        }

        // clear previous values
        typeButtons.removeClass(this.string.selected);
        $('input:radio', typeButtons).prop('checked', false);

        if (this.data.type === 'individual' && individualListCount > 0) {
            $('input:radio', individualButton).attr('checked', true);
            $('a', individualButton).parent().addClass(this.string.selected);
        } else {
            $('input:radio', globalButton).attr('checked', true);
            $('a', globalButton).parent().addClass(this.string.selected);
        }

        return this;
    }

    _renderAddButton() {
        const addButton = this.object.addButton;

        let editingIsActive = this.edit.active,
            type = this.data.type,
            individualList = this.data.individualList.length;

        if (editingIsActive) {
            addButton.hide();
            return this;
        }

        if (type === 'individual' || (type === 'global' && individualList === 0)) {
            addButton.fadeIn();
            return this;
        }

        addButton.hide();

        return this;
    }

    _renderGlobalList() {
        if (this.data.type !== 'global') {
            this.object.globalContainer.hide();
            return this;
        }

        this.object.globalContainer.fadeIn();

        return this;
    }

    _renderIndividualList() {
        // clear previous values
        this.object.individualContainer.html('');

        $.each(this.data.individualList, (index, amount) => {
            let element = this.object.amountTemplate.clone();

            // highlight
            if (this.edit.active === true && index === this.edit.index) {
                element.addClass(this.string.selected);
            }

            element.removeAttr('id')
                .removeClass(this.string.hidden)
                .find(this.amount.price)
                .attr('name', 'post_amount[' + index + '][price]')
                .val(amount.price);

            element.find(this.amount.revenueModel)
                .attr('name', 'post_amount[' + index + '][revenue_model]')
                .val(amount.revenue_model);

            element.find(this.amount.localizedPrice)
                .text(amount.localized_price + ' ' + this.data.currency.code);

            element.find(this.amount.revenueModelLabel)
                .text(amount.revenue_model_label);

            this._bindClickEdit(element.find(this.amount.editButton), index);
            this._bindClickDelete(element.find(this.amount.deleteButton), index);

            this.object.individualContainer.append(element);
        });

        if (this.data.type !== 'individual') {
            this.object.individualContainer.hide();
        } else {
            this.object.individualContainer.fadeIn();
        }

        return this;
    }

    _renderEditMode() {
        if (this.edit.active !== true ||
            (this.data.type === 'global' && this.data.individualList.length > 0)) {
            this.object.editForm.hide();
            return this;
        }

        // set revenue model label
        if (this.string.singleSale === this.edit.revenueModel) {
            this.object.singleSale
                .prop('checked', true)
                .parent('label')
                .addClass(this.string.selected);

            this.object.payPerUse
                .prop('checked', false)
                .parent('label')
                .removeClass(this.string.selected);
        } else {
            this.object.payPerUse
                .prop('checked', true)
                .parent('label')
                .addClass(this.string.selected);

            this.object.singleSale
                .prop('checked', false)
                .parent('label')
                .removeClass(this.string.selected);
        }

        this.object.priceInput.val(this.edit.localized_price);
        this.object.editForm.fadeIn();

        return this;
    }

    _bindClickEdit(button, index) {
        button.on('click', e => {
            e.preventDefault();

            let amount = this.data.individualList[index];

            this.edit = {
                active: true,
                index,
                price: amount.price,
                localized_price: amount.localized_price,
                revenueModel: amount.revenue_model
            };

            this._render();
        });
    }

    _bindClickDelete(button, index) {
        button.on('click', e => {
            e.preventDefault();

            if (true !== confirm(this.i18n.confirmDelete)) {
                return;
            }

            this.edit.active = false;
            this.data.individualList.splice(index, 1);

            this._render();
        });
    }

    _bindEditForm() {
        // validate price and revenue model when entering a price
        // (function is only triggered 800ms after the keyup)attr
        this.object.priceInput.on('keyup', $().debounce(() => {
                this._validatePrice();
            }, 800)
        );

        $('input:radio', this.object.revenueModel).on('change', () => {
            let currentRevenueModel = $('input:radio:checked', this.object.revenueModel);

            // reset previous
            $('input:radio', this.object.revenueModel).prop('checked', false);
            $('label', this.object.revenueModel).removeClass(this.string.selected);

            currentRevenueModel.prop('checked', true).closest('label').addClass(this.string.selected);
        });

        this.object.cancelButton.on('click', e => {
            e.preventDefault();
            this.edit.active = false;
            this._render();
        });

        this.object.confirmButton.on('click', e => {
            e.preventDefault();
            this._validatePrice();

            this.data.type = 'individual';

            let revenueModel = $('input:radio:checked', this.object.revenueModel),
                price = this.object.priceInput,
                amount = {
                    price: price.val(),
                    localized_price: price.val(),
                    revenue_model: revenueModel.val(),
                    revenue_model_label: revenueModel.data('label')
                };

            if (this.edit.index === null) {
                this.data.individualList.push(amount);
            } else {
                this.data.individualList[this.edit.index] = amount;
            }

            this.edit.active = false;
            this._render();
        });

        this.object.addButton.on('click', e => {
            e.preventDefault();

            this.edit = {
                active: true,
                index: null,
                price: this.string.price,
                localized_price: this.string.price,
                revenueModel: this.string.singleSale,
            };

            this._render();
        });

        return this;
    }

    _bindChangeType() {
        let globalButton = this.object.typeGlobalButton,
            individualButton = this.object.typeIndividualButton;

        globalButton.on('click', e => {
            e.preventDefault();
            this.data.type = 'global';
            this._render();
        });

        individualButton.on('click', e => {
            e.preventDefault();
            this.data.type = 'individual';
            this._render();
        });

        return this;
    }

    _validatePrice() {
        // strip non-number characters
        let price = this.object.priceInput.val().toString().replace(/[^0-9,.]/g, '');

        // convert price to proper float value
        if (typeof price === 'string' && price.indexOf(',') > -1) {
            price = parseFloat(price.replace(',', '.')).toFixed(2);
        } else {
            price = parseFloat(price).toFixed(2);
        }

        // prevent non-number prices
        if (isNaN(price)) {
            price = 0;
        }

        // prevent negative prices
        price = Math.abs(price);

        // correct prices outside the allowed range of 0.05 - 149.49
        if (price > this.data.currency.sis_max) {
            price = this.data.currency.sis_max;
        } else if (price > 0 && price < this.data.currency.ppu_min) {
            price = this.data.currency.ppu_min;
        }

        this._validateRevenueModel(price);

        // format price with two digits
        price = price.toFixed(2);

        // localize price
        if (this.data.locale.indexOf('de_DE') !== -1) {
            price = price.replace('.', ',');
        }

        this.object.priceInput.val(price);
    }

    _validateRevenueModel(price) {
        let currentRevenueModel = $('input:radio:checked', this.object.revenueModel).val(),
            singleSale = $('input[value=sis]', this.object.revenueModel),
            payPerUse = $('input[value=ppu]', this.object.revenueModel);

        if (price === 0 || (price >= this.data.currency.ppu_min && price <= this.data.currency.ppu_max)) {
            // enable Pay-per-Use for 0 and all prices between 0.05 and 5.00 Euro
            payPerUse.parent('label').removeClass(this.string.disabled);
        } else {
            // disable Pay-per-Use
            payPerUse.parent('label').addClass(this.string.disabled);
        }

        if (price >= this.data.currency.sis_min) {
            // enable Single Sale for prices >= 1.49 Euro
            // (prices > 149.99 Euro are fixed by validatePrice already)
            singleSale.parent('label').removeClass(this.string.disabled);
        } else {
            // disable Single Sale
            singleSale.parent('label').addClass(this.string.disabled);
        }

        // switch revenue model, if combination of price and revenue model is not allowed
        if (price > this.data.currency.ppu_max && currentRevenueModel === this.string.payPerUse) {
            // Pay-per-Use purchases are not allowed for prices > 5.00 Euro
            singleSale.prop('checked', true);
            $('label', this.object.revenueModel).removeClass(this.string.selected);
            singleSale.parent('label').addClass(this.string.selected);
        } else if (price < this.data.currency.sis_min && currentRevenueModel === this.string.singleSale) {
            // Single Sale purchases are not allowed for prices < 1.49 Euro
            payPerUse.prop('checked', true);
            $('label', this.object.revenueModel).removeClass(this.string.selected);
            payPerUse.parent('label').addClass(this.string.selected);
        }
    }
}