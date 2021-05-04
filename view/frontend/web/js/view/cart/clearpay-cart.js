/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2021 Clearpay https://www.clearpay.com
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
			$('afterpay-placement').attr('data-amount',totals['base_grand_total']);
			
		});
	}
);