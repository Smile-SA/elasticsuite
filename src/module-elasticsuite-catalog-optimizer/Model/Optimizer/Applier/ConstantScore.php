<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Applier;

use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\ApplierInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;

/**
 * Applier ConstantScore Model
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Fanny DECLERCK <fadec@smile.fr>
 */
class ConstantScore implements ApplierInterface
{
    /**
     * {@inheritDoc}
     */
    public function getFunction(ContainerConfigurationInterface $containerConfiguration, OptimizerInterface $optimizer)
    {
        $function = [
            'weight' => 1 + ((float) $optimizer->getConfig('constant_score_value') / 100),
            'filter' => $optimizer->getRuleCondition()->getSearchQuery(),
        ];

        return $function;
    }
}
