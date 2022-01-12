define([
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Magento_Catalog/js/price-utils',
    'Magento_Checkout/js/model/totals',
    'mage/translate'
], function (
    Component,
    quote,
    priceUtils,
    totals,
    $t
) {
    'use strict';

    return Component.extend({
        getFirstInstalmentText: function () {
            let clearpayFirstInstalmentText = '';

            switch(totals.totals().quote_currency_code){
                case 'USD':
                case 'CAD':
                    clearpayFirstInstalmentText = $t('Due today');
                    break;
                default:
                    clearpayFirstInstalmentText = $t('First instalment');

            }
            return clearpayFirstInstalmentText;
        },
        getCheckoutText: function() {
            let clearpayCheckoutText = '';
            switch(totals.totals().quote_currency_code){
                case 'USD':
                    clearpayCheckoutText = $t('4 interest-free installments of');
                    break;
                case 'CAD':
                    clearpayCheckoutText = $t('4 interest-free instalments of');
                    break;
                default:
                    clearpayCheckoutText = $t('Four interest-free payments totalling');
            }
            return clearpayCheckoutText;
        },
        getClearpayTotalAmount: function () {
            let amount = totals.totals().base_grand_total;
            switch(totals.totals().quote_currency_code){
                case 'USD':
                case 'CAD':
                    amount = totals.totals().base_grand_total / 4;
            }
            return priceUtils.formatPrice(amount.toFixed(2), quote.getPriceFormat());
        },
        getInstallmentAmount: function () {
            return priceUtils.formatPrice((totals.totals().base_grand_total / 4).toFixed(2), quote.getPriceFormat());
        },
        getLastInstallmentAmount: function () {
            return priceUtils.formatPrice(totals.totals().base_grand_total - 3 * (totals.totals().base_grand_total / 4).toFixed(2), quote.getPriceFormat());
        }
    });
});
