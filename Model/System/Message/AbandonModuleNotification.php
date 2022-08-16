<?php
declare(strict_types=1);

namespace Clearpay\ClearpayEurope\Model\System\Message;

class AbandonModuleNotification implements \Magento\Framework\Notification\MessageInterface
{
    /**
     * {@inheritdoc}
     */
    public function isDisplayed()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getText()
    {
        return __("ClearpayEU has been deprecated in favour of Clearpay, please go <a href='https://github.com/clearpayeu/clearpay-magento-2'>here</a> to download the correct version of the Clearpay module based on your Magento version.");
    }

    /**
     * @inheritdoc
     *
     * @codeCoverageIgnore
     */
    public function getSeverity()
    {
        return self::SEVERITY_CRITICAL;
    }

    public function getIdentity()
    {
        return hash('sha256','CLEARPAYEU-ABANDONED');
    }
}
