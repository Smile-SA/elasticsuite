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
namespace Smile\ElasticsuiteCatalogOptimizer\Ui\Component\Optimizer\Form\Modifier;

/**
 * Optimizer Ui Component Modifier.
 *
 * Used to prepare optimizer preview configuration.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Preview implements \Magento\Ui\DataProvider\Modifier\ModifierInterface
{
    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    private $urlBuilder;

    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    private $localeFormat;

    /**
     * @var \Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Locator\LocatorInterface
     */
    private $locator;

    /**
     * Preview constructor.
     *
     * @param \Magento\Backend\Model\UrlInterface                                          $urlBuilder   Url Builder
     * @param \Magento\Framework\Locale\FormatInterface                                    $localeFormat Locale Format
     * @param \Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Locator\LocatorInterface $locator      Locator
     */
    public function __construct(
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Locator\LocatorInterface $locator
    ) {
        $this->urlBuilder   = $urlBuilder;
        $this->localeFormat = $localeFormat;
        $this->locator      = $locator;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $config = [
            'loadUrl'      => $this->getPreviewUrl(),
            'price_format' => $this->localeFormat->getPriceFormat(),
        ];

        $meta['optimizer_preview_fieldset']['children']['optimizer_preview']['arguments']['data']['config'] = $config;

        return $meta;
    }

    /**
     * Retrieve the optimizer Preview URL.
     *
     * @return string
     */
    private function getPreviewUrl()
    {
        $urlParams = ['ajax' => true];

        return $this->urlBuilder->getUrl('smile_elasticsuite_catalog_optimizer/optimizer/preview', $urlParams);
    }
}
