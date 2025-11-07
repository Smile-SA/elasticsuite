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
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\Autocomplete\Category;

use Magento\Catalog\Model\ResourceModel\Category as CategoryResource;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;

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
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var array
     */
    private $categoryUrlSuffixes = [];

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
        $this->scopeConfig = $scopeConfig;
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

        $title = $documentSource['name'] ?? '';
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

        if ($documentSource && isset($documentSource['url'])) {
            $url = is_array($documentSource['url']) ? current($documentSource['url']) : $documentSource['url'];

            return trim($this->urlBuilder->getDirectUrl($url), '/');
        }

        if ($documentSource && isset($documentSource['url_path'])) {
            $urlPath = is_array($documentSource['url_path']) ? current($documentSource['url_path']) : $documentSource['url_path'];

            return trim($this->urlBuilder->getDirectUrl($urlPath), '/') . $this->getCategoryUrlSuffix($category->getStoreId());
        }

        return $category->getUrl();
    }

    /**
     * Get the configured category URL suffix for the given store.
     *
     * @param int $storeId Store ID.
     *
     * @return string
     */
    private function getCategoryUrlSuffix($storeId)
    {
        if (false === array_key_exists($storeId, $this->categoryUrlSuffixes)) {
            $this->categoryUrlSuffixes[$storeId] = (string) $this->scopeConfig->getValue(
                self::XML_PATH_CATEGORY_URL_SUFFIX,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }

        return $this->categoryUrlSuffixes[$storeId];
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

        // First occurrence is root category (1), second is root category of store.
        $rawPath = array_slice($rawPath, 2);

        // Last occurrence is the category displayed.
        array_pop($rawPath);

        $breadcrumb = [];
        foreach ($rawPath as $categoryId) {
            $breadcrumb[] = html_entity_decode($this->getCategoryNameById($categoryId, $category->getStoreId()));
        }

        return $breadcrumb;
    }

    /**
     * Retrieve a category name by its id, and store it in local cache
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
