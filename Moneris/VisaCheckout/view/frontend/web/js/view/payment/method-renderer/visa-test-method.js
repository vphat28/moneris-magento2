define(
    [
        'jquery',
        'Magento_Payment/js/view/payment/cc-form',
        'visaSdkSandBox',
        'Moneris_VisaCheckout/js/action/set-payment-method',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/action/set-billing-address',
        'jquery/ui',
        'mage/translate'
    ],
    function (
        $,
        Component,
        visaSdkSandBox,
        setPaymentMethodAction,
        additionalValidators,
        quote,
        customerData,
        setBillingAddress
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                active: false,
                template: 'Moneris_VisaCheckout/payment/visa-form',
                code: 'chvisa',
                grandTotalAmount: null,
                currencyCode: null,
                imports: {
                    onActiveChange: 'active'
                }
            },
            initObservable: function () {
                var self = this;
                this._super()
                    .observe(['active']);
                this.grandTotalAmount = quote.totals()['base_grand_total'];
                this.currencyCode = quote.totals()['base_currency_code'];

                quote.totals.subscribe(function () {
                    if (self.grandTotalAmount !== quote.totals()['base_grand_total']) {
                        self.grandTotalAmount = quote.totals()['base_grand_total'];
                    }

                    if (self.currencyCode !== quote.totals()['base_currency_code']) {
                        self.currencyCode = quote.totals()['base_currency_code'];
                    }
                });

                return this;
            },
            onActiveChange: function (isActive) {
                if (!isActive) {
                    return;
                }

                this.initVisaCheckout(this.currencyCode, this.grandTotalAmount);
            },
            getCode: function () {
                return this.code;
            },
            isActive: function () {
                var active = this.getCode() === this.isChecked();

                this.active(active);

                return active;
            },
            getTitle: function () {
                return window.checkoutConfig.payment['chvisa'].title;
            },
            getApiKey: function () {
                return window.checkoutConfig.payment['chvisa'].api_key;
            },
            getData: function () {
                return {
                    'method': this.item.method
                };
            },
            getButtonUrl: function () {
                return "https://sandbox.secure.checkout.visa.com/wallet-services-web/xo/button.png";
            },

            placeOrder: function (callId) {
                setBillingAddress();
                if (additionalValidators.validate()) {
                    this.selectPaymentMethod();
                    setPaymentMethodAction(this.messageContainer).done(
                        function () {
                            var form = $(document.createElement('form'));
                            $(form).attr("action", window.checkoutConfig.payment['chvisa'].success_url);
                            $(form).attr("method", "POST");
                            $(form).append('<input name="callId" value="'+callId+'"/>');
                            $(form).append('<input name="quoteId" value="'+quote.getQuoteId()+'"/>');
                            $("body").append(form);
                            $(form).submit();
                        }
                    );
                    return false;
                }
            },

            initVisaCheckout: function (currencyCode, totalAmount) {
                var self = this;
                V.init({
                    apikey: self.getApiKey(),
                    paymentRequest:{
                        currencyCode: currencyCode,
                        subtotal: totalAmount
                    }
                });
                V.on("payment.success", function (payment) {
                
                    var callId = payment.callid;
                    self.placeOrder(callId);
                });

                V.on("payment.error", function (payment, error) {
                
                    console.log(JSON.stringify(error));
                });
            }
        });
    }
);