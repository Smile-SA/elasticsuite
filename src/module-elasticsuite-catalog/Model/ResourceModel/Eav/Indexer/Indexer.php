<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCatalog\Model\ResourceModel\Eav\Indexer;

use Magento\Framework\App\ResourceConnection;
use Smile\ElasticSuiteCore\Model\ResourceModel\Indexer\AbstractIndexer;

/**
 * This class provides a lot of util methods used by Eav indexer related resource models.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @author    Fanny DECLERCK <fadec@smile.fr>
 */
class Indexer extends AbstractIndexer
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Retrieve store root category id.
     *
     * @param \Magento\Store\Api\Data\StoreInterface|int|string $store Store id.
     *
     * @return integer
     */
    protected function getRootCategoryId($store)
    {
        if (is_numeric($store) || is_string($store)) {
            $store = $this->getStore($store);
        }

        $storeGroupId = $store->getStoreGroupId();

        return $this->storeManager->getGroup($storeGroupId)->getRootCategoryId();
    }
}
