<?php
declare(strict_types=1);

namespace Clearpay\Clearpay\Model\ResourceModel\Token;

use Clearpay\Clearpay\Model\ResourceModel\Token as ResourceModel;
use Clearpay\Clearpay\Model\Token as Model;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_eventPrefix = 'clearpay_tokens_log_collection';

    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
