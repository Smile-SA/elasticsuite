<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\Autocomplete\Category;

use Magento\Catalog\Model\ResourceModel\Category as CategoryResource;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\UrlInterface;

/**
 * Create an autocomplete item from a category.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ItemFactory extends \Magento\Search\Model\Autocomplete\ItemFactory
{
    /**
     * XML path for category url suffix
     */
    const XML_PATH_CATEGORY_URL_SUFFIX = 'catalog/seo/category_url_suffix';

    /**
     * @var array An array containing category names, to use as local cache
     */
    protected $categoryNames = [];

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $urlBuilder;

    /**
     * @var null
     */
    private $categoryUrlSuffix = null;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category|null
     */
    private $categoryResource = null;

    /**
     * ItemFactory constructor.
     *
     * @param ObjectManagerInterface $objectManager    The Object Manager
     * @param UrlInterface           $urlBuilder       The Url Builder
     * @param ScopeConfigInterface   $scopeConfig      The Scope Config
     * @param CategoryResource       $categoryResource Category Resource Model
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        UrlInterface $urlBuilder,
        ScopeConfigInterface $scopeConfig,
        CategoryResource $categoryResource
    ) {
        parent::__construct($objectManager);
        $this->urlBuilder = $urlBuilder;
        $this->categoryUrlSuffix = $scopeConfig->getValue(self::XML_PATH_CATEGORY_URL_SUFFIX);
        $this->categoryResource = $categoryResource;
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data)
    {
        $data = $this->addCategoryData($data);
        unset($data['category']);

        return parent::create($data);
    }

    /**
     * Load category data and append them to the original data.
     *
     * @param array $data Autocomplete item data.
     *
     * @return array
     */
    private function addCategoryData($data)
    {
        $category = $data['category'];

        $documentSource = $category->getDocumentSource();

        $title = $documentSource['name'];
        if (is_array($title)) {
            $title = current($title);
        }

        $categoryData = [
            'title'      => html_entity_decode($title),
            'url'        => $this->getCategoryUrl($category),
            'breadcrumb' => $this->getCategoryBreadcrumb($category),
        ];

        $data = array_merge($data, $categoryData);

        return $data;
    }

    /**
     * Retrieve category Url from the document source.
     * Done from the document source to prevent having to use addUrlRewrite to result on category collection.
     *
     * @param \Magento\Catalog\Model\Category $category The category.
     *
     * @return string
     */
    private function getCategoryUrl($category)
    {
        $documentSource = $category->getDocumentSource();

        if ($documentSource && isset($documentSource['url_path'])) {
            $urlPath = is_array($documentSource['url_path']) ? current($documentSource['url_path']) : $documentSource['url_path'];

            $url = trim($this->urlBuilder->getUrl($urlPath), '/') . $this->categoryUrlSuffix;

            return $url;
        }

        return $category->getUrl();
    }

    /**
     * Return a mini-breadcrumb for a category
     *
     * @param \Magento\Catalog\Model\Category $category The category
     *
     * @return array
     */
    private function getCategoryBreadcrumb(\Magento\Catalog\Model\Category $category)
    {
        $path    = $category->getPath();
        $rawPath = explode('/', $path);

        // First occurence is root category (1), second is root category of store.
        $rawPath = array_slice($rawPath, 2);

        // Last occurence is the category displayed.
        array_pop($rawPath);

        $breadcrumb = [];
        foreach ($rawPath as $categoryId) {
            $breadcrumb[] = html_entity_decode($this->getCategoryNameById($categoryId, $category->getStoreId()));
        }

        return $breadcrumb;
    }

    /**
     * Retrieve a category name by it's id, and store it in local cache
     *
     * @param int $categoryId The category Id
     * @param int $storeId    The store Id
     *
     * @return string
     */
    private function getCategoryNameById($categoryId, $storeId)
    {
        if (!isset($this->categoryNames[$categoryId])) {
            $categoryResource = $this->categoryResource;
            $this->categoryNames[$categoryId] = $categoryResource->getAttributeRawValue($categoryId, "name", $storeId);
        }

        return $this->categoryNames[$categoryId];
    }
}
