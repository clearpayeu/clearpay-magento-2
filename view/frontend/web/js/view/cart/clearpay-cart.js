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
		"Magento_Checkout/js/model/quote",
		'mage/url',
		'Magento_Customer/js/customer-data'
	],
	function ( $, priceUtils, quote,mageUrl,customerData) {

	// Clearpay Express Checkout
	 function initClearpayExpress() {

			 var clearpayData = window.checkoutConfig.payment.clearpay;

			 //CountryCode Object to pass in initialize function.
	         var countryCurrencyMapping ={GBP:"GB"};
	         var countryCode = (clearpayData.baseCurrencyCode in countryCurrencyMapping)? countryCurrencyMapping[clearpayData.baseCurrencyCode]:'';
	        
			 var isShippingRequired= (!quote.isVirtual())?true:false;
		if( $("#clearpay-express-button").length && countryCode!=""){
			 AfterPay.initializeForPopup({
		            countryCode: countryCode, // fetch
		            shippingOptionRequired: isShippingRequired, //fetch for virtual type
		            buyNow: true,
		            target: '#clearpay-express-button',
		            onCommenceCheckout: function(actions){
		            	$.ajax({
		                    url: mageUrl.build("clearpay/payment/express")+'?action=start',
		                    success: function(data){
		                        if (!data.success) {
		                            actions.reject(data.message);
		                        } else {
		                            actions.resolve(data.token);
		                        }
		                    }
		                });
		            },
		            onShippingAddressChange: function (shippingData, actions) {
			            	$.ajax({
			                    url: mageUrl.build("clearpay/payment/express")+'?action=change',
			                    method: 'POST',
			                    data: shippingData,
			                    success: function(options){
			                    	if (options.hasOwnProperty('error')) {
			                             actions.reject(AfterPay.constants.SERVICE_UNAVAILABLE, options.message);
			                        } else {
			                            actions.resolve(options.shippingOptions);
			                        }
			                    }
			                });

		            },
		            onComplete: function (orderData) {

		            	if (orderData.data.status == 'SUCCESS') {

			            	$.ajax({
			                    url: mageUrl.build("clearpay/payment/express")+'?action=confirm',
			                    method: 'POST',
			                    data: orderData.data,
			                    beforeSend: function(){
			                    	$("body").trigger('processStart');
			                    },
			                    success: function(result){
			                    	if (result.success) {
			                    		//To Clear mini-cart
			                    		var sections = ['cart'];
			                    		customerData.invalidate(sections);
			                    		customerData.reload(sections, true);

			                    		window.location.href = mageUrl.build("checkout/onepage/success");
			                    	}
			                    },
			                    complete: function(){
			                    	$("body").trigger('processStop');
			                      }
			                });
		            	 }

		            	
		            },
		          pickup: false,
		        });

		 }
	 }


	 	$(document).ready(function() {
		 initClearpayExpress();
		});

		$(".cart-totals").bind("DOMSubtreeModified", function() {
			var totals = quote.getTotals()();
			const epsilon = Number.EPSILON ||  Math.pow(2, -52);
			$('afterpay-placement').attr('data-amount',(Math.round((parseFloat(totals['base_grand_total']) + epsilon) * 100) / 100).toFixed(2));

		});

});
