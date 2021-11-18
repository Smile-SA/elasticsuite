<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types=1);

namespace Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\Deprecation;

/**
 * Encodes and decodes id and uid values
 *
 * @deprecated To be removed when Magento v2.4.1 is no longer supported.
 * @see \Magento\Framework\GraphQl\Query\Uid
 */
class Uid
{
    /**
     * Encode ID value to UID
     *
     * @param string $id
     * @return string
     */
    public function encode(string $id): string
    {
        return base64_encode($id);
    }
}
