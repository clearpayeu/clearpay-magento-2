/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2020 Clearpay https://www.clearpay.co.uk
 */
require(
    [
        "jquery",
        "Magento_Checkout/js/model/quote"
    ],
    function ( $, quote) {

        $(".cart-totals").bind("DOMSubtreeModified", function() {
            var totals = quote.getTotals()();
            const epsilon = Number.EPSILON ||  Math.pow(2, -52);
            $('afterpay-placement').attr('data-amount',(Math.round((parseFloat(totals['base_grand_total']) + epsilon) * 100) / 100).toFixed(2));

        });

    });
