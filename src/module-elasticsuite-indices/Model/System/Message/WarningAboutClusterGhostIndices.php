<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteIndices
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2022 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteIndices\Model\System\Message;

use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;
use Smile\ElasticsuiteIndices\Model\IndexStatsProvider;

/**
 * ElasticSuite Warning about too much ghost indices in the cluster
 *
 * @category Smile
 * @package  Smile\ElasticsuiteIndices
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class WarningAboutClusterGhostIndices implements MessageInterface
{
    /**
     * Route to Elasticsuite -> Indices page
     */
    private const ROUTE_ELASTICSUITE_INDICES = 'smile_elasticsuite_indices';

    public const GHOST_STATUS = 'ghost';

    /**
     * @var IndexStatsProvider
     */
    protected $indexStatsProvider;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param IndexStatsProvider $indexStatsProvider Index stats provider
     * @param UrlInterface       $urlBuilder         Url builder
     */
    public function __construct(
        IndexStatsProvider $indexStatsProvider,
        UrlInterface $urlBuilder
    ) {
        $this->indexStatsProvider = $indexStatsProvider;
        $this->urlBuilder         = $urlBuilder;
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function isDisplayed()
    {
        if ($this->getNumberOfGhostIndices() && $this->getNumberOfGhostIndices() > 1) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentity()
    {
        return hash('sha256', 'ELASTICSUITE_GHOST_INDICES_WARNING');
    }

    /**
     * {@inheritdoc}
     */
    public function getSeverity()
    {
        return self::SEVERITY_MAJOR;
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function getText()
    {
        $messageDetails = '';

        // @codingStandardsIgnoreStart
        $messageDetails .= __(
            'You have <strong>%1 ghost indices</strong>. Ghost indices have a footprint on your Elasticsearch cluster health. You should consider removing them.',
            $this->getNumberOfGhostIndices()
        ) . '<br/>';
        $messageDetails .= __(
            'Click here to go to the <a href="%1"><strong>Elasticsuite Indices</strong></a> page to take appropriate actions.',
            $this->getElasticsuiteIndicesUrl()
        );
        // @codingStandardsIgnoreEnd

        return $messageDetails;
    }

    /**
     * Get number of the Ghost Elasticsuite Indices
     *
     * @return mixed
     * @throws \Exception
     */
    private function getNumberOfGhostIndices()
    {
        if ($this->indexStatsProvider->getElasticSuiteIndices() !== null) {
            $elasticsuiteIndices = $this->indexStatsProvider->getElasticSuiteIndices();
            $ghostIndices = [];

            foreach ($elasticsuiteIndices as $indexName => $indexAlias) {
                $indexData = $this->indexStatsProvider->indexStats($indexName, $indexAlias);

                if (array_key_exists('index_status', $indexData)
                    && $indexData['index_status'] === self::GHOST_STATUS) {
                    $ghostIndices[] = [
                        'index_name' => $indexData['index_name'],
                        'index_status' => $indexData['index_status'],
                    ];
                }
            }

            if (!empty($ghostIndices)) {
                return count($ghostIndices);
            }
        }

        return false;
    }

    /**
     * Get URL to the admin Elasticsuite Indices Status page
     *
     * @return string
     */
    private function getElasticsuiteIndicesUrl()
    {
        return $this->urlBuilder->getUrl(self::ROUTE_ELASTICSUITE_INDICES);
    }
}
