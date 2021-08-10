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

use Smile\ElasticsuiteCatalogRule\Api\Rule\Condition\Product\SpecialAttributeInterface;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product as ProductCondition;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Special "has_image" attribute class.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class HasImage implements SpecialAttributeInterface
{
    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory
     */
    private $queryFactory;

    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    private $booleanSource;

    /**
     * HasImage constructor.
     *
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory  Query Factory
     * @param \Magento\Config\Model\Config\Source\Yesno                 $booleanSource Boolean Source
     */
    public function __construct(QueryFactory $queryFactory, \Magento\Config\Model\Config\Source\Yesno $booleanSource)
    {
        $this->queryFactory  = $queryFactory;
        $this->booleanSource = $booleanSource;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeCode()
    {
        return 'has_image';
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getSearchQuery(ProductCondition $condition)
    {
        $queryParams = [];
        $queryClause = 'must';

        $queryParams[$queryClause][] = $this->queryFactory->create(QueryInterface::TYPE_EXISTS, ['field' => 'image']);

        $queryParams[$queryClause][] = $this->queryFactory->create(QueryInterface::TYPE_NOT, [
            'query' => $this->queryFactory->create(QueryInterface::TYPE_TERMS, ['field' => 'image', 'values' => ['no_selection']]),
        ]);

        return $this->queryFactory->create(QueryInterface::TYPE_BOOL, $queryParams);
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
        return __('Only products with image');
    }
}
