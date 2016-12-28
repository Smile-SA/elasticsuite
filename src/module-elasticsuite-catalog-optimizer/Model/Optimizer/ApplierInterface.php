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
namespace Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer;

use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;

/**
 * ApplierInterface Model
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Fanny DECLERCK <fadec@smile.fr>
 */
interface ApplierInterface
{
    /**
     * Build the function applied to the container.
     *
     * @param ContainerConfigurationInterface $containerConfiguration Contrainer configuration.
     * @param OptimizerInterface              $optimizer              Optimizer.
     *
     * @return mixed
     */
    public function getFunction(ContainerConfigurationInterface $containerConfiguration, OptimizerInterface $optimizer);
}
