<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2018 Clearpay https://www.clearpay.co.uk
 */
namespace Clearpay\Clearpay\Model\Source;

/**
 * Class ApiMode
 * @package Clearpay\Clearpay\Model\Source
 */
class ApiMode implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * protected object manager
     */
    protected $objectManager;

    /**
     * ApiMode constructor.
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $result = [];
        // get api mode model to get from XML
        $apiMode = $this->objectManager->create('Clearpay\Clearpay\Model\Adapter\ApiMode');

        // looping all data from api modes
        foreach ($apiMode->getAllApiModes() as $name => $environment) {
            array_push(
                $result,
                [
                    'value' => $name,
                    'label' => $environment['label'],
                ]
            );
        }

        // get the result
        return $result;
    }
}
