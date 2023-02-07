/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2021 Clearpay https://www.clearpay.com
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
        'Clearpay_Clearpay/js/view/payment/method-renderer/clearpayredirect',
        'mage/translate',
        'Magento_Checkout/js/model/totals',
        'ko'
    ],
    function ($, Component, quote, resourceUrlManager, storage, mageUrl, additionalValidators, globalMessageList, customerData, sectionConfig, setBillingAddressAction, clearpayRedirect,$t,totals,ko) {
        'use strict';

        return Component.extend({
            /** Don't redirect to the success page immediately after placing order **/
            redirectAfterPlaceOrder: false,
            defaults: {
                template: 'Clearpay_Clearpay/payment/clearpaypayovertime',
                billingAgreement: '',
                isRenderCheckoutWidget:window.checkoutConfig.payment.clearpay.storeLocale==="en_GB"
            },
            /**
             * Initialize Checkout Widget
             *
             */
            initWidget: function () {
                var clearpay = window.checkoutConfig.payment.clearpay;
                var storeLocale=clearpay.storeLocale.replace('_', '-');
                window.clearpayWidget = new AfterPay.Widgets.PaymentSchedule({
                    target: '#clearpay-widget-container',
                    locale: storeLocale,
                    amount: this._getOrderAmount(totals.totals()),
                    onError: function (event) {
                        console.log(event.data.error);
                    },
                });
                totals.totals.subscribe((totals) => {
                    if (clearpayWidget) {
                        clearpayWidget.update({
                            amount: this._getOrderAmount(totals),
                        })
                    }
                });
            },
            /**
             * Get Order Amount
             * @returns {*}
             */
            _getOrderAmount: function (totals) {
                return {
                    amount: parseFloat(totals.grand_total).toFixed(2),
                    currency: totals.quote_currency_code
                }
            },
            /**
             * Initialize Checkout Price Table
             *
             */
            getPriceTable: function() {
                return ko.computed(() =>
                    '<afterpay-placement ' +
                    'data-amount="' + parseFloat(totals.totals().base_grand_total).toFixed(2) +
                    '" data-locale="' + window.checkoutConfig.payment.clearpay.storeLocale +
                    '" data-currency="' + totals.totals().base_currency_code +
                    '" data-type="price-table">' +
                    '</afterpay-placement>');
            },
            /**
             * Terms and condition link
             * @returns {*}
             */
            getTermsConditionUrl: function () {
                return window.checkoutConfig.payment.clearpay.termsConditionUrl;
            },

            /**
             * Get Checkout Message based on the currency
             */
            getCheckoutText: function () {
                return ko.computed(() =>
                    '<afterpay-placement ' +
                    'data-amount="' + parseFloat(totals.totals().base_grand_total).toFixed(2) +
                    '" data-locale="' + window.checkoutConfig.payment.clearpay.storeLocale +
                    '" data-currency="' + totals.totals().base_currency_code +
                    '" data-is-eligible="true"' +
                    '" data-intro-text="false"' + 
                    '" data-badge-theme="black-on-mint">' +
                    '</afterpay-placement>');
            },

			getTermsLink: function () {

                return window.checkoutConfig.payment.clearpay.termsConditionUrl;

            },

            /**
             *  process Clearpay Payment
             */
            continueClearpayPayment: function () {
                // Added additional validation to check
                if (additionalValidators.validate()) {
                    // start clearpay payment is here
                    var clearpay = window.checkoutConfig.payment.clearpay;
                    // Making sure it using API V2
                    var url = mageUrl.build("clearpay/payment/process");
                    var data = $("#co-shipping-form").serialize();
                    var email = window.checkoutConfig.customerData.email;
                    var ajaxRedirected = false;
                    //CountryCode Object to pass in initialize function.

                   //CountryCode Object to pass in initialize function.
                   // var countryCurrencyMapping ={GBP:"GB"};
                    var countryCode = {countryCode: clearpay.countryCode};


                    //Update billing address of the quote
                    const setBillingAddressActionResult = setBillingAddressAction(globalMessageList);

                    setBillingAddressActionResult.done(function () {
                        //handle guest and registering customer emails
                        if (!window.checkoutConfig.quoteData.customer_id) {
                            email = document.getElementById("customer-email").value;
                        }

                        data = data + '&email=' + encodeURIComponent(email);


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
                                if (typeof AfterPay !== "undefined") {
                                    AfterPay.initialize(countryCode);
                                } else if (typeof Clearpay !== "undefined") {
                                    Clearpay.initialize(countryCode);
                                } else {
                                    AfterPay.init();
                                }

                                //Waiting for all AJAX calls to resolve to avoid error messages upon redirection
                                $("body").ajaxStop(function () {
									ajaxRedirected = true;
                                    clearpayRedirect.redirectToClearpay(data,clearpay.countryCode);
                                });
								setTimeout(
									function(){
										if(!ajaxRedirected){
											clearpayRedirect.redirectToClearpay(data,clearpay.countryCode);
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
                    }).fail(function () {
						window.scrollTo({top: 0, behavior: 'smooth'});
                    });
                }
            },

            /**
             * Start popup or redirect payment
             *
             * @param response
             */
            afterPlaceOrder: function () {

                // start clearpay payment is here
                var clearpay = window.checkoutConfig.payment.clearpay;

                // Making sure it using current flow

                var url = mageUrl.build("clearpay/payment/process");

				//Update billing address of the quote
				setBillingAddressAction(globalMessageList);

                $.ajax({
                    url: url,
                    method:'post',
                    success: function (response) {

                        // var data = $.parseJSON(response);
                        var data = response;

                        if (typeof AfterPay.initialize === "function") {
                            AfterPay.initialize({
                                relativeCallbackURL: window.checkoutConfig.payment.clearpay.clearpayReturnUrl
                            });
                        } else {
                            AfterPay.init({
                                relativeCallbackURL: window.checkoutConfig.payment.clearpay.clearpayReturnUrl
                            });
                        }

                        clearpayRedirect.redirectToClearpay(data,clearpay.countryCode);
                    }
                });
            }
        });
    }
);
