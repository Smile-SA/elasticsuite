<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2017 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteVirtualCategory\Model\Category\Attribute\VirtualRule;

/**
 * Virtual Category rule save handler.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class SaveHandler implements \Magento\Framework\EntityManager\Operation\ExtensionInterface
{
    /**
     * The virtual rule attribute code.
     */
    const ATTRIBUTE_CODE = 'virtual_rule';

    /**
     * @var \Smile\ElasticsuiteCatalogRule\Model\RuleFactory $ruleFactory
     */
    private $ruleFactory;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $jsonSerializer;

    /**
     * Constructor.
     *
     * @param \Smile\ElasticsuiteCatalogRule\Model\RuleFactory $ruleFactory    Search rule factory.
     * @param \Magento\Framework\Serialize\Serializer\Json     $jsonSerializer JSON Serializer.
     */
    public function __construct(
        \Smile\ElasticsuiteCatalogRule\Model\RuleFactory $ruleFactory,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
    ) {
        $this->ruleFactory    = $ruleFactory;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($entity, $arguments = [])
    {
        $attributeData = $entity->getData(self::ATTRIBUTE_CODE);

        if ($attributeData !== null) {
            $rule = $this->ruleFactory->create();

            if (is_object($attributeData)) {
                $rule = $attributeData;
            } elseif (is_array($attributeData)) {
                $rule->loadPost($attributeData);
            } elseif (is_string($attributeData)) {
                $attributeData = $this->jsonSerializer->unserialize($attributeData);
                $rule->getConditions()->loadArray($attributeData);
            }

            $entity->setData(self::ATTRIBUTE_CODE, $this->jsonSerializer->serialize($rule->getConditions()->asArray()));
        }

        return $entity;
    }
}
