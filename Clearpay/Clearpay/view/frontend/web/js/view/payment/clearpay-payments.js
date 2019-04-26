/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
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
        rendererList.push(
            {
                type: 'clearpaypayovertime',
                component: 'Clearpay_Clearpay/js/view/payment/method-renderer/clearpaypayovertime'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
