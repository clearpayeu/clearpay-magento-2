<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Observer\Adminhtml;

class ConfigSaveAfter implements \Magento\Framework\Event\ObserverInterface
{
    private \Magento\Payment\Gateway\CommandInterface $merchantConfigurationCommand;
    private \Magento\Framework\Message\ManagerInterface $messageManager;

    const CLEARPAY_CONFIGS = [
        \Clearpay\Clearpay\Model\Config::XML_PATH_API_MODE,
        \Clearpay\Clearpay\Model\Config::XML_PATH_MERCHANT_KEY,
        \Clearpay\Clearpay\Model\Config::XML_PATH_MERCHANT_ID
    ];
    const CONFIGS_PATHS_TO_TRACK = [
        \Magento\Directory\Model\Currency::XML_PATH_CURRENCY_BASE,
        \Magento\Directory\Helper\Data::XML_PATH_DEFAULT_COUNTRY,
        \Clearpay\Clearpay\Model\Config::XML_PATH_PAYPAL_MERCHANT_COUNTRY
    ];

    public function __construct(
        \Magento\Payment\Gateway\CommandInterface $merchantConfigurationCommand,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->merchantConfigurationCommand = $merchantConfigurationCommand;
        $this->messageManager = $messageManager;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var array $changedPaths */
        $changedPaths = $observer->getData('changed_paths');
        $isClearpayConfigChanged = count(array_intersect($changedPaths, self::CLEARPAY_CONFIGS)) > 0;
        if ($isClearpayConfigChanged || count(array_intersect($changedPaths, self::CONFIGS_PATHS_TO_TRACK)) > 0) {
            $websiteId = $observer->getData('website');
            $store = $observer->getData('store');
            if ($websiteId === '' && $store === '') {
                $websiteId = 0;
            }
            $messageAction = fn () => null;
            if ($websiteId !== '') {
                try {
                    $this->merchantConfigurationCommand->execute([
                        'websiteId' => (int)$observer->getData('website')
                    ]);
                } catch (\Magento\Payment\Gateway\Command\CommandException $e) {
                    $messageAction = fn () => $this->messageManager->addWarningMessage($e->getMessage());
                } catch (\Exception $e) {
                    $messageAction = fn () => $this->messageManager->addErrorMessage(
                        (string)__('Clearpay merchant configuration fetching is failed. See logs.')
                    );
                }
            }
            if ($isClearpayConfigChanged) {
                $messageAction();
            }
        }
    }
}
