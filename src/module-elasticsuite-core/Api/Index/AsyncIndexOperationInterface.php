<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Api\Index;

/**
 * Asynchronous Index Operations interface
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
interface AsyncIndexOperationInterface extends IndexOperationInterface
{
    /**
     * Call to a bulk action in future (async) mode.
     * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/6.x/future_mode.html
     *
     * @param \Smile\ElasticsuiteCore\Api\Index\Bulk\BulkRequestInterface $bulk The bulk to add to the queue.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface
     */
    public function addFutureBulk(\Smile\ElasticsuiteCore\Api\Index\Bulk\BulkRequestInterface $bulk);

    /**
     * Resolve all future bulks at once.
     * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/6.x/future_mode.html
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\Bulk\BulkResponseInterface[]
     */
    public function resolveFutureBulks();
}
