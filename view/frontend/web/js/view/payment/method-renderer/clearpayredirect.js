/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2021 Clearpay https://www.clearpay.com
 */
/*browser:true*/
/*global define*/
define(['jquery'],
 function($) {
    'use strict';
    return  {
        redirectToClearpayeu: function (data) {
            Clearpay.redirect({
				token: data.token
			});
        }
    }

});