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
        var show_clearpay = clearpay_instalment_element.attr('showClearpay'); 
        
        setTimeout(function (e) {
             setInstalment(clearpay_instalment_element, max_limit, min_limit);
        }, 1000);

        $('body').on('click', $('form#product_addtocart_form'), function (e) {
            setInstalment(clearpay_instalment_element, max_limit, min_limit);
        });

        function setInstalment(clearpay_instalment_element, max_limit, min_limit)
        {
            var price_raw = $('span.price-final_price > span.price-wrapper > span.price:first');
            var price = price_raw.text().match(/[\d\.]+/g);
            
            if (price[1]) {
                product_variant_price = price[0]+price[1];
            } else {
                product_variant_price = price[0];
            }

            var instalment_price = parseFloat(Math.round(product_variant_price / 4 * 100) / 100);
            var formatted_instalment_price = priceUtils.formatPrice(instalment_price);

            $('.clearpay-installments.clearpay-installments-amount .clearpay_instalment_price').text(formatted_instalment_price);

            if (parseFloat(product_variant_price) >= parseFloat(min_limit) && parseFloat(product_variant_price) <= parseFloat(max_limit) && show_clearpay ) {
                clearpay_instalment_element.show();
            } else {
                clearpay_instalment_element.hide();
            }

        }
    }
); 