<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteIndices
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteIndices\Block\Adminhtml\GhostIndices;

use Exception;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Smile\ElasticsuiteIndices\Block\Widget\Grid\Column\Renderer\IndexStatus;
use Smile\ElasticsuiteIndices\Model\IndexStatsProvider;

/**
 * Block for displaying toolbar with the number of ghost indices and remove button when ghost indices exist.
 */
class Toolbar extends Template
{
    /**
     * @var IndexStatsProvider
     */
    protected IndexStatsProvider $indexStatsProvider;

    /**
     * Constructor.
     *
     * @param Context            $context            Adminhtml context.
     * @param IndexStatsProvider $indexStatsProvider Index stats provider.
     * @param array              $data               Block data.
     */
    public function __construct(
        Context $context,
        IndexStatsProvider $indexStatsProvider,
        array $data = []
    ) {
        $this->indexStatsProvider = $indexStatsProvider;
        parent::__construct($context, $data);
    }

    /**
     * Get the number of ghost indices.
     *
     * @return int
     * @throws Exception
     */
    public function getGhostIndicesCount(): int
    {
        $ghostIndices = 0;
        $elasticsuiteIndices = $this->indexStatsProvider->getElasticSuiteIndices();

        if ($elasticsuiteIndices !== null) {
            foreach ($elasticsuiteIndices as $indexName => $indexAlias) {
                $indexData = $this->indexStatsProvider->indexStats($indexName, $indexAlias);

                if (isset($indexData['index_status']) && $indexData['index_status'] === IndexStatus::GHOST_STATUS) {
                    $ghostIndices++;
                }
            }
        }

        return $ghostIndices;
    }

    /**
     * Determine whether the toolbar should be displayed.
     *
     * @return bool
     */
    public function canShow(): bool
    {
        return $this->getGhostIndicesCount() > 0;
    }

    /**
     * Retrieve a URL for removing ghost indices.
     *
     * @return string
     */
    public function getRemoveGhostIndicesUrl(): string
    {
        return $this->getUrl('smile_elasticsuite_indices/index/removeGhostIndices');
    }
}
