<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogGraphQl
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2023 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogGraphQl\Model\Resolver\Products;

/**
 * Elasticsuite search result for GraphQL.
 * Overridden to add the fact that the query was spellchecked or not.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogGraphQl
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class SearchResult extends \Magento\CatalogGraphQl\Model\Resolver\Products\SearchResult
{
    /**
     * @var array
     */
    private $data;

    /**
     * @param array $data Object Data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        parent::__construct($data);
    }

    /**
     * @return bool
     */
    public function isSpellchecked()
    {
        return (bool) ($this->data['isSpellchecked'] ?? false);
    }

    /**
     * @return ?int
     */
    public function getQueryId()
    {
        return $this->data['queryId'] ?? null;
    }
}
