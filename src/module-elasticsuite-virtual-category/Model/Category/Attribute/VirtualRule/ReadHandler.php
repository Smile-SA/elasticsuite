<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteVirtualCategory\Model\Category\Attribute\VirtualRule;

use Magento\Catalog\Model\Category;

/**
 * Virtual Category rule read handler.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ReadHandler implements \Magento\Framework\EntityManager\Operation\ExtensionInterface
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

        if (!is_object($attributeData)) {
            $rule = $this->ruleFactory->create();
            $rule->setStoreId($entity->getStoreId());

            if (is_array($attributeData)) {
                $rule->loadPost($attributeData);
                $attributeData = null;
            } elseif ($attributeData !== null && is_string($attributeData)) {
                $attributeData = $this->jsonSerializer->unserialize($attributeData);
            }

            if ($attributeData !== null && is_array($attributeData)) {
                $rule->getConditions()->loadArray($attributeData);
            }

            $entity->setData(self::ATTRIBUTE_CODE, $rule);
        }

        return $entity;
    }
}
