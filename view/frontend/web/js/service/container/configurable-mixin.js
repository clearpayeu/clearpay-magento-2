define([
    'jquery',
    "Clearpay_Clearpay/js/model/container/container-model-holder"
], function ($, containerModelHolder) {
    'use strict';
    const containerModel = containerModelHolder.getModel("clearpay-pdp-container");
    const configurableMixin = {
        _configureElement: function (element) {
            const res = this._super(element);
            if (this.simpleProduct) {
                containerModel.setCurrentProductId(this.simpleProduct);
            }
            return res;
        }
    };
    return function (targetWidget) {
        $.widget('mage.configurable', targetWidget, configurableMixin);

        return $.mage.configurable;
    };
});
