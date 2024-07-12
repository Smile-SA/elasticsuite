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
    /**
     * @var \Smile\ElasticsuiteCatalog\Helper\ProductAttribute
     */
    private $mappingHelper;

    /**
     * @var \Magento\Catalog\Api\ProductAttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * AttributeValue constructor.
     *
     * @param \Smile\ElasticsuiteCatalog\Helper\ProductAttribute       $mappingHelper              Mapping Helper
     * @param \Magento\Catalog\Api\ProductAttributeRepositoryInterface $productAttributeRepository Attribute Repository
     */
    public function __construct(
        \Smile\ElasticsuiteCatalog\Helper\ProductAttribute $mappingHelper,
        \Magento\Catalog\Api\ProductAttributeRepositoryInterface $productAttributeRepository
    ) {
        $this->mappingHelper       = $mappingHelper;
        $this->attributeRepository = $productAttributeRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function getFunction(ContainerConfigurationInterface $containerConfiguration, OptimizerInterface $optimizer)
    {
        $field       = $this->getField($containerConfiguration, $optimizer);
        $scaleFactor = (float) $optimizer->getConfig('scale_factor');
        $queryName   = sprintf('Optimizer [%s]:%d', $optimizer->getName(), $optimizer->getId());
        $query       = $optimizer->getRuleCondition()->getSearchQuery();
        $query->setName(($query->getName() !== '') ? $queryName . " => " . $query->getName() : $queryName);

        $function = [
            'field_value_factor' => [
                'field'    => $field,
                'factor'   => $scaleFactor,
                'modifier' => $optimizer->getConfig('scale_function'),
                'missing'  => 1 / $scaleFactor,
            ],
            'filter' => $query,
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
}
