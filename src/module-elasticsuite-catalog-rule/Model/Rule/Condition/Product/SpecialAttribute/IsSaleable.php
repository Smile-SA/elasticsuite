<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogRule
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\SpecialAttribute;

use Magento\Config\Model\Config\Source\Yesno;
use Smile\ElasticsuiteCatalogRule\Api\Rule\Condition\Product\SpecialAttributeInterface;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product as ProductCondition;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;

/**
 * Special "is_saleable" attribute class.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 */
class IsSaleable implements SpecialAttributeInterface
{
    /**
     * @var Yesno
     */
    private $booleanSource;

    /**
     * @var QueryFactory
     */
    private QueryFactory $queryFactory;

    /**
     * IsSaleable constructor.
     *
     * @param Yesno        $booleanSource Boolean Source
     * @param QueryFactory $queryFactory  Query Factory
     */
    public function __construct(
        Yesno $booleanSource,
        QueryFactory $queryFactory
    ) {
        $this->booleanSource = $booleanSource;
        $this->queryFactory = $queryFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeCode()
    {
        return 'is_saleable';
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getSearchQuery(ProductCondition $condition)
    {
        return $this->queryFactory->create(
            QueryInterface::TYPE_TERM,
            ['value' => true, 'field' => 'stock.is_in_stock']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getOperatorName()
    {
        return ' ';
    }

    /**
     * {@inheritdoc}
     */
    public function getInputType()
    {
        return 'select';
    }

    /**
     * {@inheritdoc}
     */
    public function getValueElementType()
    {
        return 'hidden';
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getValueName($value)
    {
        return ' ';
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getValue($value)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getValueOptions()
    {
        return $this->booleanSource->toOptionArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return __('Only saleable products');
    }
}
