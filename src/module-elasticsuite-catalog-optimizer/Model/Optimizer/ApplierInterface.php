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
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer;

/**
 * ApplierInterface Model
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Fanny DECLERCK <fadec@smile.fr>
 */
interface ApplierInterface
{
    /**
     * @param ContainerConfigurationInterface                     $containerConfiguration Contrainer configuration.
     * @param \Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer $optimizer              Optimizer.
     *
     * @SuppressWarnings(PHPMD.LongVariable)
     * @return mixed
     */
    public function getFunction(ContainerConfigurationInterface $containerConfiguration, Optimizer $optimizer);
}
