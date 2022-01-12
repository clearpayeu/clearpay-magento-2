var config = {
    map: {
        '*': {
            "clearpayBaseContainer": "Clearpay_Clearpay/js/view/container/container",
            "clearpayCta": "Clearpay_Clearpay/js/view/container/cta/cta",
            "clearpayExpressCheckoutButton": "Clearpay_Clearpay/js/view/container/express-checkout/button",
            "clearpayExpressCheckoutButtonPdp": "Clearpay_Clearpay/js/view/container/express-checkout/product/button"
        }
    },
    config: {
        mixins: {
            "Magento_Catalog/js/price-box": {
                "Clearpay_Clearpay/js/service/container/pricebox-widget-mixin": true
            },
            "Magento_ConfigurableProduct/js/configurable": {
                "Clearpay_Clearpay/js/service/container/configurable-mixin": true
            }
        }
    }
};
