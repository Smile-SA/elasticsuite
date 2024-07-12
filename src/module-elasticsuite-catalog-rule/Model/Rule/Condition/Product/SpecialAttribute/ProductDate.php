<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogRule
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\SpecialAttribute;

use Smile\ElasticsuiteCatalogRule\Api\Rule\Condition\Product\SpecialAttributeInterface;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product as ProductCondition;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Special product dates attribute class.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class ProductDate implements SpecialAttributeInterface
{
    /**
     * @var string
     */
    private $attributeCode;

    /**
     * @var string
     */
    private $attributeLabel;

    /**
     * @var string
     */
    private $fieldName;

    /**
     * @var QueryFactory
     */
    protected $queryFactory;

    /**
     * ProductDate constructor.
     *
     * @param string       $attributeCode  Attribute Code.
     * @param string       $attributeLabel Attribute Label.
     * @param string       $fieldName      Field Name.
     * @param QueryFactory $queryFactory   Query factory.
     */
    public function __construct(
        string $attributeCode,
        string $attributeLabel,
        string $fieldName,
        QueryFactory $queryFactory
    ) {
        $this->attributeCode  = $attributeCode;
        $this->attributeLabel = $attributeLabel;
        $this->fieldName      = $fieldName;
        $this->queryFactory   = $queryFactory;
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
    public function getSearchQuery(ProductCondition $condition)
    {
        // Retrieve the operator and days from the condition.
        $operator = $condition->getOperator();
        $value = (int) $condition->getValue();

        // Calculate the reference date based on the number of days.
        $dateReference = new \DateTime();
        $dateReference->setTime(0, 0, 0); // Set the time to the start of the day.
        $dateReference->modify('-' . $value . ' days');

        // Prepare the bounds based on the operator.
        $bounds = [];
        switch ($operator) {
            case '==':
            case '!=':
                $bounds['gte'] = $dateReference->format('Y-m-d');
                $bounds['lt'] = $dateReference->modify('+1 day')->format('Y-m-d');
                break;
            case '>':
                $bounds['lt'] = $dateReference->format('Y-m-d');
                break;
            case '<':
                $bounds['gt'] = $dateReference->format('Y-m-d');
                break;
            case '>=':
                $bounds['lte'] = $dateReference->format('Y-m-d');
                break;
            case '<=':
                $bounds['gte'] = $dateReference->format('Y-m-d');
                break;
            default:
                throw new \InvalidArgumentException('Invalid operator');
        }

        $queryParams = ['field' => $this->fieldName, 'bounds' => $bounds];
        $query = $this->queryFactory->create(QueryInterface::TYPE_RANGE, $queryParams);

        if (substr($operator, 0, 1) === '!') {
            $query = $this->applyNegation($query);
        }

        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function getOperatorName()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getInputType()
    {
        return 'numeric';
    }

    /**
     * {@inheritdoc}
     */
    public function getValueElementType()
    {
        return 'text';
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getValueName($value)
    {
        if ($value === null || '' === $value) {
            return '...';
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getValue($rawValue)
    {
        return $rawValue;
    }

    /**
     * {@inheritdoc}
     */
    public function getValueOptions()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return __($this->attributeLabel);
    }

    /**
     * Apply a negation to the current query.
     *
     * @param QueryInterface $query Negated query.
     *
     * @return QueryInterface
     */
    private function applyNegation(QueryInterface $query)
    {
        return $this->queryFactory->create(QueryInterface::TYPE_NOT, ['query' => $query]);
    }
}
