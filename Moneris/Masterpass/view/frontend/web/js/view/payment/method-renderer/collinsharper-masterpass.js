define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/model/quote',
        'Magento_Payment/js/view/payment/cc-form',
        'Magento_Checkout/js/action/set-billing-address',
        'Moneris_Masterpass/js/action/set-payment-method',
        'mage/url'
    ],
    function ($, ko, quote, Component, setBillingAddress, setPaymentMethodAction, urlBuilder) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Moneris_Masterpass/payment/collinsharper-masterpass',
                code: 'chmasterpass'
            },
            initialize: function () {
                this._super();
            },
            getCode: function () {
                return 'chmasterpass';
            },
            getTitle: function () {
                return window.checkoutConfig.payment[this.getCode()].title;
            },
            isActive: function () {
                return window.checkoutConfig.payment[this.getCode()].active;
            },
            getCcAvailableTypes: function () {
                return window.checkoutConfig.payment.ccform.availableTypes['chmasterpass'];
            },
            getCcAvailableTypesValues: function () {
                return _.map(this.getCcAvailableTypes(), function (value, key) {
                    return {
                        'value': key,
                        'type': value
                    };
                });
            },
            getCcTypeTitleByCode: function (code) {
                var title = '';
                var keyValue = 'value';
                var keyType = 'type';

                _.each(this.getCcAvailableTypesValues(), function (value) {
                    if (value[keyValue] === code) {
                        title = value[keyType];
                    }
                });
                return title;
            },
            placeOrder: function () {
                $('body').trigger('processStart');
                var guestEmail = '';
                if (quote.guestEmail != '' || quote.guestEmail != undefined) {
                    guestEmail = quote.guestEmail;
                }
                $.post(urlBuilder.build("chmasterpass/index/payment"), {guestEmail: guestEmail}, function (data) {
                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                    }
                    console.log(data.redirect_url);
                    console.log(data);
                }, 'json');
            }
        });
    }
);


