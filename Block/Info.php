<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2021 Clearpay https://www.Clearpay.com
 */
namespace Clearpay\Clearpay\Block;

/**
 * Class Info
 * @package Clearpay\Clearpay\Block
 */
class Info extends \Magento\Payment\Block\Info
{
    /**
     * @param null $transport
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $transport = parent::_prepareSpecificInformation($transport);
        $data = [];
        $info = $this->getInfo();

        // load the data available on additional informations
        if ($this->getArea() === \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE
            && $info->getAdditionalInformation()
        ) {
            foreach ($info->getAdditionalInformation() as $field => $value) {
                $beautifiedFieldName = str_replace('_', ' ', ucwords(trim(preg_replace('/(?<=\\w)(?=[A-Z])/', " $1", $field))));
                $data[__($beautifiedFieldName)->getText()] = $value;
            }
        }
        return $transport->setData(array_merge($data, $transport->getData()));
    }
}
