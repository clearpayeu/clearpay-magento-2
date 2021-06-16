<?php

namespace Clearpay\Clearpay\Plugin\Checkout\CustomerData;

class Cart
{
    protected $_configPayovertime;

    public function __construct(
        \Clearpay\Clearpay\Model\Config\Payovertime $configPayovertime
    ){
        $this->_configPayovertime = $configPayovertime;
    }

    public function aroundGetItemData(
        \Magento\Checkout\CustomerData\AbstractItem $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote\Item $item
    ) {
        $data = $proceed($item);
        $result['is_virtual'] = $item->getProduct()->getIsVirtual();
        $result['clearpay_restricted'] = false;
        $excluded_categories = $this->_configPayovertime->getExcludedCategories();

        if($excluded_categories !="") {
            $excluded_categories_array =  explode(",",$excluded_categories);
            foreach($item->getProduct()->getCategoryIds() as $k)
            {
                if(in_array($k,$excluded_categories_array)){
                    $result['clearpay_restricted'] = true;
                }
            }
        }
        return \array_merge(
            $result,
            $data
        );
    }
}
