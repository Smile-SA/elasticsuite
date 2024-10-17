<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Pierre GAUTHIER <pigau@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Ui\Component\Listing\Column;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Optimizers Boost Weight Column for Ui Component
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Pierre GAUTHIER <pigau@smile.fr>
 */
class BoostWeight extends Column
{
    /**
     * Serializer
     *
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @param ContextInterface    $context            Context.
     * @param UiComponentFactory  $uiComponentFactory UI Component factory.
     * @param SerializerInterface $serializer         Serializer.
     * @param array               $components         Components.
     * @param array               $data               Data.
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        SerializerInterface $serializer,
        array $components = [],
        array $data = []
    ) {
        $this->serializer = $serializer;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource Data source.
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $value  = '';
                $config = $this->serializer->unserialize($item['config']);
                $type   = $item['model'] ?? 'constant_score';
                if ($type === 'constant_score') {
                    $value = $config['constant_score_value'] ? ($config['constant_score_value'] . '%') : '';
                } elseif ($type === 'attribute_value') {
                    $factor   = $config['scale_factor'] ?? '';
                    $modifier = $config['scale_function'] ?? '';
                    $field    = $config['attribute_code'] ?? '';
                    $value    = sprintf("%s(%s * %s)", $modifier, $factor, $field);
                }

                $item[$this->getData('name')] = $value;
            }
        }

        return $dataSource;
    }
}
