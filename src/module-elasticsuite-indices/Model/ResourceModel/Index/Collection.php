<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteIndices
 * @author    Dmytro ANDROSHCHUK <dmand@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteIndices\Model\ResourceModel\Index;

use Elasticsearch\Common\Exceptions\Missing404Exception;
use Magento\Framework\Data\Collection as DataCollection;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DataObject;
use Smile\ElasticsuiteCore\Client\Client;
use Smile\ElasticsuiteIndices\Block\Widget\Grid\Column\Renderer\IndexStatus;
use Smile\ElasticsuiteIndices\Helper\Index as IndexHelper;

/**
 * Class Resource Model: Index Collection
 *
 * @category Smile
 * @package  Smile\ElasticsuiteIndices
 * @author   Dmytro ANDROSHCHUK <dmand@smile.fr>
 */
class Collection extends DataCollection
{
    /**
     * @var Client
     */
    private $esClient;

    /**
     * @var IndexHelper
     */
    protected $indexHelper;

    /**
     * @param EntityFactoryInterface $entityFactory Entity factory.
     * @param Client                 $esClient      ElasticSearch client.
     * @param IndexHelper            $indexHelper   Index helper.
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        Client $esClient,
        IndexHelper $indexHelper
    ) {
        parent::__construct($entityFactory);

        $this->setItemObjectClass(DataObject::class);
        $this->esClient = $esClient;
        $this->indexHelper = $indexHelper;
    }

    /**
     * @inheritdoc
     *
     * @param bool $printQuery Is print query.
     * @param bool $logQuery   Is log query.
     *
     * @return Collection IndicesCollection.
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function loadData($printQuery = false, $logQuery = false): Collection
    {
        foreach ($this->indexHelper->getElasticSuiteIndices() as $indexName => $alias) {
            $item = $this->getNewEmptyItem();
            $data = [
                'index_name'  => $indexName,
                'index_alias' => $alias,
            ];

            try {
                $indexStatsResponse = $this->esClient->indexStats($indexName);
            } catch (Missing404Exception $e) {
                $data['index_status'] = IndexStatus::REBUILDING_STATUS;
                $this->addItem($item->setData($data));
                continue;
            }

            $indexStats = current($indexStatsResponse['indices']);
            $data['number_of_documents'] = $indexStats['total']['docs']['count'];
            $data['size'] = $this->indexHelper->sizeFormatted($indexStats['total']['store']['size_in_bytes']);
            $data['index_status'] = $this->indexHelper->getIndexStatus($indexName, $alias);
            $this->addItem($item->setData($data));
        }
        $this->_setIsLoaded(true);

        return $this;
    }
}
