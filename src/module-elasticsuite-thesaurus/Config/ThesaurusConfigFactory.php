<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile_ElasticSuite
 * @package   Smile_ElasticSuiteThesaurus
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteThesaurus\Config;

use Smile\ElasticSuiteCore\Search\Request\ContainerConfiguration\RelevanceConfig\Factory;
use Magento\Framework\ObjectManagerInterface;

/**
 * Thesaurus configuration factory.
 *
 * @category Smile_ElasticSuite
 * @package  Smile_ElasticSuiteThesaurus
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class ThesaurusConfigFactory extends \Smile\ElasticSuiteCore\Search\Request\ContainerConfiguration\RelevanceConfig\Factory
{
    /**
     * Constructor.
     *
     * @param ObjectManagerInterface $objectManager Object manager.
     * @param string                 $instanceName  Config class name
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        $instanceName = 'Smile\ElasticSuiteThesaurus\Config\ThesaurusConfig'
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
