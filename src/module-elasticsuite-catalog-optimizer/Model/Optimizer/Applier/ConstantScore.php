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
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer;

/**
 * Applier ConstantScore Model
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Fanny DECLERCK <fadec@smile.fr>
 */
class ConstantScore implements ApplierInterface
{
    /**
     * @param ContainerConfigurationInterface                     $containerConfiguration Contrainer configuration.
     * @param \Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer $optimizer              Optimizer.
     *
     * @SuppressWarnings(PHPMD.LongVariable)
     * @return mixed
     */
    public function getFunction(ContainerConfigurationInterface $containerConfiguration, Optimizer $optimizer)
    {
        $function = [
            'weight' => 1 + ((float) $optimizer->getConfig('constant_score_value') / 100),
            'filter' => $optimizer->getRuleCondition()->getSearchQuery(),
        ];

        return $function;
    }
}
