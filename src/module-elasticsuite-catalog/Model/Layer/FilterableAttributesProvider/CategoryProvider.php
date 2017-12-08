<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2017 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Layer\FilterableAttributesProvider;

use Smile\ElasticsuiteCatalog\Model\Layer\Category\FilterableAttributeListFactory;
use Smile\ElasticsuiteCatalog\Model\Layer\FilterableAttributesProviderInterface;

/**
 * Category Provider for Filterable Attributes
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class CategoryProvider implements FilterableAttributesProviderInterface
{
    /**
     * @var FilterableAttributeListFactory
     */
    private $filterableAttributesListFactory;

    /**
     * CategoryProvider constructor.
     *
     * @param FilterableAttributeListFactory $filterableAttributeListFactory Filterable Attributes List Factory
     */
    public function __construct(FilterableAttributeListFactory $filterableAttributeListFactory)
    {
        $this->filterableAttributesListFactory = $filterableAttributeListFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterableAttributes(\Magento\Catalog\Model\Layer $layer)
    {
        return $this->filterableAttributesListFactory->create(['category' => $layer->getCurrentCategory()]);
    }
}
