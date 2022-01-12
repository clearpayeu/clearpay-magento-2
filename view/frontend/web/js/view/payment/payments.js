define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';

    rendererList.push(
        {
            type: 'clearpay',
            component: 'Clearpay_Clearpay/js/view/payment/method-renderer/clearpay'
        }
    );

    return Component.extend({});
});
