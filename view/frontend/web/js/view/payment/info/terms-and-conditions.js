define([
    'uiComponent',
    'Magento_Checkout/js/model/totals',
    'mage/translate'
], function (
    Component,
    totals,
    $t
) {
    'use strict';

    return Component.extend({
        getTermsText: function () {
            return $t('You will be redirected to the Clearpay website when you proceed to checkout.');
        },
        getTermsLink: function () {
            return $t("https://www.clearpay.co.uk/en-GB/terms-of-service");
        },
    });
});
