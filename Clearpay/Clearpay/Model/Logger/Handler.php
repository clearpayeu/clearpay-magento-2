<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2019 Clearpay https://www.clearpay.co.uk
 */
namespace Clearpay\Clearpay\Model\Logger;

use Monolog\Logger as MonoLogger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    protected $loggerType = MonoLogger::DEBUG;

    protected $fileName = '/var/log/clearpay.log';
}
