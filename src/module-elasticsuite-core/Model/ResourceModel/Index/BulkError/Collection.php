<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Pierre Gauthier <pigau@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Model\ResourceModel\Index\BulkError;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Smile\ElasticsuiteCore\Model\Index\BulkError as Model;
use Smile\ElasticsuiteCore\Model\ResourceModel\Index\BulkError as ResourceModel;

/**
 * Bulk error collection ressource model.
 */
class Collection extends AbstractCollection
{
    /**
     * Collection resource model constructor.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
