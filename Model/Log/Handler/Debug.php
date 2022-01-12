<?php

declare(strict_types=1);

namespace Clearpay\Clearpay\Model\Log\Handler;

class Debug extends \Magento\Framework\Logger\Handler\Base
{
    protected $fileName = "/var/log/clearpay.log";

    protected $loggerType = \Monolog\Logger::DEBUG;
}
