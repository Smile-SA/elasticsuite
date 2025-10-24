<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\Category\Indexer\Fulltext\Datasource;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Smile\ElasticsuiteCore\Api\Index\DatasourceInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\DynamicFieldProviderInterface;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Category\Indexer\Fulltext\Datasource\UrlRewriteData as ResourceModel;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticsuiteCore\Index\Mapping\FieldFactory;

/**
 * Categories url rewrite datasource model.
 * Adds the possibly manually edited rewrite URL (full path and suffix) into the 'url' field.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 */
class UrlRewriteData implements DatasourceInterface, DynamicFieldProviderInterface
{
    /**
     * XML path for URL rewrites support for categories
     */
    const XML_PATH_URL_REWRITE_SUPPORT_ENABLED = 'smile_elasticsuite_catalogsearch_settings/explicit_url_rewrite_support/categories';

    /**
     * @var ResourceModel
     */
    private $resourceModel;

    /**
     * @var FieldFactory
     */
    private $fieldFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var array
     */
    private $isDatasourceEnabled = [];

    /**
     * Constructor.
     *
     * @param ResourceModel        $resourceModel Datasource resource model.
     * @param FieldFactory         $fieldFactory  Mapping field factory.
     * @param ScopeConfigInterface $scopeConfig   Scope config.
     */
    public function __construct(
        ResourceModel $resourceModel,
        FieldFactory $fieldFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->resourceModel = $resourceModel;
        $this->fieldFactory = $fieldFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function getFields()
    {
        return [
            'url' => $this->fieldFactory->create(
                ['name' => 'url', 'type' => FieldInterface::FIELD_TYPE_KEYWORD]
            ),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function addData($storeId, array $indexData)
    {
        if ($this->isDatasourceEnabled($storeId)) {
            $urlRewrites = $this->resourceModel->loadUrlRewrites($storeId, array_keys($indexData));
            foreach ($urlRewrites as $urlRewrite) {
                $categoryId = $urlRewrite['entity_id'];
                if (array_key_exists($categoryId, $indexData)) {
                    $indexData[$categoryId]['url'] = $urlRewrite['request_path'];
                }
            }
        }

        return $indexData;
    }

    /**
     * Returns true if this datasource is enabled for this store, ie if URL rewrites are supposed to be indexed.
     *
     * @param int $storeId Store ID.
     *
     * @return bool
     */
    private function isDatasourceEnabled($storeId)
    {
        if (false === array_key_exists($storeId, $this->isDatasourceEnabled)) {
            $this->isDatasourceEnabled[$storeId] = (bool) $this->scopeConfig->getValue(
                self::XML_PATH_URL_REWRITE_SUPPORT_ENABLED,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }

        return $this->isDatasourceEnabled[$storeId];
    }
}
