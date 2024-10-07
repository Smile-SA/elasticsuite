<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteIndices
 * @author    Vadym HONCHARUK <vahonc@smile.fr>
 * @copyright 2022 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteIndices\Block\Adminhtml\Analysis;

use Exception;
use Magento\Backend\Block\Template;
use Smile\ElasticsuiteIndices\Block\Widget\Grid\Column\Renderer\IndexStatus;
use Smile\ElasticsuiteIndices\Model\IndexStatsProvider;
use Smile\ElasticsuiteIndices\Model\ResourceModel\IndexSettings\CollectionFactory;

/**
 * Adminhtml Analysis by Analyzer Block.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteIndices
 * @author   Vadym HONCHARUK <vahonc@smile.fr>
 */
class Analyzer extends Template
{
    /**
     * @var IndexStatsProvider
     */
    protected $indexStatsProvider;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * Analyzer Constructor.
     *
     * @param Template\Context   $context            The current context.
     * @param IndexStatsProvider $indexStatsProvider Index stats provider.
     * @param CollectionFactory  $collectionFactory  Index settings factory.
     * @param array              $data               Data.
     */
    public function __construct(
        Template\Context $context,
        IndexStatsProvider $indexStatsProvider,
        CollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->indexStatsProvider = $indexStatsProvider;
        $this->collectionFactory  = $collectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve ElasticSuite Indices.
     *
     * @return array|null
     *
     * @throws Exception
     */
    public function getElasticSuiteIndices(): ?array
    {
        if ($this->indexStatsProvider->getElasticSuiteIndices() !== null) {
            $elasticSuiteIndices = $this->indexStatsProvider->getElasticSuiteIndices();
            $excludedIndexStatus = [
                IndexStatus::GHOST_STATUS,
                IndexStatus::EXTERNAL_STATUS,
                IndexStatus::UNDEFINED_STATUS,
            ];
            $indices = [];

            foreach ($elasticSuiteIndices as $indexName => $indexAlias) {
                $indexData = $this->indexStatsProvider->indexStats($indexName, $indexAlias);
                $indexCollection = $this->collectionFactory->create(['name' => $indexData['index_name']])->load();

                if (array_key_exists('index_status', $indexData)
                    && !in_array($indexData['index_status'], $excludedIndexStatus)) {
                    $indices[] = [
                        'index_name' => $indexData['index_name'],
                        'index_status' => $indexData['index_status'],
                        'analyzers' => array_keys($indexCollection->getItems()["analysis"]["analyzer"]),
                    ];
                }
            }

            return $indices;
        }

        return null;
    }

    /**
     * Retrieve the Ajax Request URL.
     *
     * @return string
     */
    public function getAjaxRequestUrl()
    {
        return $this->getUrl('smile_elasticsuite_indices/analysis/request', ['ajax' => true]);
    }
}
