define([
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Magento_Catalog/js/price-utils',
    'Magento_Checkout/js/model/totals',
    'mage/translate',
    'ko'
], function (
    Component,
    quote,
    priceUtils,
    totals,
    $t,
    ko
) {
    'use strict';

    return Component.extend({
        defaults: {
            dataLocale: window.checkoutConfig.payment.clearpay.locale,
            dataCurrency: ko.computed(() => checkoutConfig.payment.clearpay.isCBTCurrency === true
                ? totals.totals().quote_currency_code
                : totals.totals().base_currency_code
            ),
            dataAmount: ko.computed(() => checkoutConfig.payment.clearpay.isCBTCurrency === true
                ? totals.totals().grand_total
                : totals.totals().base_grand_total
            ),
        },
        getPriceTable: function() {
            return ko.computed(() =>
            '<afterpay-price-table ' +
                'data-amount="' + this.dataAmount() +
                '" data-locale="' + this.dataLocale +
                '" data-currency="' + this.dataCurrency() +
                '">' +
            '</afterpay-price-table>');
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
            if (checkoutConfig.payment.clearpay.isCBTCurrency === true) {
                amount = totals.totals().grand_total / 4;
            }
            return priceUtils.formatPrice(Number(amount).toFixed(2), quote.getPriceFormat());
        },
    });
});
