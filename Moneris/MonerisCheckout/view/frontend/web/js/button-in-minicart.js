define(
    [
        'uiComponent',
        'jquery',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/checkout-data',
        'underscore',
        'mage/url',
        'mage/translate',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function (Component, $, Data, Checkout, _, urlBuilder, $t, fullScreenLoader) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Moneris_MonerisCheckout/button-in-cart'
            },

            cartId: null,

            initialize: function () {
                this._super();

                if (
                    $('body').hasClass('checkout-index-index') ||
                    $('body').hasClass('checkout-cart-index')
                ) {
                    this.isInCheckoutPages = true;
                }

            },

            myCheckout: {},
            formattedAddress: {
            },
            isInCheckoutPages: false,

            initMonerisCheckout: function () {
                this.initButton();
            },

            openIframe: function () {
                fullScreenLoader.startLoader();
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
                    self.cartId = returnData.quote_id;
                    self.myCheckout.startCheckout(returnData.ticket);
                }).fail(function () {
                    fullScreenLoader.stopLoader();
                });
            },

            configureButton: function () {
                var self = this;
                window.$ = $;
                $(document).ready(function () {
                    self.myCheckout = new monerisCheckout();
                    var res = {};
                    self.myCheckout.setMode("dev");
                    self.myCheckout.setCheckoutDiv("monerisCheckout");

                    self.myCheckout.setCallback("address_change", myAddressChange);
                    self.myCheckout.setCallback("cancel_transaction", myCancelTransaction);
                    self.myCheckout.setCallback("payment_receipt", myPaymentReceipt);
                    self.myCheckout.setCallback("payment_complete", myPaymentComplete);
                    self.myCheckout.setCallback("error_event", myErrorEvent);
                    self.myCheckout.setCallback("page_loaded", myPageLoad);

                    self.myCheckout.logConfig();

                    //myCheckout.startCheckout("qwerty12345");

                    function myAddressChange(msg) {
                        console.log("new address is: ");
                        console.log(Data.get('cart')().summary_count);
                        var response = JSON.parse(msg);
                        console.log(response);
                        var urlXhr;

                        if (!self.isGuest()) {
                            urlXhr = urlBuilder.build('rest/V1/carts/' + 'mine' + '/estimate-shipping-methods');
                        } else {
                            urlXhr = urlBuilder.build('rest/V1/guest-carts/' + self.cartId + '/estimate-shipping-methods');
                        }

                        var address = response.address;
                        var newAddress = {
                            firstname: 'test',
                            lastname: 'test',
                            street: [address.address_1, address.address_2],
                            city: address.city,
                            countryId: address.country,
                            postcode: address.postal_code,
                            region: address.province,
                            telephone: '123456789',
                        };

                        self.formattedAddress = newAddress;

                        $.ajax({
                            url: urlXhr,
                            data: JSON.stringify({ 'address': self.formattedAddress }),
                            type: 'post',
                            dataType: 'json',
                            contentType: 'application/json'
                        }).done(function (result) {
                            var rates = {};
                            rates["action"] = "set_shipping_rates";
                            rates["data"] = {};

                            var shippingOptions = [];

                            for (var i = 0; i < result.length; i++) {
                                shippingOptions.push({
                                    'code': result[i]['carrier_code'] + '|' + result[i]['method_code'],
                                    'description': result[i]['method_title'],
                                    'date': result[i]['carrier_title'],
                                    'amount': result[i]['amount'],
                                    'txn_taxes': 0,
                                    'txn_total': parseFloat(Data.get('cart')().subtotalAmount) + result[i]['amount'],
                                });
                            }

                            rates["data"] = shippingOptions;
                            var json_rate = JSON.stringify(rates)
                            console.log(json_rate);
                            self.myCheckout.setNewShippingRates(json_rate);
                        });
                    }

                    function myCancelTransaction(data) {
                        console.log(data);
                        fullScreenLoader.stopLoader();
                        self.myCheckout.closeCheckout();
                    }

                    function myPaymentComplete(data) {
                        fullScreenLoader.startLoader();
                        self.myCheckout.closeCheckout();
                    }

                    function myPaymentReceipt(data) {
                        fullScreenLoader.startLoader();
                        var json = JSON.parse(data);
                        var urlXhr = urlBuilder.build('monerischeckout/request/getreceipt');

                        $.post(urlXhr, json, function(data) {
                            self.chargeRequest(data.data.request);
                        }, "json");
                    }

                    function myErrorEvent() {
                        console.log('error');
                        self.myCheckout.closeCheckout();
                    }

                    function myPageLoad(data) {
                        console.log(data);
                        var load = JSON.parse(data);
                    }
                });
            },

            initButton: function () {
                var self = this;

                if (!this.isInCheckoutPages) {
                    $('body').append('<div id="monerisCheckout" style="z-index: 9999"></div>');
                    var url = "https://gatewaydev.moneris.com/chkt/js/chkt_v1.00.js";

                    $.getScript(url, function () {
                        self.configureButton();
                    });
                } else {
                    self.configureButton();
                }
            },

            chargeRequest: function (ev) {
                var self = this;

                if (self.isGuest()) {
                    self.makeGuestShipping(ev);
                } else {
                    self.makeMineOrderShipping(ev);
                }
            },

            makeMineOrderShipping: function (ev) {
                console.log('make min order shipping');
                var self = this;
                var urlXhr = urlBuilder.build('rest/V1/carts/' + 'mine' + '/shipping-information');
                var formattedAddress = self.formattedAddress;
                var shippingAddress = formattedAddress;
                var shippingOption = ev.ship_rates.code;
                shippingOption = shippingOption.split('|');
                shippingAddress.same_as_billing = 1;

                $.ajax({
                    url: urlXhr,
                    data: JSON.stringify({
                        addressInformation: {
                            'shipping_address': shippingAddress,
                            'billing_address': formattedAddress,
                            'shipping_method_code': shippingOption[0],
                            'shipping_carrier_code': shippingOption[1],
                        }
                    }),
                    type: 'post',
                    dataType: 'json',
                    contentType: 'application/json'
                }).done(function (returnData) {
                    self.makeMineOrder(ev);
                }).fail(function () {
                    console.log('can not make order');
                });
            },

            makeGuestShipping: function (ev) {
                var self = this;
                var cartId = self.cartId;
                var urlXhr = urlBuilder.build('rest/V1/guest-carts/' + cartId + '/shipping-information');
                var formattedAddress = this.formattedAddress;
                var shippingAddress = formattedAddress;
                shippingAddress.same_as_billing = 1;
                var shippingOption = ev.ship_rates.code;
                shippingOption = shippingOption.split('|');
                shippingAddress.same_as_billing = 1;

                $.ajax({
                    url: urlXhr,
                    data: JSON.stringify({
                        addressInformation: {
                            'shipping_address': shippingAddress,
                            'billing_address': formattedAddress,
                            'shipping_method_code': shippingOption[0],
                            'shipping_carrier_code': shippingOption[1],
                        }
                    }),
                    type: 'post',
                    dataType: 'json',
                    contentType: 'application/json'
                }).done(function (returnData) {
                    self.makeQuestOrder(ev);
                }).fail(function () {
                });
            },

            makeMineOrder: function (ev) {
                var self = this;
                var urlXhr = urlBuilder.build('rest/V1/carts/' + 'mine' + '/payment-information');
                var paymentInformation = {
                    'email': ev.cust_info.email,
                    'paymentMethod': {
                        'method': 'chmoneriscc',
                        'additional_data': {
                            'moneris_checkout_ticket': ev.ticket
                        }
                    },
                    'billingAddress': self.formattedAddress
                };

                $.ajax({
                    url: urlXhr,
                    data: JSON.stringify(paymentInformation),
                    type: 'post',
                    dataType: 'json',
                    contentType: 'application/json'
                }).done(function () {
                    Data.set('cart', {});
                    window.location.href = urlBuilder.build('checkout/onepage/success');
                });
            },

            makeQuestOrder: function (ev) {
                var self = this;
                var urlXhr = urlBuilder.build('rest/V1/guest-carts/' + self.cartId + '/payment-information');
                var paymentInformation = {
                    'email': ev.cust_info.email,
                    'paymentMethod': {
                        'method': 'chmoneriscc',
                        'additional_data': {
                            'moneris_checkout_ticket': ev.ticket
                        }
                    },
                    'billingAddress': self.formattedAddress
                };

                $.ajax({
                    url: urlXhr,
                    data: JSON.stringify(paymentInformation),
                    type: 'post',
                    dataType: 'json',
                    contentType: 'application/json'
                }).done(function () {
                    Data.set('cart', {});
                    fullScreenLoader.stopLoader();
                    window.location.href = urlBuilder.build('checkout/onepage/success');
                });
            },

            isGuest: function () {
                var mageStorage = JSON.parse(window.localStorage['mage-cache-storage']);

                if (typeof mageStorage.customer === 'undefined') {
                    return true;
                }

                if (typeof mageStorage.customer.firstname === 'undefined') {
                    return true;
                }

                return false;
            }
        });
    }
);
