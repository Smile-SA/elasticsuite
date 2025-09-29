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

namespace Smile\ElasticsuiteCore\Ui\Component\DataProvider;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Smile\ElasticsuiteCore\Model\ResourceModel\Index\BulkError\Collection;
use Smile\ElasticsuiteCore\Model\ResourceModel\Index\BulkError\CollectionFactory;

/**
 * Data provider for admin grid of indexing bulk error.
 */
class BulkErrorDataProvider extends AbstractDataProvider
{
    /** @var Collection */
    protected $collection;

    /** @var StoreManagerInterface */
    protected $storeManager;

    /**
     * Constructor.
     *
     * @param string                $name              Data Provider name.
     * @param string                $primaryFieldName  Primary field name.
     * @param string                $requestFieldName  Request field name.
     * @param CollectionFactory     $collectionFactory Bulk error collection factory.
     * @param StoreManagerInterface $storeManager      Store manager.
     * @param array                 $meta              Provider configuration metadata.
     * @param array                 $data              Provider configuration data.
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->storeManager = $storeManager;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        if ($filter->getField() === 'store_id') {
            $storeId = $filter->getValue();
            if ($storeId !== Store::DEFAULT_STORE_ID) {
                $storeCode = null;
                try {
                    $storeCode = $this->storeManager->getStore($storeId)->getCode();
                } catch (NoSuchEntityException $e) {
                    ;
                }
                if ($storeCode !== null) {
                    return parent::addFilter(
                        $filter->setField('store_code')->setValue($storeCode)
                    );
                }
            }

            return $this;
        }

        return parent::addFilter($filter);
    }

    /**
     * Get bulk error item from collection.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->collection->toArray();
    }
}
