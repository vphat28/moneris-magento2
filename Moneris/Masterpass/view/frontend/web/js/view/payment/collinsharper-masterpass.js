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
        rendererList.push({
            type: 'chmasterpass',
            component: 'Moneris_Masterpass/js/view/payment/method-renderer/collinsharper-masterpass'
        });
        /** Add view logic here if needed */
        return Component.extend({});
    }
);


