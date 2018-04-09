const $ = jQuery;

class LaterPayAdminAdvanced {
    constructor(options) {
        this.container = $(options.container);

        this.i18n = {
            confirmTitle: options.i18n.confirmTitle,
            proMerchantMessage: options.i18n.proMerchantMessage,
            contributionMessage: options.i18n.contributionMessage,
            donationMessage: options.i18n.donationMessage,
            confirm: options.i18n.confirm,
            cancel: options.i18n.cancel
        };

        this.object = {
            form: $('form', this.container),
            navigation: $('.lp_navigation'),
            unlimitedAccess: {
                none: $('.lp_access-none', this.container),
                all: $('.lp_access-all', this.container),
                input: $('.lp_category-access-input', this.container),
            },
            proMerchant: $('#lp_js_proMerchant', this.container),
            businessModel: $('#lp_js_businessModel', this.container),
            businessModelPrevious: $('#lp_js_businessModel', this.container).val()
        };

        this
            ._bindEvents()
            ._prepareUnlimitedAccess();
    }

    _bindEvents() {
        this.object.form
            .bind('submit', e => {
                e.preventDefault();
                this._saveForm();
            });

        this.object.unlimitedAccess.none
            .bind('change', e => {
                this._toggleUnlimitedAccessNone(e.target);
            });

        this.object.unlimitedAccess.all
            .bind('change', e => {
                this._toggleUnlimitedAccessAll(e.target);
            });

        this.object.proMerchant
            .bind('change', () => {
                this._toggleProMerchant();
            });

        this.object.businessModel
            .bind('change', () => {
                this._toggleBusinessModel();
            });

        return this;
    }

    _saveForm() {
        $.post(
            ajaxurl,
            this.object.form.serializeArray(),
            (data) => {
                this.object.navigation.showMessage(data);
            },
            'json'
        );
    }

    _toggleUnlimitedAccessNone(e) {
        let el = $(e),
            categories = el.closest('tr').find(this.object.unlimitedAccess.input),
            all = el.closest('tr').find(this.object.unlimitedAccess.all);

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

    _toggleUnlimitedAccessAll(e) {
        let el = $(e),
            categories = el.closest('tr').find(this.object.unlimitedAccess.input),
            none = el.closest('tr').find(this.object.unlimitedAccess.none);

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

    _prepareUnlimitedAccess() {
        this.object.unlimitedAccess.none.each((i, el) => {
            this._toggleUnlimitedAccessNone(el);
        });
        this.object.unlimitedAccess.all.each((i, el) => {
            this._toggleUnlimitedAccessAll(el);
        });
    }

    _toggleProMerchant() {
        let dialog = $('<div>' + this.i18n.proMerchantMessage + '</div>').dialog({
            resizable: false,
            title: this.i18n.confirmTitle,
            height: "auto",
            modal: true,
            buttons: [
                {
                    text: this.i18n.confirm,
                    click: () => {
                        dialog.dialog("close");
                        this.object.businessModel.val('paid');
                        this.object.businessModelPrevious = 'paid';
                    },
                },
                {
                    text: this.i18n.cancel,
                    click: () => {
                        dialog.dialog("close");
                        this.object.proMerchant.removeAttr('checked');
                    }
                },
            ]
        });
    }

    _checkCurrentBusinessModel() {
        // disable pro feature if non paid business model
        if (this.object.businessModel.val() !== 'paid') {
            this.object.proMerchant
                .attr('disabled', 'disabled')
                .removeAttr('checked');
        } else {
            // reset business model to paid
            this.object.proMerchant
                .removeAttr('disabled');
        }
    }

    _toggleBusinessModel() {
        let selectedValue = this.object.businessModel.val(),
            message = selectedValue === 'contribution' ? this.i18n.contributionMessage : this.i18n.donationMessage;

        // do nothing for "paid" model
        if (selectedValue === 'paid') {
            this.object.businessModelPrevious = selectedValue;
            this._checkCurrentBusinessModel();
            return;
        }

        let dialog = $('<div>' + message + '</div>').dialog({
            resizable: false,
            title: this.i18n.confirmTitle,
            height: "auto",
            modal: true,
            buttons: [
                {
                    text: this.i18n.confirm,
                    click: () => {
                        dialog.dialog("close");

                        this._checkCurrentBusinessModel();
                        this.object.businessModelPrevious = selectedValue;
                    },
                },
                {
                    text: this.i18n.cancel,
                    click: () => {
                        dialog.dialog("close");
                        this.object.businessModel.val(this.object.businessModelPrevious);
                    }
                },
            ]
        });
    }
}