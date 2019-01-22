/**
 * Copyright © 2016 Collins Harper. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'jquery',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url',
        'Moneris_CreditCard/js/action/set-payment-method',
        'Magento_Checkout/js/action/set-billing-address'
    ],
    function (ko,Component, quote,$,placeOrderAction,selectPaymentMethodAction,customer, checkoutData, additionalValidators, mageurl,setPaymentMethodAction,setBillingAddress) {
        'use strict';
       
        return Component.extend({
            defaults: {
                template: 'Moneris_CreditCard/payment/chmonerisinterac'
            },

            getCode: function () {
                return 'chmonerisinterac';
            },
            
            getRedirectionText: function () {
                return '';   //'You will be transfered to Moneris to complete your purchase when using this payment method.';
            },
            continueInteracAcceptant: function () {
                var me = this;
                setBillingAddress();
                if (additionalValidators.validate()) {
                    //update payment method information if additional data was changed
                    this.selectPaymentMethod();
                    setPaymentMethodAction(this.messageContainer).done(
                            function () {
                                var paymentToken = '';
                                $.ajax({
                                    url: window.authenticationPopup.baseUrl + 'moneriscc/interac/referrer',
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

            
        });
    }
);
