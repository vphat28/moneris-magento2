define([
    'jquery',
    'Magento_Ui/js/modal/alert'
], function ($, alert) {
    'use strict';

    return function (config, element) {
        var url = "https://gatewaydev.moneris.com/chkt/js/chkt_v1.00.js";

        if (typeof window.MonerisCheckoutMagento === 'undefined') {
            window.MonerisCheckoutMagento = [];
        }

        $.getScript(url, function () {
            window.$ = $;
            $(document).ready(function () {
                var myCheckout = new monerisCheckout();
                var res = {};

                myCheckout.setMode("dev");
                myCheckout.setCheckoutDiv("monerisCheckout");

                myCheckout.setCallback("address_change", myAddressChange);
                myCheckout.setCallback("cancel_transaction", myCancelTransaction);
                myCheckout.setCallback("payment_receipt", myPaymentReceipt);
                myCheckout.setCallback("payment_complete", myPaymentComplete);
                myCheckout.setCallback("error_event", myErrorEvent);
                myCheckout.setCallback("page_loaded", myPageLoad);

                myCheckout.logConfig();

                //myCheckout.startCheckout("qwerty12345");

                function myAddressChange(msg) {
                    console.log("new address is: " + msg);
                    // got an address change send new shipping and total.

                    rates = {};
                    rates["action"] = "set_shipping_rates";
                    rates["data"] = {};

                    rate = {};
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

                    rates["data"] = rate;

                    json_rate = JSON.stringify(rates)
                    console.log(json_rate);

                    myCheckout.setNewShippingRates(json_rate);
                }

                function myCancelTransaction(data) {
                    console.log(data);
                    myCheckout.closeCheckout();
                }

                function myPaymentComplete(data) {
                    console.log("got payment complete");
                    console.log(data);
                    myCheckout.closeCheckout();

                    res = JSON.parse(data);

                    $('<form method="POST"><input type="hidden" name="get_response" value="true"><input type="hidden" name="ticket" value="' + res.ticket + '"></form>').appendTo('body').submit();

                }

                function myPaymentReceipt(data) {
                    console.log("got payment Receipt");
                    console.log(data);
                }

                function myErrorEvent() {
                    alert("error");
                    myCheckout.closeCheckout();
                }

                function myPageLoad(data) {
                    console.log(data);
                    var load = JSON.parse(data);
                }

                $(element).click(function () {
                    myCheckout.startCheckout('1547580967l3Ikh0JHZgArYNopx1H5NceVyRdmP1');
                });
            });
        });
    };
});