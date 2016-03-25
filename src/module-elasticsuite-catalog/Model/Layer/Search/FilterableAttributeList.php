<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCatalog\Model\Layer\Search;

/**
 * Custom implementation of the search filterable attribute list to load attribute set info with the collection.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class FilterableAttributeList extends \Magento\Catalog\Model\Layer\Search\FilterableAttributeList
{
    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * {@inheritDoc}
     */
    protected function _prepareAttributeCollection($collection)
    {
        $collection->addSetInfo(true);
        $collection->addIsFilterableInSearchFilter()
            ->addVisibleFilter();

        return $collection;
    }
}
