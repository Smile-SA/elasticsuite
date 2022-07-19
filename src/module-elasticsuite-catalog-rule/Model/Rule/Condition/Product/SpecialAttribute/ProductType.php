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
use Magento\Config\Model\Config\Source\Yesno;
use Magento\Framework\Search\Request\QueryInterface as QueryInterfaceAlias;

/**
 * Special product type attribute class.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class ProductType implements SpecialAttributeInterface
{
    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @var Yesno
     */
    private $booleanSource;

    /**
     * @var string
     */
    private $attributeCode;

    /**
     * @var string
     */
    private $typeIdentifier;

    /**
     * @var string
     */
    private $attributeLabel;

    /**
     * ProductType constructor.
     *
     * @param QueryFactory $queryFactory   Query Factory
     * @param Yesno        $booleanSource  Boolean Source
     * @param string       $attributeCode  Attribute Code
     * @param string       $typeIdentifier Type Identifier
     * @param string       $attributeLabel Attribute Label
     */
    public function __construct(
        QueryFactory $queryFactory,
        Yesno $booleanSource,
        string $attributeCode,
        string $typeIdentifier,
        string $attributeLabel
    ) {
        $this->queryFactory   = $queryFactory;
        $this->booleanSource  = $booleanSource;
        $this->attributeCode  = $attributeCode;
        $this->typeIdentifier = $typeIdentifier;
        $this->attributeLabel = $attributeLabel;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeCode(): string
    {
        return $this->attributeCode;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getSearchQuery(ProductCondition $condition): ?QueryInterface
    {
        $queryParams = [];
        $queryClause = 'must';

        $queryParams[$queryClause][] = $this->queryFactory->create(QueryInterface::TYPE_TERMS, [
            'field' => 'type_id', 'values' => [$this->typeIdentifier],
        ]);

        return $this->queryFactory->create(QueryInterfaceAlias::TYPE_BOOL, $queryParams);
    }

    /**
     * {@inheritdoc}
     */
    public function getOperatorName(): ?string
    {
        return ' ';
    }

    /**
     * {@inheritdoc}
     */
    public function getInputType(): string
    {
        return 'select';
    }

    /**
     * {@inheritdoc}
     */
    public function getValueElementType(): string
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
    public function getValueOptions(): array
    {
        return $this->booleanSource->toOptionArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return __($this->attributeLabel);
    }
}
