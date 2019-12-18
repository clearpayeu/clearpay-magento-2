/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2019 Clearpay https://www.clearpay.co.uk
 */
var config = {
    map: {
        '*': {
            clearpay:    'https://portal.sandbox.clearpay.co.uk/afterpay.js', // @todo change to use dynamic js window.checkoutConfig.payment.clearpay.clearpayJs
            transparent: 'Magento_Payment/transparent'
        }
    }
};
