/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */

define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'mage/url'
    ],
    function (ko, $, Component, urlBuilder) {
        'use strict';
        var config = window.checkoutConfig.payment.chmonerischeckout;

        return Component.extend({
            isActive: function () {
                return true;
            },
            monerisTicket: null,
            defaults: {
                template: 'Moneris_MonerisCheckout/payment/chmonerischeckout',

            },

            placeMonerisOrder: function () {
                var urlXhr = urlBuilder.build('monerischeckout/request/getticket');

                var self = this;

                $.ajax({
                    showLoader: true,
                    url: urlXhr,
                    data: JSON.stringify({
                        "test": "OK"
                    }),
                    type: 'post',
                    contentType: 'application/json'
                }).done(function (returnData) {
                    // Close payment sheet with success message
                    // self.cartId = returnData.quote_id;
                    self.myCheckout.startCheckout(returnData.ticket);
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
                        console.log(data);
                        self.myCheckout.closeCheckout();
                    }

                    function myPaymentReceipt(data) {
                        data = JSON.parse(data);
                        console.log(data);

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

            chargeRequest: function (ticket) {
                this.myCheckout.closeCheckout();
                this.monerisTicket = ticket;
                this.placeOrder();
            },

            initButton: function () {
                var self = this;

                $('body').append('<div id="monerisCheckout" style="z-index: 9999"></div>');
                var url = "https://gatewayt.moneris.com/chkt/js/chkt_v1.00.js";

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
        });
    }
);
