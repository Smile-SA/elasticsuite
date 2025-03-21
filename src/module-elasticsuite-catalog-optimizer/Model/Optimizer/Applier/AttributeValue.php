<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Applier;

use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\ApplierInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;

/**
 * Applier model for optimizers based on attributes values.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class AttributeValue implements ApplierInterface
{
    /** @var string */
    const FUNCTION_LOG1P = 'log1p';

    /** @var string */
    const FUNCTION_SQRT = 'sqrt';

    /** @var string */
    const FUNCTION_NONE = 'none';

    /**
     * @var \Smile\ElasticsuiteCatalog\Helper\ProductAttribute
     */
    private $mappingHelper;

    /**
     * @var \Magento\Catalog\Api\ProductAttributeRepositoryInterface
     */
    private $attributeRepository;

    /** @var QueryFactory */
    private $queryFactory;

    /**
     * AttributeValue constructor.
     *
     * @param \Smile\ElasticsuiteCatalog\Helper\ProductAttribute        $mappingHelper              Mapping Helper
     * @param \Magento\Catalog\Api\ProductAttributeRepositoryInterface  $productAttributeRepository Attribute Repository
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory               Query Factory
     */
    public function __construct(
        \Smile\ElasticsuiteCatalog\Helper\ProductAttribute $mappingHelper,
        \Magento\Catalog\Api\ProductAttributeRepositoryInterface $productAttributeRepository,
        QueryFactory $queryFactory
    ) {
        $this->mappingHelper       = $mappingHelper;
        $this->attributeRepository = $productAttributeRepository;
        $this->queryFactory        = $queryFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function getFunction(ContainerConfigurationInterface $containerConfiguration, OptimizerInterface $optimizer)
    {
        $field       = $this->getField($containerConfiguration, $optimizer);
        $scaleFactor = (float) $optimizer->getConfig('scale_factor');

        $function = [
            'field_value_factor' => [
                'field'    => $field,
                'factor'   => $scaleFactor,
                'modifier' => $optimizer->getConfig('scale_function'),
                'missing'  => 1 / $scaleFactor,
            ],
            'filter' => $this->getFilter($optimizer, $field),
        ];

        return $function;
    }

    /**
     * Get field to apply boost on.
     *
     * @param ContainerConfigurationInterface $containerConfiguration Container Configuration
     * @param OptimizerInterface              $optimizer              Optimizer
     *
     * @return string
     */
    private function getField(ContainerConfigurationInterface $containerConfiguration, OptimizerInterface $optimizer)
    {
        $attributeCode = $optimizer->getConfig('attribute_code');

        try {
            $attribute = $this->getAttribute($attributeCode);
            $field     = $this->mappingHelper->getFilterField($attribute);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
            try {
                $field = $containerConfiguration->getMapping()->getField($attributeCode);
                $field = $field->getName();
            } catch (\LogicException $exception) {
                $field = $attributeCode;
            }
        }

        return $field;
    }

    /**
     * Get attribute by attribute code.
     *
     * @param string $attributeCode The attribute code
     *
     * @return \Magento\Catalog\Api\Data\ProductAttributeInterface
     */
    private function getAttribute($attributeCode)
    {
        return $this->attributeRepository->get($attributeCode);
    }

    /**
     * Compute the optimizer filter. Adds a filtering clause to properly ignore attribute values defined but
     * that can be a source of technical or functional error.
     *
     * @param OptimizerInterface $optimizer The optimizer
     * @param string             $field     The field
     *
     * @return QueryInterface
     */
    private function getFilter(OptimizerInterface $optimizer, $field)
    {
        $baseFilter  = $optimizer->getRuleCondition()->getSearchQuery();
        $queryName   = sprintf('Optimizer [%s]:%d', $optimizer->getName(), $optimizer->getId());

        $filter = $this->queryFactory->create(
            QueryInterface::TYPE_BOOL,
            [
                'must' => [
                    $baseFilter,
                    $this->queryFactory->create(QueryInterface::TYPE_EXISTS, ['field' => $field]),
                    $this->queryFactory->create(
                        QueryInterface::TYPE_RANGE,
                        ['field'  => $field, 'bounds' => ['gt' => $this->getMinValue($optimizer)]]
                    ),
                ],
            ]
        );

        $filter->setName(($baseFilter->getName() !== '') ? $queryName . " => " . $baseFilter->getName() : $queryName);

        return $filter;
    }

    /**
     * Returns the min value to filter out product to avoid either an error when computing the boost
     * if providing a forbidden value to the scale function (for instance 0 for log1p)
     * or (if enabled) penalizing products with an attribute value too low for the scale function to generate a positive boost.
     * For instance, for "log1p", products with a (scale_factor * (1+field value)) below 10 would actually be penalized.
     * For "sqrt", products with a (scale_factor * field value) below 1 would actually be penalized.
     *
     * @param OptimizerInterface $optimizer The optimizer
     *
     * @return float
     */
    private function getMinValue($optimizer)
    {
        $minValue = 0;

        if (false === (bool) $optimizer->getConfig('allow_negative_boost')) {
            $scaleFactor = (float) $optimizer->getConfig('scale_factor');
            $modifier = $optimizer->getConfig('scale_function');

            switch ($modifier) {
                case self::FUNCTION_LOG1P:
                    $minValue = ceil(9 / $scaleFactor);
                    break;
                case self::FUNCTION_SQRT:
                case self::FUNCTION_NONE:
                    $minValue = ceil(1 / $scaleFactor);
                    break;
            }
        }

        return $minValue;
    }
}
