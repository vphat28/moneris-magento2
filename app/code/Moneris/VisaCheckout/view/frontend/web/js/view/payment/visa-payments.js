/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';

        var chCollinsHarperVisa = 'chvisa';
        var config = window.checkoutConfig.payment[chCollinsHarperVisa];


        if (config.isDeveloperMode === "true") {
            rendererList.push(
                {
                    type: chCollinsHarperVisa,
                    component: 'Moneris_VisaCheckout/js/view/payment/method-renderer/visa-test-method'
                }
            );
        } else {
            rendererList.push(
                {
                    type: chCollinsHarperVisa,
                    component: 'Moneris_VisaCheckout/js/view/payment/method-renderer/visa-method'
                }
            );
        }
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
