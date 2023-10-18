<?php
declare(strict_types=1);

namespace Clearpay\Clearpay\Model;

use Clearpay\Clearpay\Api\Data\TokenInterface;
use Clearpay\Clearpay\Model\ResourceModel\Token as ResourceModel;
use Magento\Framework\Model\AbstractModel;

class Token extends AbstractModel implements TokenInterface
{
    protected $_eventPrefix = 'clearpay_tokens_log_model';

    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }
}
