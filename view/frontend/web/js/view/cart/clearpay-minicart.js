/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2020 Clearpay https://www.clearpay.co.uk
 */
define(
    [
        "jquery",
        "Magento_Catalog/js/price-utils",
        'mage/url',
        'Magento_Customer/js/customer-data'
    ],
    function ( $, priceUtils,mageUrl,customerData) {
        return function (config){
            function canDispayExpress(){
                var minicart_subtotal = customerData.get('cart')().subtotalAmount;

                if (!isAfterpayRestricted() && parseFloat(minicart_subtotal) >= parseFloat(config.clearpayConfig.minLimit) && parseFloat(minicart_subtotal) <= parseFloat(config.clearpayConfig.maxLimit) && (config.clearpayConfig.paymentActive)) {
                    $('#clearpay-minicart-express-button').show();
                } else {
                    $('#clearpay-minicart-express-button').hide();
                }
            }


            function isAfterpayRestricted(){
                var cartItems = customerData.get('cart')().items;
                var clearpayRestricted = false;
                if(cartItems && cartItems.length > 0){
                    $.each(cartItems,function(key,val){
                        if(val.clearpay_restricted){
                            clearpayRestricted = true;
                            return false;
                        }
                    });
                }
                return clearpayRestricted;
            }
            $(document).ready(function() {
                $('[data-block=\'minicart\']').on('contentUpdated', function () {
                    canDispayExpress();
                });
            });
        }
    });
