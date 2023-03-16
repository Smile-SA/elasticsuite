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
 * @copyright 2020 Smile
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
     * @var \Smile\ElasticsuiteTracker\Model\IndexManager
     */
    private $indexManager;

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
        \Smile\ElasticsuiteTracker\Model\IndexManager $indexManager
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
     * @return \Smile\ElasticsuiteCore\Api\Index\IndexInterface|null
     */
    public function getIndex($indexIdentifier, $storeId, $date)
    {
        $indexName = $indexIdentifier;

        try {
            $indexName  = $this->indexManager->getIndexName($indexIdentifier, $storeId, $date);

            if (!isset($this->indices[$indexName])) {
                $index = $this->indexManager->getIndex($indexIdentifier, $storeId, $date);
                if ($index instanceof \Smile\ElasticsuiteCore\Api\Index\IndexInterface) {
                    $this->indices[$index->getName()] = $index;
                }

                if ($index === null) {
                    $this->indices[$indexName] = null;
                }
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->indices[$indexName] = null;
        }

        return $this->indices[$indexName];
    }
}
