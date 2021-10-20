<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Pierre Gauthier <pigau@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Model\Rule\WidgetCondition;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Product search rule condition  for pagebuilder widget.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Pierre Gauthier <pigau@smile.fr>
 */
class Product extends \Smile\ElasticsuiteVirtualCategory\Model\Rule\Condition\Product
{
    /**
     * @var string
     */
    protected $elementName = 'parameters';

    /**
     * {@inheritDoc}
     */
    public function getSearchQuery($excludedCategories = [], $virtualCategoryRoot = null): ?QueryInterface
    {
        // Fix some js encoding error with operators.
        $this->setData('operator', htmlspecialchars_decode($this->getOperator()));

        return parent::getSearchQuery($excludedCategories, $virtualCategoryRoot);
    }
}
