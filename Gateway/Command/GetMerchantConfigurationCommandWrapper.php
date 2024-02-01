<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Gateway\Command;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class GetMerchantConfigurationCommandWrapper implements \Magento\Payment\Gateway\CommandInterface
{
    public const DEFAULT_WEBSITE_ID = 0;

    private $merchantConfigurationCommand;
    private $clearpayConfig;
    private $appConfig;
    private $debugLogger;
    private $logger;

    public function __construct(
        \Magento\Payment\Gateway\CommandInterface $merchantConfigurationCommand,
        \Clearpay\Clearpay\Model\Config $clearpayConfig,
        \Magento\Framework\App\Config\ReinitableConfigInterface $appConfig,
        \Clearpay\Clearpay\Model\Log\Method\Logger $debugLogger,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->merchantConfigurationCommand = $merchantConfigurationCommand;
        $this->clearpayConfig = $clearpayConfig;
        $this->appConfig = $appConfig;
        $this->debugLogger = $debugLogger;
        $this->logger = $logger;
    }

    /**
     * @return \Magento\Payment\Gateway\Command\ResultInterface|null
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function execute(array $commandSubject)
    {
        $websiteId = (int)$commandSubject['websiteId'];
        $scope = ScopeInterface::SCOPE_WEBSITE;
        if ($websiteId === self::DEFAULT_WEBSITE_ID) {
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        }
        $websiteHasOwnConfig = $this->clearpayConfig->websiteHasOwnConfig($websiteId);
        try {
            if (!$websiteHasOwnConfig) {
                $this->eraseMerchantConfiguration($websiteId, $websiteHasOwnConfig);
                return null;
            }
            $this->checkCountry($scope, $websiteId);
            $this->checkCurrency($scope, $websiteId);
            $this->debugLogger->setForceDebug($this->clearpayConfig->getIsDebug($websiteId));
            return $this->merchantConfigurationCommand->execute($commandSubject);
        } catch (\Magento\Payment\Gateway\Command\CommandException $e) {
            $this->eraseMerchantConfiguration($websiteId, $websiteHasOwnConfig);
            $this->logger->notice($e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->eraseMerchantConfiguration($websiteId, $websiteHasOwnConfig);
            throw $e;
        } finally {
            $this->appConfig->reinit();
        }
    }

    private function eraseMerchantConfiguration(int $websiteId, bool $websiteHasOwnConfig): void
    {
        $this->clearpayConfig
            ->deleteMaxOrderTotal($websiteId, $websiteHasOwnConfig)
            ->deleteMinOrderTotal($websiteId, $websiteHasOwnConfig)
            ->deleteCbtCurrencyLimits($websiteId, $websiteHasOwnConfig);
        $this->clearpayConfig->deleteSpecificCountries($websiteId, $websiteHasOwnConfig);
    }

    /**
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    private function checkCountry(string $scope, int $websiteId): void
    {

        $merchantCountry = $this->clearpayConfig->getMerchantCountry(
            $scope,
            $websiteId
        );
        $allowedCountries = $this->clearpayConfig->getAllowedCountries($websiteId);
        if (!in_array($merchantCountry, $allowedCountries)) {
            throw new \Magento\Payment\Gateway\Command\CommandException(
            // @codingStandardsIgnoreLine
                __('Unable to fetch Clearpay merchant configuration due to unsupported merchant country. Supported countries: %1.', implode(', ', $allowedCountries))
            );
        }
    }

    /**
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    private function checkCurrency(string $scope, int $websiteId): void
    {
        $merchantCurrency = $this->clearpayConfig->getMerchantCurrency(
            $scope,
            $websiteId
        );
        $allowedCurrencies = $this->clearpayConfig->getAllowedCurrencies($websiteId);
        if (!in_array($merchantCurrency, $allowedCurrencies)) {
            throw new \Magento\Payment\Gateway\Command\CommandException(
            // @codingStandardsIgnoreLine
                __('Unable to fetch Clearpay merchant configuration due to unsupported merchant currency. Supported currencies: %1.', implode(', ', $allowedCurrencies))
            );
        }
    }
}
