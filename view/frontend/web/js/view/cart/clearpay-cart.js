/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2020 Clearpay https://www.clearpay.co.uk
 */
require(
	[
		"jquery",
		"Magento_Catalog/js/price-utils",
		"Magento_Checkout/js/model/quote"
	],
	function ( $, priceUtils, quote ) {
		
		$(".cart-totals").bind("DOMSubtreeModified", function() {
			var totals = quote.getTotals()();
			var instalment_price = parseFloat(Math.round(totals['base_grand_total'] / 4 * 100) / 100);
			var format = {decimalSymbol: '.',pattern:'$%s'};
			var formatted_instalment_price = priceUtils.formatPrice(instalment_price,format);
			$('.payment-method-note.clearpay-checkout-note .clearpay_instalment_price').text(formatted_instalment_price);
		});
	}
);