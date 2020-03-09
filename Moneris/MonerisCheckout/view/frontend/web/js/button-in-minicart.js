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
            isInCheckoutPages: false,

            initMonerisCheckout: function () {
                this.initButton();
            },

            openIframe: function () {
                fullScreenLoader.startLoader();
                var urlXhr = urlBuilder.build('monerischeckout/request/getticket');
                var self = this;
               /* self.myCheckout.startCheckout('1549295979bOLpvtgDROtU97uS0oIzTeRdGP1Ma5');
                return;
*/

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
                    self.myCheckout.startCheckout(returnData);
                    fullScreenLoader.stopLoader();
                }).fail(function () {
                    fullScreenLoader.stopLoader();
                });
            },

            calculateShipping: function (address) {

            },

            configureButton: function () {
                var self = this;
                window.$ = $;
                $(document).ready(function () {
                    self.myCheckout = new monerisCheckout();
                    var res = {};

                    console.log('a');

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
                        console.log("new address is: " + msg);
                        // got an address change send new shipping and total.

                        var rates = {};
                        rates["action"] = "set_shipping_rates";
                        rates["data"] = {};

                        var rate = {};
                        rate[0] = {};
                        rate[0]["code"] = "rateM1";
                        rate[0]["description"] = "Standard R";
                        rate[0]["date"] = "4 days";
                        rate[0]["amount"] = "Free";
                        rate[0]["txn_taxes"] = "0.01";
                        rate[0]["txn_total"] = "1.00";
                        rate[0]["default_rate"] = "false";

                        rate[1] = {};
                        rate[1]["code"] = "rateM2";
                        rate[1]["description"] = "Express R";
                        rate[1]["date"] = "0.5 day";
                        rate[1]["amount"] = "11.01";
                        rate[1]["txn_taxes"] = "2.01";
                        rate[1]["txn_total"] = "13.01";
                        rate[1]["default_rate"] = "true";

                        rate[2] = {};
                        rate[2]["code"] = "rateM3";
                        rate[2]["description"] = "Deliery by foot";
                        rate[2]["date"] = "1 week";
                        rate[2]["amount"] = "6.00";
                        rate[2]["txn_taxes"] = "1.51";
                        rate[2]["txn_total"] = "16.23";
                        rate[2]["default_rate"] = "true";

                        rate[3] = {};
                        rate[3]["code"] = "rateM3xavi";
                        rate[3]["description"] = "Deliery by xavi";
                        rate[3]["date"] = "1 week";
                        rate[3]["amount"] = "61.00";
                        rate[3]["txn_taxes"] = "11.51";
                        rate[3]["txn_total"] = "161.23";
                        rate[3]["default_rate"] = "true";

                        rates["data"] = rate;

                        var json_rate = JSON.stringify(rates)
                        console.log(json_rate);

                        self.myCheckout.setNewShippingRates(json_rate);
                    }

                    function myCancelTransaction(data) {
                        console.log(data);
                        self.myCheckout.closeCheckout();
                    }

                    function myPaymentComplete(data) {
                        console.log("got payment complete");
                        console.log(data);
                        self.myCheckout.closeCheckout();

                        res = JSON.parse(data);

                        $('<form method="POST"><input type="hidden" name="get_response" value="true"><input type="hidden" name="ticket" value="' + res.ticket + '"></form>').appendTo('body').submit();

                    }

                    function myPaymentReceipt(data) {
                        console.log("got payment Receipt");
                        console.log(data);
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
                    $('body').append('<div id="monerisCheckout"></div>');
                    var url = "https://gatewaydev.moneris.com/chkt/js/chkt_v1.00.js";

                    $.getScript(url, function () {
                        console.log('loaded');
                        self.configureButton();
                    });
                } else {
                    self.configureButton();
                }
            }
        });
    }
);
