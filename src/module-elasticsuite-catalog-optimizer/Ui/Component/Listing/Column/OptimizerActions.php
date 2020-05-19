<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\UrlInterface;

/**
 * Optimizers Actions for Ui Component
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Fanny DECLERCK <fadec@smile.fr>
 */
class OptimizerActions extends Column
{
    /**
     * Edit Url path
     **/
    const OPTIMIZER_URL_PATH_EDIT = 'smile_elasticsuite_catalog_optimizer/optimizer/edit';

    /**
     * Delete Url path
     **/
    const OPTIMIZER_URL_PATH_DELETE = 'smile_elasticsuite_catalog_optimizer/optimizer/delete';

    /**
     * Duplicate Url path
     **/
    const OPTIMIZER_URL_PATH_DUPLICATE = 'smile_elasticsuite_catalog_optimizer/optimizer/duplicate';

    /** @var UrlInterface */
    protected $urlBuilder;

    /**
     * @var string
     */
    private $editUrl;

    /**
     * @var string
     */
    private $duplicateUrl;

    /**
     * @param ContextInterface   $context            Application context
     * @param UiComponentFactory $uiComponentFactory Ui Component Factory
     * @param UrlInterface       $urlBuilder         URL Builder
     * @param array              $components         Components
     * @param array              $data               Component Data
     * @param string             $editUrl            Edit Url
     * @param string             $duplicateUrl       Duplicate Url
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = [],
        $editUrl = self::OPTIMIZER_URL_PATH_EDIT,
        $duplicateUrl = self::OPTIMIZER_URL_PATH_DUPLICATE
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->editUrl = $editUrl;
        $this->duplicateUrl = $duplicateUrl;

        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource The data source
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $name = $this->getData('name');

                if (isset($item['optimizer_id'])) {
                    $item[$name]['edit'] = [
                        'href'  => $this->urlBuilder->getUrl($this->editUrl, ['id' => $item['optimizer_id']]),
                        'label' => __('Edit'),
                    ];

                    $item[$name]['duplicate'] = [
                        'href'  => $this->urlBuilder->getUrl($this->duplicateUrl, ['id' => $item['optimizer_id']]),
                        'label' => __('Duplicate'),
                    ];

                    $item[$name]['delete'] = [
                        'href'    => $this->urlBuilder->getUrl(self::OPTIMIZER_URL_PATH_DELETE, ['id' => $item['optimizer_id']]),
                        'label'   => __('Delete'),
                        'confirm' => [
                            'title'   => __('Delete ${ $.$data.name }'),
                            'message' => __('Are you sure you want to delete ${ $.$data.name } ?'),
                        ],
                    ];
                }
            }
        }

        return $dataSource;
    }
}
