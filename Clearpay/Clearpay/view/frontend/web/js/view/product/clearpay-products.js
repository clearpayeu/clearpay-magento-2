/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
require(
	[
		"jquery",
		"Magento_Catalog/js/price-utils"
	],
	function ( $, priceUtils, quote ) {

		var clearpay_instalment_element = $('.clearpay-installments.clearpay-installments-amount');

		var max_limit = clearpay_instalment_element.attr('maxLimit');
		var min_limit = clearpay_instalment_element.attr('minLimit');
		var product_type = clearpay_instalment_element.attr('product_type');
 
		$(document).ready(function($) {
			setInstalment(clearpay_instalment_element, max_limit, min_limit);

			$('body').on('click change', $('form#product_addtocart_form'), function (e) {
				setInstalment(clearpay_instalment_element, max_limit, min_limit);
			});
			$('body').on('input', $('form#product_addtocart_form select'), function (e) {
				setTimeout(function() {
					$('form#product_addtocart_form').trigger('change');
				}, 3);
			});
		});

		function setInstalment(clearpay_instalment_element, max_limit, min_limit)
		{
			//var price_raw = $('span.price-final_price > span.price-wrapper > span.price:first');
			//Above line only extracts the value from first price element product page. This might cause problem in some cases
			if(product_type=="bundle" && $("[data-price-type=minPrice]:first").text()!=""){
				 var price_raw = $("[data-price-type=minPrice]:first").text();
			}
			else if($("[data-price-type=finalPrice]:first").text()!=""){
				var price_raw = $("[data-price-type=finalPrice]:first").text();
			}
			else{
				var price_raw = $('span.price-final_price > span.price-wrapper > span.price:first').text();
			}
			
			var price = price_raw.match(/[\d\.]+/g);
		
			if(price != null){
				if (price[1]) {
					product_variant_price = price[0]+price[1];
				} else {
					product_variant_price = price[0];
				}
				var instalment_price = parseFloat(Math.round(product_variant_price / 4 * 100) / 100);

				//pass the price format object - fix for the group product format

				var format = {decimalSymbol: '.',pattern:'£%s'};
				var formatted_instalment_price = priceUtils.formatPrice(instalment_price,format);

				$('.clearpay-installments.clearpay-installments-amount .clearpay_instalment_price').text(formatted_instalment_price);

				if (parseFloat(product_variant_price) >= parseFloat(min_limit) && parseFloat(product_variant_price) <= parseFloat(max_limit)) {
					clearpay_instalment_element.show();
				} else {
					clearpay_instalment_element.hide();
				}
			}
			else{
				clearpay_instalment_element.hide();
			}
		}
	}
);