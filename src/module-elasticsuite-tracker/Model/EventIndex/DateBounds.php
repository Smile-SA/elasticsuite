<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Botis <botis@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Model\EventIndex;

use Smile\ElasticsuiteTracker\Api\DateBoundsInterface;
use Smile\ElasticsuiteTracker\Model\ResourceModel\EventIndex\DateBounds as ResourceModel;

/**
 * Session index implementation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Botis <botis@smile.fr>
 */
class DateBounds implements DateBoundsInterface
{
    /**
     * @var ResourceModel
     */
    private $resourceModel;

    /**
     * Constructor.
     *
     * @param ResourceModel $resourceModel Resource model
     */
    public function __construct(
        ResourceModel $resourceModel
    ) {
        $this->resourceModel  = $resourceModel;
    }

    /**
     * {@inheritDoc}
     */
    public function getIndicesDateBounds(): array
    {
        return $this->resourceModel->getIndicesDateBounds();
    }
}
