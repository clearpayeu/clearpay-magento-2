define([
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Clearpay_Clearpay/js/action/create-clearpay-checkout',
    'Magento_Checkout/js/action/set-payment-information',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Customer/js/customer-data',
    'Magento_Customer/js/section-config'
], function (
    $,
    Component,
    additionalValidators,
    createClearpayCheckoutAction,
    setPaymentInformationAction,
    errorProcessor,
    customerData,
    sectionConfig
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Clearpay_Clearpay/payment/clearpay'
        },

        continueToClearpay: function (data, event) {
            const self = this;

            if (event) {
                event.preventDefault();
            }

            if (additionalValidators.validate() && this.isPlaceOrderActionAllowed() === true) {
                this.isPlaceOrderActionAllowed(false);

                setPaymentInformationAction(
                    self.messageContainer,
                    self.getData()
                ).done(function () {

                    const captureUrlPath = 'clearpay/payment/capture';
                    createClearpayCheckoutAction(self.messageContainer, {
                        confirmPath: captureUrlPath,
                        cancelPath: captureUrlPath
                    }).done(function (response) {
                        const sections = sectionConfig.getAffectedSections(captureUrlPath);
                        customerData.invalidate(sections);
                        $.mage.redirect(response.clearpay_redirect_checkout_url);
                    }).always(function () {
                        self.isPlaceOrderActionAllowed(true);
                    });

                }).fail(function (response) {
                    errorProcessor.process(response, self.messageContainer);
                }).always(function () {
                    self.isPlaceOrderActionAllowed(true);
                });
            }
        }
    });
});
