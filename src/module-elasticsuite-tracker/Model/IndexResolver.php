<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Model;

/**
 * Resolve tracking indices.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class IndexResolver
{
    /**
     * @var \Smile\ElasticsuiteCore\Api\Index\IndexInterface[]
     */
    private $indices = [];

    /**
     * @var \Smile\ElasticsuiteCore\Api\Index\IndexInterfaceFactory
     */
    private $indexFactory;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface
     */
    private $indexSettings;


    /**
     * Constructor.
     *
     * @param \Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface $indexSettings Index settings.
     * @param \Smile\ElasticsuiteCore\Api\Index\IndexInterfaceFactory  $indexFactory  Index factory.
     * @param \Smile\ElasticsuiteTracker\Model\IndexManager            $indexManager  Index Manager.
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface $indexSettings,
        \Smile\ElasticsuiteCore\Api\Index\IndexInterfaceFactory $indexFactory,
        IndexManager $indexManager
    ) {
        $this->indexFactory  = $indexFactory;
        $this->indexSettings = $indexSettings;
        $this->indexManager  = $indexManager;
    }

    /**
     * Get index by identifier, store and date.
     *
     * @param string $indexIdentifier Index identifier.
     * @param int    $storeId         Store id.
     * @param string $date            Date.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\IndexInterface
     */
    public function getIndex($indexIdentifier, $storeId, $date)
    {
        $indexName = $indexIdentifier;

        try {
            $indexAlias = $this->indexManager->getIndexAlias($indexIdentifier, $storeId);
            $indexName  = $this->indexManager->getIndexName($indexIdentifier, $storeId, $date);

            if (!isset($this->indices[$indexName])) {
                $indexSettings = $this->indexSettings->getIndicesConfig();
                $indexConfig = array_merge(['identifier' => $indexAlias, 'name' => $indexName], $indexSettings[$indexIdentifier]);
                $this->indices[$indexName] = $this->indexFactory->create($indexConfig);
                $this->indexManager->createIndexIfNotExists($this->indices[$indexName], $storeId);
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->indices[$indexName] = null;
        }

        return $this->indices[$indexName];
    }
}
