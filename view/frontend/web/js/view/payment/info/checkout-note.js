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
    totals
) {
    'use strict';

    return Component.extend({
        defaults: {
            isRenderedByWidget: window.checkoutConfig.payment.clearpay.locale === 'en_GB'
        },
        initWidget: function () {
            window.afterpayWidget = new AfterPay.Widgets.PaymentSchedule({
                target: '#clearpay-widget-container',
                locale: window.checkoutConfig.payment.clearpay.locale.replace('_', '-'),
                amount: this._getWidgetAmount(totals.totals()),
                onError: (event) => console.log(event.data.error)
            })
            totals.totals.subscribe((totals) => {
                if (afterpayWidget) {
                    afterpayWidget.update({
                        amount: this._getWidgetAmount(totals),
                    })
                }
            });
        },
        _getWidgetAmount: function (totals) {
            return {
                amount: parseFloat(totals.base_grand_total).toFixed(2),
                currency: totals.base_currency_code
            }
        }
    });
});
