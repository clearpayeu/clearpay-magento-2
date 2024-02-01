<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Serialize\SerializerInterface;

class Config
{

    public const XML_PATH_PAYMENT_ACTIVE = 'payment/clearpay/active';
    public const XML_PATH_API_MODE = 'payment/clearpay/api_mode';
    public const XML_PATH_DEBUG = 'payment/clearpay/debug';
    public const XML_PATH_ENABLE_CTA_PRODUCT = 'payment/clearpay/enable_cta_product_page';
    public const XML_PATH_ENABLE_CTA_MINI_CART = 'payment/clearpay/enable_cta_mini_cart';
    public const XML_PATH_ENABLE_CTA_CART_PAGE = 'payment/clearpay/enable_cta_cart_page';
    public const XML_PATH_ENABLE_EXPRESS_CHECKOUT_ACTION_PRODUCT = 'payment/clearpay/enable_express_checkout_product_page';
    public const XML_PATH_ENABLE_EXPRESS_CHECKOUT_ACTION_MINI_CART = 'payment/clearpay/enable_express_checkout_mini_cart';
    public const XML_PATH_ENABLE_EXPRESS_CHECKOUT_ACTION_CART_PAGE = 'payment/clearpay/enable_express_checkout_cart_page';
    public const XML_PATH_ADD_LAST_SELECTED_SHIP_RATE = 'payment/clearpay/add_last_selected_ship_rate';
    public const XML_PATH_MERCHANT_ID = 'payment/clearpay/merchant_id';
    public const XML_PATH_MERCHANT_KEY = 'payment/clearpay/merchant_key';
    public const XML_PATH_PAYMENT_FLOW = 'payment/clearpay/payment_flow';
    public const XML_PATH_MIN_LIMIT = 'payment/clearpay/min_order_total';
    public const XML_PATH_MAX_LIMIT = 'payment/clearpay/max_order_total';
    public const XML_PATH_CBT_CURRENCY_LIMITS = 'payment/clearpay/cbt_currency_limits';
    public const XML_PATH_EXCLUDE_CATEGORIES = 'payment/clearpay/exclude_categories';
    public const XML_PATH_ALLOW_SPECIFIC_COUNTRIES = 'payment/clearpay/allowspecific';
    public const XML_PATH_SPECIFIC_COUNTRIES = 'payment/clearpay/specificcountry';
    public const XML_PATH_ALLOWED_MERCHANT_COUNTRIES = 'payment/clearpay/allowed_merchant_countries';
    public const XML_PATH_ALLOWED_MERCHANT_CURRENCIES = 'payment/clearpay/allowed_merchant_currencies';
    public const XML_PATH_ENABLE_REVERSAL = 'payment/clearpay/enable_reversal';
    public const XML_PATH_MPID = 'payment/clearpay/public_id';


    private ScopeConfigInterface $scopeConfig;
    private WriterInterface $writer;
    private ResourceConnection $resourceConnection;
    private SerializerInterface $serializer;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        WriterInterface      $writer,
        ResourceConnection   $resourceConnection,
        SerializerInterface  $serializer

    ) {
        $this->scopeConfig = $scopeConfig;
        $this->writer = $writer;
        $this->resourceConnection = $resourceConnection;
        $this->serializer = $serializer;
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
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_CBT_CURRENCY_LIMITS,
            ScopeInterface::SCOPE_WEBSITE,
            $scopeCode
        );

        if (!$value) {
            return [];
        }

        return $this->serializer->unserialize($value);
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

    public function getIsReversalEnabled(?int $scopeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE_REVERSAL,
            ScopeInterface::SCOPE_WEBSITE,
            $scopeCode
        );
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
        ?int   $scopeCode = null
    ): ?string {
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
        ?int   $scopeCode = null
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
        $coreConfigData = $this->resourceConnection->getTableName('core_config_data');
        $configsExistToCheck = array_merge(
            \Clearpay\Clearpay\Observer\Adminhtml\ConfigSaveAfter::CLEARPAY_CONFIGS,
            \Clearpay\Clearpay\Observer\Adminhtml\ConfigSaveAfter::CONFIGS_PATHS_TO_TRACK
        );
        $selectQuery = $connection->select()->from($coreConfigData, ['path', 'value'])
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


    public function setPublicId(string $value, int $scopeId = 0): self
    {
        if ($scopeId) {
            $this->writer->save(
                self::XML_PATH_MPID,
                $value,
                ScopeInterface::SCOPE_WEBSITES,
                $scopeId
            );
            return $this;
        }
        $this->writer->save(
            self::XML_PATH_MPID,
            $value
        );
        return $this;
    }

    public function getPublicId(?int $scopeCode = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_MPID,
            ScopeInterface::SCOPE_WEBSITE,
            $scopeCode
        );
    }

    public function getAddLastSelectedShipRate(?int $scopeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ADD_LAST_SELECTED_SHIP_RATE,
            ScopeInterface::SCOPE_WEBSITE,
            $scopeCode
        );
    }
}
