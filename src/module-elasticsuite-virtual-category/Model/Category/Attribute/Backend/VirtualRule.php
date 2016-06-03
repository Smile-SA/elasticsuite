<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteVirtualCategory\Model\Category\Attribute\Backend;

/**
 * Virtual category rule attribute backend model.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class VirtualRule extends \Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend
{
    /**
     * @var \Smile\ElasticSuiteCatalogRule\Model\RuleFactory $ruleFactory
     */
    private $ruleFactory;

    /**
     * Constructor.
     *
     * @param \Smile\ElasticSuiteCatalogRule\Model\RuleFactory $ruleFactory Search rule factory.
     */
    public function __construct(\Smile\ElasticSuiteCatalogRule\Model\RuleFactory $ruleFactory)
    {
        $this->ruleFactory = $ruleFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function beforeSave($object)
    {
        $attributeCode = $this->getAttributeCode();
        $attributeData = $object->getData($attributeCode);

        $rule = $this->ruleFactory->create();

        if ($attributeData !== null && is_object($attributeData)) {
            $rule = $attributeData;
        } elseif ($attributeData !== null && is_array($attributeData)) {
            $rule->loadPost($attributeData);
        }

        $object->setData($attributeCode, serialize($rule->getConditions()->asArray()));

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function afterLoad($object)
    {
        $attributeCode = $this->getAttributeCode();
        $attributeData = $object->getData($attributeCode);

        $rule = $this->ruleFactory->create();
        $rule->setStoreId($object->getStoreId());

        if ($attributeData !== null && is_string($attributeData)) {
            $attributeData = unserialize($attributeData);
        }

        $rule->getConditions()->loadArray($attributeData);

        $object->setData($attributeCode, $rule);

        return $this;
    }

    /**
     * Get current attribute code.
     *
     * @return string
     */
    private function getAttributeCode()
    {
        return $this->getAttribute()->getAttributeCode();
    }
}
