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
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Ui\Component\Listing\Column;

/**
 * Simple Yes/No column renderer.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class YesNo extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * Convert boolean value to Yes/No text output.
     *
     * @param mixed[] $dataSource The data source
     *
     * @return mixed[]
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $config = $this->getConfiguration();
            $name   = $config['fieldName'] ?? $this->getName();
            foreach ($dataSource['data']['items'] as & $item) {
                $item[$name] = ($item[$name] ? __('Yes') : __('No'));
            }
        }

        return parent::prepareDataSource($dataSource);
    }
}
