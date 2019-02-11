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
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/checkout-data',
        'Moneris_CreditCard/js/action/set-payment-method',
        'Magento_Checkout/js/action/set-billing-address'
    ],
    function (ko, $, Component, placeOrderAction,validator, additionalValidators, alert,redirectOnSuccessAction,url, selectPaymentMethodAction, checkoutData, setPaymentMethodAction,setBillingAddress) {
        'use strict';
        var config=window.checkoutConfig.payment.chmonerisredirect;
        return Component.extend( {
            isActive: function () {
                return true;
            },
            defaults: {
                template: 'Moneris_CreditCard/payment/chmonerisredirect',
                isCardListShown: false,
                save: config ? config.canSaveCard && config.defaultSaveCard : false,
                selectedCard: config ? config.selectedCard : '',
                storedCards: config ? config.storedCards : {},
            },
            initVars: function () {
                this.canSaveCard     = config ? config.canSaveCard : false;
                this.redirectAfterPlaceOrder = config ? config.redirectAfterPlaceOrder : false;
            },
            /**
             * @override
             */
            initObservable: function () {
                this.initVars();
                this._super()
                    .observe([
                        'selectedCard',
                        'save',
                        'storedCards'
                    ]);

                this.isCardListShown = ko.computed(function () {
                    return this.useVault() && this.save();
                }, this);

                return this;
            },
            /**
             * @override
             */
            getData: function () {
                return {
                    'method': this.getCode(),
                    additional_data: {
                        'save': this.save(),
                        'vault_id': this.selectedCard()
                    }
                };
            },
            getCode: function () {
                return 'chmonerisredirect';
            },
            useVault: function () {
                return this.getStoredCards().length > 0;
            },
            isShowLegend: function () {
                return true;
            },
            getStoredCards: function () {
                return this.storedCards();
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
            
            continueCybersourceSecureAcceptant: function () {
                var me = this;
                setBillingAddress();
                if (additionalValidators.validate()) {
                    //update payment method information if additional data was changed
                    this.selectPaymentMethod();
                    setPaymentMethodAction(this.messageContainer).done(
                            function () {
                            	var paymentToken = '';
                                $.ajax({
                                    url: window.authenticationPopup.baseUrl + 'moneriscc/index/loadredirect',
                                    type: "post",
                                    dataType: 'json',
                                    success: function (data) {
                                            var form = $(document.createElement('form'));
                                            $(form).attr("action", data.request_url);
                                            $(form).attr("method", "POST");
                                            for(var k in data) {
                                                $(form).append('<input name="'+k+'" value="'+data[k]+'"/>');
                                            }
                                            
                                            $(form).append('<TYPE="SUBMIT" NAME="SUBMIT" VALUE="Click to proceed to Secure Page">');
                                            $(form).css("display","none");
                                            $('body').append(form);
                                            $(form).submit();
                                    }
                                });
                            }
                        );
                    //return false;
                }
            },

            selectPaymentMethod: function () {
                selectPaymentMethodAction(this.getData());
                checkoutData.setSelectedPaymentMethod(this.item.method);
                return true;
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