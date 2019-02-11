/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */

define(
    [
        'ko',
        'jquery',
        'Magento_Payment/js/view/payment/cc-form',
        'Magento_Checkout/js/action/place-order',
        'Magento_Payment/js/model/credit-card-validation/validator',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Ui/js/modal/alert',
        'Magento_Checkout/js/action/redirect-on-success',
        'mage/url',
        'Magento_Customer/js/model/customer',
        'underscore'
    ],
    function (ko,$,Component,placeOrderAction,validator,additionalValidators,alert,redirectOnSuccessAction,url, customer, _) {
        'use strict';
        var config=window.checkoutConfig.payment.chmoneriscc;
        console.log(config);
        return Component.extend({
            isActive:function () {
                return true;
            },

            isRecurringEnable: function () {
                if (customer.isLoggedIn() && config.isRecurring) {
                    return true;
                }
                return false;
            },

            getRecurringTerms: function () {
                return _.map(config.supportedRecurringTerms, function (value, key) {
                    return {
                        'value': key,
                        'text': value
                    };
                });
            },

            defaults: {
                template: 'Moneris_CreditCard/payment/chmoneriscc',
                isCcFormShown: true,
                recurring: false,
                recurringTerm: null,
                save: config ? config.canSaveCard && config.defaultSaveCard : false,
                selectedCard: config ? config.selectedCard : '',
                storedCards: config ? config.storedCards : {},
                creditCardExpMonth: config ? config.creditCardExpMonth : null,
                creditCardExpYear: config ? config.creditCardExpYear : null,
            },
            initVars:function () {
                this.canSaveCard     = config ? config.canSaveCard : false;
                this.forceSaveCard   = config ? config.forceSaveCard : false;
                this.defaultSaveCard = config ? config.defaultSaveCard : false;
                this.redirectAfterPlaceOrder = config ? config.redirectAfterPlaceOrder : false;
            },
            /**
             */
            initObservable:function () {
                this.initVars();
                this._super()
                    .observe([
                        'selectedCard',
                        'save',
                        'storedCards',
                        'requireCcv',
                        'recurring',
                        'recurringTerm'
                    ]);

                this.isCcFormShown = ko.computed(function () {
                    return !this.useVault()
                        || this.selectedCard() === undefined
                        || this.selectedCard() == '';
                }, this);

                this.isCcvShown = ko.computed(function () {
                    return this.requireCcv()
                        || !this.useVault()
                        || this.selectedCard() === undefined
                        || this.selectedCard() == '';
                }, this);

                return this;
            },
            /**
             * @override
             */
            getData:function () {
                return {
                    'method': this.getCode(),
                    additional_data: {
                        'save': this.save(),
                        'recurring': this.recurring(),
                        'recurringTerm': this.recurringTerm(),
                        'cc_type': this.selectedCardType() != '' ? this.selectedCardType() : this.creditCardType(),
                        'cc_exp_year': this.creditCardExpYear(),
                        'cc_exp_month': this.creditCardExpMonth(),
                        'cc_number': this.creditCardNumber(),
                        'cc_cid': this.creditCardVerificationNumber(),
                        'vault_id': this.selectedCard()
                    }
                };
            },
            getCode: function () {
                return 'chmoneriscc';
            },
            useVault: function () {
                return this.getStoredCards().length > 0;
            },
            isShowLegend: function () {
                return true;
            },
            isCcDetectionEnabled: function () {
                return true;
            },
            getStoredCards: function () {
                return this.storedCards();
            },
            context: function () {
                return this;
            },
            hasVerification: function () {
                return window.checkoutConfig.payment.ccform.hasVerification[this.getCode()];
            },

            /**
             * @override
             */
            placeOrder: function (data, event) {
                var self = this;

                if (event) {
                    event.preventDefault();
                }

                if (this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);

                    this.getPlaceOrderDeferredObject()
                        .fail(
                            function () {
                                self.isPlaceOrderActionAllowed(true);
                            }
                        ).done(
                            function () {
                                if (self.redirectAfterPlaceOrder) {
                                    redirectOnSuccessAction.execute();
                                }else {
                                    self.afterPlaceOrder();
                                }
                            }
                        );

                    return true;
                }

                return false;
            },
            /**
             * After place order callback
             */
            afterPlaceOrder: function () {
                window.location.replace(url.build('moneriscc/index/redirect'));
            },
            /**
             * @override
             */
            validate: function () {
                var $form = $('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            }
        });
    }
);