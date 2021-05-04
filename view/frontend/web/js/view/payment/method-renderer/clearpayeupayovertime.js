/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2021 Clearpay https://www.clearpay.com
 */
/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/resource-url-manager',
        'mage/storage',
        'mage/url',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Ui/js/model/messageList',
        'Magento_Customer/js/customer-data',
        'Magento_Customer/js/section-config',
		'Magento_Checkout/js/action/set-billing-address',
        'Clearpay_ClearpayEurope/js/view/payment/method-renderer/clearpayredirect'
    ],
    function ($, Component, quote, resourceUrlManager, storage, mageUrl, additionalValidators, globalMessageList, customerData, sectionConfig, setBillingAddressAction, clearpayRedirect) {
        'use strict';

        return Component.extend({
            /** Don't redirect to the success page immediately after placing order **/
            redirectAfterPlaceOrder: false,
            defaults: {
                template: 'Clearpay_ClearpayEurope/payment/clearpayeupayovertime',
                billingAgreement: ''
            },

            /**
             * Terms and condition link
             * @returns {*}
             */
            getTermsConditionUrl: function () {
				return window.checkoutConfig.payment.clearpayeu.termsConditionUrl;
            },

            /**
             * Get Grand Total of the current cart
             * @returns {*}
             */
            getGrandTotal: function () {

                var total = quote.getCalculatedTotal();
                var format = window.checkoutConfig.priceFormat.pattern
				var clearpayeu = window.checkoutConfig.payment.clearpayeu;
				
                storage.get(resourceUrlManager.getUrlForCartTotals(quote), false)
                .done(
                    function (response) {

                        var amount = response.base_grand_total;
                        var installmentFee = response.base_grand_total / 4;
                        var installmentFeeLast = amount - installmentFee.toFixed(window.checkoutConfig.priceFormat.precision) * 3;

                        $(".clearpay_instalments_amount").text(format.replace(/%s/g, installmentFee.toFixed(window.checkoutConfig.priceFormat.precision)));
                        $(".clearpay_instalments_amount_last").text(format.replace(/%s/g, installmentFeeLast.toFixed(window.checkoutConfig.priceFormat.precision)));
						$(".clearpay_total_amount").text(format.replace(/%s/g, amount.toFixed(window.checkoutConfig.priceFormat.precision)));
						return format.replace(/%s/g, amount);
                    }
                )
                .fail(
                    function (response) {
                       //do your error handling

                    return 'Error';
                    }
                );
            },

            /**
             * Get Checkout Message based on the currency
             * @returns {*}
             */
            getCheckoutText: function () {

                var clearpayCheckoutText = window.checkoutConfig.payment.clearpayeu.clearpayCheckoutText;
                
                return clearpayCheckoutText;
            },
			getFirstInstalmentText: function () {

                var clearpayFirstInstalmentText = window.checkoutConfig.payment.clearpayeu.clearpayFirstInstalmentText;

                return clearpayFirstInstalmentText;
            },
            getSecondInstalmentText: function () {

                var clearpaySecondInstalmentText = window.checkoutConfig.payment.clearpayeu.clearpaySecondInstalmentText;

                return clearpaySecondInstalmentText;
            },
            getThirdInstalmentText: function () {

                var clearpayThirdInstalmentText = window.checkoutConfig.payment.clearpayeu.clearpayThirdInstalmentText;

                return clearpayThirdInstalmentText;
            },
            getFourthInstalmentText: function () {

                var clearpayFourthInstalmentText = window.checkoutConfig.payment.clearpayeu.clearpayFourthInstalmentText;

                return clearpayFourthInstalmentText;
            },
			getTermsText: function () {

                var clearpayTermsText = window.checkoutConfig.payment.clearpayeu.termsConditionText;

                return clearpayTermsText;
            },
            getButtonText: function () {

                var clearpayButtonText = window.checkoutConfig.payment.clearpayeu.buttonText;

                return clearpayButtonText;
            },

			getTermsLink: function () {

                var clearpayCheckoutTermsLink = window.checkoutConfig.payment.clearpayeu.termsConditionUrl;
                
                return clearpayCheckoutTermsLink;
            },
            getTermsAndConditions: function () {

                var clearpayCheckoutTermsAndConditions = window.checkoutConfig.payment.clearpayeu.termsAndConditions;

                return clearpayCheckoutTermsAndConditions;
            },

            /**
             * Returns the installment fee of the payment */
            getClearpayInstallmentFee: function () {
                // Checking and making sure checkoutConfig data exist and not total 0 dollar
                if (typeof window.checkoutConfig !== 'undefined' &&
                    quote.getCalculatedTotal() > 0) {
                    // Set installment fee from grand total and check format price to be output
                    var installmentFee = quote.getCalculatedTotal() / 4;
                    var format = window.checkoutConfig.priceFormat.pattern;

                    // return with the currency code ($) and decimal setting (default: 2)
                    return format.replace(/%s/g, installmentFee.toFixed(window.checkoutConfig.priceFormat.precision));
                }
            },

            /**
             *  process Clearpay Payment
             */
            continueClearpayeuPayment: function () {
                // Added additional validation to check
                if (additionalValidators.validate()) {
                    // start clearpay payment is here
                    var clearpayeu = window.checkoutConfig.payment.clearpayeu;
                    // Making sure it using API V2
                    var url = mageUrl.build("clearpayeurope/payment/process");
                    var data = $("#co-shipping-form").serialize();
                    var email = window.checkoutConfig.customerData.email;
                    var ajaxRedirected = false;
                    //CountryCode Object to pass in initialize function.

                    var countryCode = {countryCode: "ES"};
					
					//Update billing address of the quote
					setBillingAddressAction(globalMessageList);

                        //handle guest and registering customer emails
                        if (!window.checkoutConfig.quoteData.customer_id) {
                            email = document.getElementById("customer-email").value;
                        }

                        data = data + '&email=' + email;

                        $.ajax({
                            url: url,
                            method: 'post',
                            data: data,
                            beforeSend: function () {
                                $('body').trigger('processStart');
                            }
                        }).done(function (response) {
                            // var data = $.parseJSON(response);
                            var data = response;

                            if (data.success && (typeof data.token !== 'undefined' && data.token !== null && data.token.length) ) {
                                //Init or Initialize Clearpay
                                //Pass countryCode to Initialize function
                                if (typeof Clearpay.initialize === "function") {
                                    Clearpay.initialize({'token':data.token});
                                } else {
                                    Clearpay.init();
                                }

                                //Waiting for all AJAX calls to resolve to avoid error messages upon redirection
                                $("body").ajaxStop(function () {
									ajaxRedirected = true;
                                    clearpayRedirect.redirectToClearpayeu(data);
                                });
								setTimeout(
									function(){
										if(!ajaxRedirected){
											clearpayRedirect.redirectToClearpayeu(data);
										}
									}
								,5000);
                            } else if (typeof data.error !== 'undefined' && typeof data.message !== 'undefined' &&
                                data.error && data.message.length) {
                                globalMessageList.addErrorMessage({
                                    'message': data.message
                                });
                            } else {
                                globalMessageList.addErrorMessage({
                                    'message': data.message
                                });
                            }
                        }).fail(function () {
                            window.location.reload();
                        }).always(function () {
                            customerData.invalidate(['cart']);
                            $('body').trigger('processStop');
                        });
                }
            },

            /**
             * Start popup or redirect payment
             *
             * @param response
             */
            afterPlaceOrder: function () {
                
                // start clearpayeu payment is here
                var clearpayeu = window.checkoutConfig.payment.clearpayeu;

                // Making sure it using current flow

                var url = mageUrl.build("clearpayeurope/payment/process");
				
				//Update billing address of the quote
				setBillingAddressAction(globalMessageList);
                
                $.ajax({
                    url: url,
                    method:'post',
                    success: function (response) {

                        // var data = $.parseJSON(response);
                        var data = response;

                        if (typeof Clearpay.initialize === "function") {
                            Clearpay.initialize({
                                relativeCallbackURL: window.checkoutConfig.payment.clearpayeu.clearpayReturnUrl
                            });
                        } else {
                            Clearpay.init({
                                relativeCallbackURL: window.checkoutConfig.payment.clearpayeu.clearpayReturnUrl
                            });
                        }

                        clearpayRedirect.redirectToClearpay(data);
                    }
                });
            }
        });
    }
);
