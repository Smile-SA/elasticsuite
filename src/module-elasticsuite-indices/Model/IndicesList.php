<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteIndices
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteIndices\Model;

use Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface;

/**
 * Elasticsuite related indices list.
 * Will merge common indices built with elasticsuite_indices.xml but also others that could be created on the flight.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteIndices
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class IndicesList
{
    /**
     * @var \Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface
     */
    private $indexSettings;

    /**
     * @var array
     */
    private $specialIndices;

    /**
     * IndicesList constructor.
     *
     * @param \Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface $indexSettings  Indices Settings.
     * @param array                                                    $specialIndices Special Indices names, if any.
     */
    public function __construct(IndexSettingsInterface $indexSettings, $specialIndices = [])
    {
        $this->indexSettings  = $indexSettings;
        $this->specialIndices = $specialIndices;
    }

    /**
     * Get list of indices managed by Smile Elastic Suite
     *
     * @return array
     */
    public function getList()
    {
        $list = array_keys($this->indexSettings->getIndicesConfig());
        foreach ($this->specialIndices as $indexName) {
            $list[] = $indexName;
        }

        return $list;
    }
}
