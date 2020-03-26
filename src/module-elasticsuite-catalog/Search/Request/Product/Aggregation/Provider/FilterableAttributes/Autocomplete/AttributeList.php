<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Search\Request\Product\Aggregation\Provider\FilterableAttributes\Autocomplete;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Smile\ElasticsuiteCatalog\Search\Request\Product\Aggregation\Provider\FilterableAttributes\AttributeListInterface;

/**
 * Attributes List used in Autocomplete queries.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class AttributeList implements AttributeListInterface
{
    /**
     * Cache Key
     */
    const CACHE_KEY = 'ES_ACP_ATTRS';

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    private $cache;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * @var null|array
     */
    private $attributeList = null;

    /**
     * FilterableAttributeList constructor
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $collectionFactory Collection Factory
     * @param CacheInterface                                                           $cache             Cache
     * @param SerializerInterface                                                      $serializer        Serializer
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $collectionFactory,
        CacheInterface $cache,
        SerializerInterface $serializer
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->cache             = $cache;
        $this->serializer        = $serializer;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function getList()
    {
        if (null === $this->attributeList) {
            $cacheKey       = self::CACHE_KEY;
            $attributesList = $this->cache->load($cacheKey);

            if (false === $attributesList) {
                /** @var $collection \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection */
                $collection = $this->collectionFactory->create();
                $collection->setItemObjectClass(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class)
                    ->setOrder('position', 'ASC');

                $collection->addSetInfo(true);
                $collection->addFieldToFilter('additional_table.is_displayed_in_autocomplete', ['eq' => 1]);
                $collection->setOrder('attribute_id', 'ASC');

                $collection->load();

                $this->attributeList = $collection->getItems();
                $cacheData = $this->serializer->serialize($collection->toArray());

                $this->cache->save($cacheData, $cacheKey, $this->getCacheTags());
            } else {
                $attributesData = $this->serializer->unserialize($attributesList);
                $collection     = $this->collectionFactory->create();

                $this->attributeList = [];
                foreach ($attributesData['items'] ?? [] as $attributeData) {
                    $item = $collection->getNewEmptyItem();
                    $item->setData($attributeData);
                    $this->attributeList[] = $item;
                }
            }
        }

        return $this->attributeList;
    }

    /**
     * Get cache tags.
     *
     * @return array
     */
    private function getCacheTags()
    {
        return [
            \Magento\Framework\App\Cache\Type\Config::CACHE_TAG,
            \Magento\Eav\Model\Cache\Type::CACHE_TAG,
        ];
    }
}
