/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */

define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'mage/url',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/redirect-on-success'
    ],
    function (ko, $, Component, urlBuilder, quote, additionalValidators, redirectOnSuccessAction) {
        'use strict';
        var config = window.checkoutConfig.payment.chmonerischeckout;

        return Component.extend({
            isActive: function () {
                return window.MonerisCheckoutConfig.enable;
            },
            monerisTicket: null,
            processedOrder: false,
            showReceipt: false,
            defaults: {
                template: 'Moneris_MonerisCheckout/payment/chmonerischeckout',

            },

            getInputedEmail: function () {
                if(quote.guestEmail) return quote.guestEmail;
                else return window.checkoutConfig.customerData.email;
            },

            getBillingAddress: function () {
                var billing = quote.billingAddress();
                return {
                    postcode: billing.postcode,
                    street: billing.street,
                    city: billing.city,
                    province: billing.region,
                    country: billing.countryId,
                }
            },

            placeMonerisOrder: function () {
                var urlXhr = urlBuilder.build('monerischeckout/request/getticket');

                var self = this;

                $.ajax({
                    showLoader: true,
                    url: urlXhr,
                    data: JSON.stringify({
                        "test": "OK",
                        "email": self.getInputedEmail(),
                        "billing": self.getBillingAddress(),
                    }),
                    type: 'post',
                    contentType: 'application/json'
                }).done(function (returnData) {
                    // Close payment sheet with success message
                    // self.cartId = returnData.quote_id;
                    if (typeof returnData !== 'undefined' &&
                        typeof returnData.ticket !== 'undefined' &&
                        returnData.ticket.length > 0
                    ) {
                        self.myCheckout.startCheckout(returnData.ticket);
                    } else {
                        var error_msg = '';

                        if (typeof returnData.error !== 'undefined') {
                            for (var i = 0; i < returnData.error.length; i++) {
                                error_msg += returnData.error[i].data + '\n';
                            }

                            alert(error_msg);
                        }
                    }
                }).fail(function () {
                    //fullScreenLoader.stopLoader();
                });
            },

            configureButton: function () {
                var self = this;
                $(document).ready(function () {
                    self.myCheckout = new monerisCheckout();

                    if (window.MonerisCheckoutConfig.mode == 'qa') {
                        self.myCheckout.setMode("dev");
                    } else {
                        self.myCheckout.setMode("prod");
                    }

                    self.myCheckout.setCheckoutDiv("monerisCheckout");

                    self.myCheckout.setCallback("cancel_transaction", myCancelTransaction);
                    self.myCheckout.setCallback("payment_receipt", myPaymentReceipt);
                    self.myCheckout.setCallback("payment_complete", myPaymentComplete);
                    self.myCheckout.setCallback("error_event", myErrorEvent);
                    self.myCheckout.setCallback("page_loaded", myPageLoad);

                    self.myCheckout.logConfig();

                    function myCancelTransaction(data) {
                        console.log(data);
                        self.myCheckout.closeCheckout();
                    }

                    function myPaymentComplete(data) {
                        data = JSON.parse(data);
                        console.log('Completing transaction');

                        if (self.showReceipt === true) {
                            if (!self.processedOrder) {
                                self.chargeRequest(data.ticket);
                            }
                            self.myCheckout.closeCheckout();
                            console.log(data);
                        } else {
                            self.chargeRequest(data.ticket);
                        }
                    }

                    function myPaymentReceipt(data) {
                        console.log('Got receipt for transaction');

                        self.showReceipt = true;
                        data = JSON.parse(data);

                        if (data.response_code === '001') {
                            self.chargeRequest(data.ticket);
                        } else {
                            alert('Transaction error. Please try again');
                            self.myCheckout.closeCheckout();
                        }
                    }

                    function myErrorEvent() {
                        console.log('error');
                        self.myCheckout.closeCheckout();
                    }

                    function myPageLoad(data) {
                        console.log(data);
                    }
                });
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
                                setTimeout(
                                    function () {
                                        self.myCheckout.closeCheckout();
                                        redirectOnSuccessAction.execute();
                                    }, 7500);
                            } else {
                                self.afterPlaceOrder();
                            }
                        }
                    );

                    return true;
                }

                return false;
            },

            chargeRequest: function (ticket) {
                //this.myCheckout.closeCheckout();
                this.monerisTicket = ticket;
                this.processedOrder = true;
                this.placeOrder();
            },

            initButton: function () {
                var self = this;

                $('body').append('<div id="monerisCheckout" style="z-index: 9999"></div>');

                if ( window.MonerisCheckoutConfig.mode == 'qa' ) {
                    var url = "https://gatewayt.moneris.com/chkt/js/chkt_v1.00.js";
                } else {
                    var url = "https://gateway.moneris.com/chkt/js/chkt_v1.00.js";
                }

                $.getScript(url, function () {
                    self.configureButton();
                });
            },

            /**
             */
            initObservable: function () {
                this._super();

                this.initButton();

                return this;
            },
            /**
             * @override
             */
            getData: function () {
                if (this.monerisTicket !== null) {
                    return {
                        'method': 'chmoneriscc',
                        'additional_data': {
                            'moneris_checkout_ticket': this.monerisTicket
                        }
                    };
                } else {
                    return {
                        'method': 'monerisinstantcheckout',
                        'additional_data': {
                            'moneris_checkout_ticket': this.monerisTicket
                        }
                    };
                }
            },
            getCode: function () {
                return 'monerisinstantcheckout';
            },
            getTitle: function () {
                return window.checkoutConfig.payment.chmoneriscc.title;
            },
        });
    }
);
