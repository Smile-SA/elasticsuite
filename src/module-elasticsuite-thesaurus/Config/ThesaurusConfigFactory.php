<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteThesaurus
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteThesaurus\Config;

use Smile\ElasticsuiteCore\Search\Request\ContainerConfiguration\RelevanceConfig\Factory;
use Magento\Framework\ObjectManagerInterface;

/**
 * Thesaurus configuration factory.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class ThesaurusConfigFactory extends \Smile\ElasticsuiteCore\Search\Request\ContainerConfiguration\RelevanceConfig\Factory
{
    /**
     * Constructor.
     *
     * @param ObjectManagerInterface $objectManager Object manager.
     * @param string                 $instanceName  Config class name
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        $instanceName = 'Smile\ElasticsuiteThesaurus\Config\ThesaurusConfig'
    ) {
        parent::__construct($objectManager, $instanceName);
    }

    /**
     * {@inheritDoc}
     */
    protected function loadConfiguration($scopeCode)
    {
        return $this->getConfigValue('thesaurus', $scopeCode);
    }
}
