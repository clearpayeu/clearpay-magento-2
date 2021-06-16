<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2021 Clearpay https://www.clearpay.co.uk
 */
namespace Clearpay\Clearpay\Block\Catalog;

use Clearpay\Clearpay\Block\JsConfig;
use Clearpay\Clearpay\Model\Config\Payovertime as ClearpayConfig;
use Clearpay\Clearpay\Model\Payovertime as ClearpayPayovertime;
use Magento\Framework\Locale\Resolver as Resolver;
use Magento\Framework\Serialize\Serializer\Json as JsonHelper;
use Magento\Framework\Registry as Registry;
use Magento\Framework\View\Element\Template\Context;
use Magento\Checkout\Model\Session as CheckoutSession;

class Installments extends JsConfig
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var ClearpayConfig
     */
    private $clearpayConfig;
    /**
     * @var ClearpayPayovertime
     */
    private $clearpayPayovertime;

    /**
     * @var Resolver
     */
    private $localeResolver;
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;
    /**
     * @var JsonHelper
     */
    protected $_jsonHelper;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ClearpayConfig $clearpayConfig
     * @param ClearpayPayovertime $clearpayPayovertime
     * @param JsonHelper $jsonHelper
     * @param array $data
     * @param Resolver $localeResolver
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ClearpayConfig $clearpayConfig,
        ClearpayPayovertime $clearpayPayovertime,
        CheckoutSession $checkoutSession,
        JsonHelper $jsonHelper,
        array $data,
        Resolver $localeResolver
    ) {
        $this->registry = $registry;
        $this->clearpayConfig = $clearpayConfig;
        $this->clearpayPayovertime = $clearpayPayovertime;
        $this->localeResolver = $localeResolver;

        $this->checkoutSession = $checkoutSession;
        parent::__construct($clearpayConfig, $clearpayPayovertime,$context, $localeResolver,$jsonHelper, $data);
    }

    /**
     * @return bool
     */
    public function canShow(): bool
    {
        // check if payment is active
        $product = $this->registry->registry('product');

        if ($this->_getPaymentIsActive() &&
            $this->clearpayConfig->getCurrencyCode() &&
            $this->clearpayPayovertime->canUseForCurrency($this->clearpayConfig->getCurrencyCode() &&
                $product->isSalable())
        ) {
            $excluded_categories = $this->clearpayConfig->getExcludedCategories();
            if ($excluded_categories != "") {
                $excluded_categories_array = explode(",", $excluded_categories);
                $categoryids = $product->getCategoryIds();
                foreach ($categoryids as $k) {
                    if (in_array($k, $excluded_categories_array)) {
                        return false;
                    }
                }
            }

            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getTypeOfProduct()
    {
        $product = $this->registry->registry('product');
        return $product->getTypeId();
    }

    /**
     * @return string
     */
    public function getFinalAmount()
    {
        // get product
        $product = $this->registry->registry('product');

        // set if final price is exist
        $price = $product->getPriceInfo()->getPrice('final_price')->getValue();

        return !empty($price) ? number_format($price, 2, ".", "") : "0.00";
    }

    /**
     * @return boolean
     */
    public function canUseCurrency()
    {
        $canUse=false;
        //Check for Supported currency

        if($this->clearpayConfig->getCurrencyCode())
        {
            $canUse= $this->clearpayPayovertime->canUseForCurrency($this->clearpayConfig->getCurrencyCode());
        }

        return $canUse;
    }
    /**
     * @return boolean
     */
    public function isProductVirtual()
    {
        $isVirtual=false;

        $product = $this->registry->registry('product');

        if ($product->getIsVirtual()) {
            $isVirtual=true;
            if ($this->checkoutSession->hasQuote()) {
                $isVirtual=$this->checkoutSession->getQuote()->isVirtual();
            }
        }

        return $isVirtual;
    }
}
