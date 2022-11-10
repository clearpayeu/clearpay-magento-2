<?php

declare(strict_types=1);

namespace Clearpay\Clearpay\Model;

use Magento\Store\Model\ScopeInterface;

class Config
{
    const XML_PATH_PAYMENT_ACTIVE = 'payment/clearpay/active';
    const XML_PATH_API_MODE = 'payment/clearpay/api_mode';
    const XML_PATH_DEBUG = 'payment/clearpay/debug';
    const XML_PATH_ENABLE_CTA_PRODUCT = 'payment/clearpay/enable_cta_product_page';
    const XML_PATH_ENABLE_CTA_MINI_CART = 'payment/clearpay/enable_cta_mini_cart';
    const XML_PATH_ENABLE_CTA_CART_PAGE = 'payment/clearpay/enable_cta_cart_page';
    const XML_PATH_ENABLE_EXPRESS_CHECKOUT_ACTION_PRODUCT = 'payment/clearpay/enable_express_checkout_product_page';
    const XML_PATH_ENABLE_EXPRESS_CHECKOUT_ACTION_MINI_CART = 'payment/clearpay/enable_express_checkout_mini_cart';
    const XML_PATH_ENABLE_EXPRESS_CHECKOUT_ACTION_CART_PAGE = 'payment/clearpay/enable_express_checkout_cart_page';
    const XML_PATH_MERCHANT_ID = 'payment/clearpay/merchant_id';
    const XML_PATH_MERCHANT_KEY = 'payment/clearpay/merchant_key';
    const XML_PATH_PAYMENT_FLOW = 'payment/clearpay/payment_flow';
    const XML_PATH_MIN_LIMIT = 'payment/clearpay/min_order_total';
    const XML_PATH_MAX_LIMIT  = 'payment/clearpay/max_order_total';
    const XML_PATH_CBT_CURRENCY_LIMITS  = 'payment/clearpay/cbt_currency_limits';
    const XML_PATH_EXCLUDE_CATEGORIES  = 'payment/clearpay/exclude_categories';
    const XML_PATH_ALLOW_SPECIFIC_COUNTRIES  = 'payment/clearpay/allowspecific';
    const XML_PATH_SPECIFIC_COUNTRIES  = 'payment/clearpay/specificcountry';
    const XML_PATH_ALLOWED_MERCHANT_COUNTRIES  = 'payment/clearpay/allowed_merchant_countries';
    const XML_PATH_ALLOWED_MERCHANT_CURRENCIES  = 'payment/clearpay/allowed_merchant_currencies';
    const XML_PATH_PAYPAL_MERCHANT_COUNTRY  = 'paypal/general/merchant_country';

