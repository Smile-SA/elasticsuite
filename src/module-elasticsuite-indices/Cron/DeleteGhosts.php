<?php

namespace Smile\ElasticsuiteIndices\Cron;

use Smile\ElasticsuiteIndices\Block\Widget\Grid\Column\Renderer\IndexStatus;

class DeleteGhosts
{
    /**
     * @var \Smile\ElasticsuiteIndices\Model\IndexStatsProvider
     */
    protected $indexStatsProvider;

    public function __construct(
        \Smile\ElasticsuiteIndices\Model\IndexStatsProvider $indexStatsProvider
    ) {
        $this->indexStatsProvider = $indexStatsProvider;
    }

    public function execute()
    {
        $indices = $this->indexStatsProvider->getElasticSuiteIndices();
        foreach ($indices as $name => $alias) {
            $indexStats = $this->indexStatsProvider->indexStats($name, $alias);
            if(isset($indexStats['index_status']) && $indexStats['index_status'] === IndexStatus::GHOST_STATUS) {
                $this->indexStatsProvider->deleteIndex($name);
            }

        }
    }
}
