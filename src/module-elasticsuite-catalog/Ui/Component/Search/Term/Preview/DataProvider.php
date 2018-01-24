<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Ui\Component\Search\Term\Preview;

use Magento\Ui\DataProvider\AbstractDataProvider;

/**
 * Search merchandiser form dataprovider.
 *
 * @category Smile
 * @package Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class DataProvider extends AbstractDataProvider
{
    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    private $localeFormat;

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    private $urlBuilder;

    /**
     * @var \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Search\Position
     */
    private $resourceModel;

    /**
     * Constructor.
     *
     * @param string                                                                 $name              Component Name
     * @param string                                                                 $primaryFieldName  Primary Field Name
     * @param string                                                                 $requestFieldName  Request Field Name.
     * @param \Magento\Search\Model\ResourceModel\Query\CollectionFactory            $collectionFactory Query collection factory.
     * @param \Magento\Framework\Locale\FormatInterface                              $localeFormat      Locale formatter.
     * @param \Magento\Backend\Model\UrlInterface                                    $urlBuilder        URL builder.
     * @param \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Search\Position $resourceModel     Resource model for search position.
     * @param array                                                                  $meta              Init meta.
     * @param array                                                                  $data              Init data.
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        \Magento\Search\Model\ResourceModel\Query\CollectionFactory $collectionFactory,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Search\Position $resourceModel,
        array $meta = [],
        array $data = []
    ) {
        $this->collection    = $collectionFactory->create();
        $this->localeFormat  = $localeFormat;
        $this->urlBuilder    = $urlBuilder;
        $this->resourceModel = $resourceModel;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function getData()
    {
        $data = parent::getData();
        $items = [];

        foreach ($data['items'] as $currentItem) {
            $queryId = $currentItem[$this->primaryFieldName];
            $currentItem['sorted_products'] = json_encode($this->getSortedProducts($queryId), JSON_FORCE_OBJECT);
            $currentItem['price_format'] = $this->localeFormat->getPriceFormat();
            $currentItem['product_sorter_load_url'] = $this->getProductSorterLoadUrl();
            $items[$queryId] = $currentItem;
        }

        return $items;
    }

    /**
     * Return list of sorted products for the query.
     *
     * @param int $queryId Query id.
     *
     * @return array
     */
    private function getSortedProducts($queryId)
    {
        return $this->resourceModel->getProductPositionsByQuery($queryId);
    }

    /**
     * Return product sorter load URL.
     *
     * @return string
     */
    private function getProductSorterLoadUrl()
    {
        return $this->urlBuilder->getUrl('search/term_merchandiser/load', ['ajax' => true]);
    }
}