    private \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig;
    private \Magento\Framework\App\Config\Storage\WriterInterface $writer;
    private \Magento\Framework\App\ResourceConnection $resourceConnection;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Config\Storage\WriterInterface $writer,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->writer = $writer;
        $this->resourceConnection = $resourceConnection;
    }

    public function getIsPaymentActive(?int $scopeCode = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_PAYMENT_ACTIVE,
            ScopeInterface::SCOPE_WEBSITE,
            $scopeCode
        );
    }

    public function getApiMode(?int $scopeCode = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_API_MODE,
            ScopeInterface::SCOPE_WEBSITE,
            $scopeCode
        );
    }

    public function getIsDebug(?int $scopeCode = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_DEBUG,
            ScopeInterface::SCOPE_WEBSITE,
            $scopeCode
        );
    }

    public function getIsEnableCtaProductPage(?int $scopeCode = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLE_CTA_PRODUCT,
            ScopeInterface::SCOPE_WEBSITE,
            $scopeCode
        );
    }

    public function getIsEnableCtaMiniCart(?int $scopeCode = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLE_CTA_MINI_CART,
            ScopeInterface::SCOPE_WEBSITE,
            $scopeCode
        );
    }

    public function getIsEnableCtaCartPage(?int $scopeCode = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLE_CTA_CART_PAGE,
            ScopeInterface::SCOPE_WEBSITE,
            $scopeCode
        );
    }

    public function getIsEnableExpressCheckoutProductPage(?int $scopeCode = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLE_EXPRESS_CHECKOUT_ACTION_PRODUCT,
            ScopeInterface::SCOPE_WEBSITE,
            $scopeCode
        );
    }

    public function getIsEnableExpressCheckoutMiniCart(?int $scopeCode = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLE_EXPRESS_CHECKOUT_ACTION_MINI_CART,
            ScopeInterface::SCOPE_WEBSITE,
            $scopeCode
        );
    }

    public function getIsEnableExpressCheckoutCartPage(?int $scopeCode = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLE_EXPRESS_CHECKOUT_ACTION_CART_PAGE,
            ScopeInterface::SCOPE_WEBSITE,
            $scopeCode
        );
    }

    public function getMerchantId(?int $scopeCode = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_MERCHANT_ID,
            ScopeInterface::SCOPE_WEBSITE,
            $scopeCode
        );
    }

    public function getMerchantKey(?int $scopeCode = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_MERCHANT_KEY,
            ScopeInterface::SCOPE_WEBSITE,
            $scopeCode
        );
    }

    public function getPaymentFlow(?int $scopeCode = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_PAYMENT_FLOW,
            ScopeInterface::SCOPE_WEBSITE,
            $scopeCode
        );
    }

    public function getMaxOrderTotal(?int $scopeCode = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_MAX_LIMIT,
            ScopeInterface::SCOPE_WEBSITE,
            $scopeCode
        );
    }

    public function getMinOrderTotal(?int $scopeCode = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_MIN_LIMIT,
            ScopeInterface::SCOPE_WEBSITE,
            $scopeCode
        );
    }

    public function getCbtCurrencyLimits(?int $scopeCode = null): array
    {
        $data = [];
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_CBT_CURRENCY_LIMITS,
            ScopeInterface::SCOPE_WEBSITE,
            $scopeCode
        );

        if (!$value) {
            return [];
        }

        $list = explode(',', $value);
        foreach ($list as $item) {
            $currencyLimit = explode(':', $item);
            if (isset($currencyLimit[0]) && isset($currencyLimit[1])) {
                $data[$currencyLimit[0]] = (float) $currencyLimit[1];
            }
        }

        return $data;
    }

    public function getExcludeCategories(?int $scopeCode = null): array
    {
        $excludeCategories = $this->scopeConfig->getValue(
            self::XML_PATH_EXCLUDE_CATEGORIES,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );

        return $excludeCategories ? explode(',', $excludeCategories) : [];
    }

    public function setMaxOrderTotal(string $value, int $scopeId = 0): self
    {
        if ($scopeId) {
            $this->writer->save(
                self::XML_PATH_MAX_LIMIT,
                $value,
                ScopeInterface::SCOPE_WEBSITES,
                $scopeId
            );
            return $this;
        }
        $this->writer->save(
            self::XML_PATH_MAX_LIMIT,
            $value
        );
        return $this;
    }

    public function setMinOrderTotal(string $value, int $scopeId = 0): self
    {
        if ($scopeId) {
            $this->writer->save(
                self::XML_PATH_MIN_LIMIT,
                $value,
                ScopeInterface::SCOPE_WEBSITES,
                $scopeId
            );
            return $this;
        }
        $this->writer->save(
            self::XML_PATH_MIN_LIMIT,
            $value
        );
        return $this;
    }

    public function deleteMaxOrderTotal(int $scopeId = 0, bool $websiteHasOwnConfig = false): self
    {
        return $this->eraseConfigByPath($scopeId, self::XML_PATH_MAX_LIMIT, $websiteHasOwnConfig);
    }

    public function deleteMinOrderTotal(int $scopeId = 0, bool $websiteHasOwnConfig = false): self
    {
        return $this->eraseConfigByPath($scopeId, self::XML_PATH_MIN_LIMIT, $websiteHasOwnConfig);
    }

    public function deleteCbtCurrencyLimits(int $scopeId = 0, bool $websiteHasOwnConfig = false): self
    {
        return $this->eraseConfigByPath($scopeId, self::XML_PATH_CBT_CURRENCY_LIMITS, $websiteHasOwnConfig);
    }

    public function setCbtCurrencyLimits(string $value, int $scopeId = 0): self
    {
        if ($scopeId) {
            $this->writer->save(
                self::XML_PATH_CBT_CURRENCY_LIMITS,
                $value,
                ScopeInterface::SCOPE_WEBSITES,
                $scopeId
            );
            return $this;
        }
        $this->writer->save(
            self::XML_PATH_CBT_CURRENCY_LIMITS,
            $value
        );
        return $this;
    }

    /**
     * @return string[]
     */
    public function getAllowedCountries(?int $scopeCode = null): array
    {
        $specificCountries = $this->scopeConfig->getValue(
            self::XML_PATH_ALLOWED_MERCHANT_COUNTRIES,
            ScopeInterface::SCOPE_WEBSITE,
            $scopeCode
        );
        if ($specificCountries != null) {
            return explode(",", $specificCountries);
        }
        return [];
    }

    /**
     * @return string[]
     */
    public function getAllowedCurrencies(?int $scopeCode = null): array
    {
        $specificCountries = $this->scopeConfig->getValue(
            self::XML_PATH_ALLOWED_MERCHANT_CURRENCIES,
            ScopeInterface::SCOPE_WEBSITE,
            $scopeCode
        );
        if ($specificCountries != null) {
            return explode(",", $specificCountries);
        }
        return [];
    }

    /**
     * @return string[]
     */
    public function getSpecificCountries(?int $scopeCode = null): array
    {
        $specificCountries = $this->scopeConfig->getValue(
            self::XML_PATH_SPECIFIC_COUNTRIES,
            ScopeInterface::SCOPE_WEBSITE,
            $scopeCode
        );
        if ($specificCountries != null) {
            return explode(",", $specificCountries);
        }
        return [];
    }

    public function deleteSpecificCountries(int $scopeId = 0, bool $websiteHasOwnConfig = false): self
    {
        return $this->eraseConfigByPath($scopeId, self::XML_PATH_SPECIFIC_COUNTRIES, $websiteHasOwnConfig);
    }

    public function setSpecificCountries(string $value, int $scopeId = 0): self
    {
        if ($scopeId) {
            $this->writer->save(
                self::XML_PATH_SPECIFIC_COUNTRIES,
                $value,
                ScopeInterface::SCOPE_WEBSITES,
                $scopeId
            );
            return $this;
        }
        $this->writer->save(
            self::XML_PATH_SPECIFIC_COUNTRIES,
            $value
        );
        return $this;
    }

    public function getMerchantCountry(
        string $scope = ScopeInterface::SCOPE_WEBSITES,
        ?int $scopeCode = null
    ): ?string {
        if ($countryCode = $this->scopeConfig->getValue(
            self::XML_PATH_PAYPAL_MERCHANT_COUNTRY,
            $scope,
            $scopeCode
        )) {
            return $countryCode;
        }
        if ($countryCode = $this->scopeConfig->getValue(
            \Magento\Directory\Helper\Data::XML_PATH_DEFAULT_COUNTRY,
            $scope,
            $scopeCode
        )) {
            return $countryCode;
        }
        return null;
    }

    public function getMerchantCurrency(
        string $scope = ScopeInterface::SCOPE_WEBSITES,
        ?int $scopeCode = null
    ): ?string {
        return $this->scopeConfig->getValue(
            \Magento\Directory\Model\Currency::XML_PATH_CURRENCY_BASE,
            $scope,
            $scopeCode
        );
    }

    public function getByConfigPath(?string $path, ?int $scopeCode = null): ?string
    {
        if (!$path) {
            return null;
        }

        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_WEBSITES,
            $scopeCode
        );
    }

    public function websiteHasOwnConfig(int $websiteId): bool
    {
        if ($websiteId == 0) {
            return true;
        }
        $connection = $this->resourceConnection->getConnection();
        $coreConfigData = $connection->getTableName('core_config_data');
        $configsExistToCheck = array_merge(
            \Clearpay\Clearpay\Observer\Adminhtml\ConfigSaveAfter::CLEARPAY_CONFIGS,
            \Clearpay\Clearpay\Observer\Adminhtml\ConfigSaveAfter::CONFIGS_PATHS_TO_TRACK
        );
        $selectQuery = $connection->select()->from($coreConfigData, ['path','value'])
            ->where("scope = ?", \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES)
            ->where("scope_id = ?", $websiteId)
            ->where("path in (?)", $configsExistToCheck);
        $ownConfig = $connection->fetchAll($selectQuery);
        return count($ownConfig) != 0;
    }

    private function eraseConfigByPath(int $scopeId, string $path, bool $websiteHasOwnConfig): self
    {
        if ($scopeId === 0) {
            $this->writer->delete($path);
            return $this;
        }
        if (!$websiteHasOwnConfig) {
            $this->writer->delete(
                $path,
                ScopeInterface::SCOPE_WEBSITES,
                $scopeId
            );
            return $this;
        }

        $this->writer->save(
            $path,
            "",
            ScopeInterface::SCOPE_WEBSITES,
            $scopeId
        );
        return $this;
    }
}
