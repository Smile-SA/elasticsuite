<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\Autocomplete\Category;

use Magento\Catalog\Model\CategoryFactory;
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
     * The offset to display on the beginning of the Breadcrumb
     */
    const START_BREADCRUMB_OFFSET = 1;

    /**
     * The offset to display on the end of the Breadcrumb
     */
    const END_BREADCRUMB_OFFSET = 1;

    /**
     * The string used when chunking
     */
    const CHUNK_STRING = "...";

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
     * @var \Magento\Catalog\Model\CategoryFactory|null
     */
    private $categoryFactory = null;

    /**
     * ItemFactory constructor.
     *
     * @param ObjectManagerInterface $objectManager   The Object Manager
     * @param UrlInterface           $urlBuilder      The Url Builder
     * @param ScopeConfigInterface   $scopeConfig     The Scope Config
     * @param CategoryFactory        $categoryFactory Category Factory
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        UrlInterface $urlBuilder,
        ScopeConfigInterface $scopeConfig,
        CategoryFactory $categoryFactory
    ) {
        parent::__construct($objectManager);
        $this->urlBuilder = $urlBuilder;
        $this->categoryUrlSuffix = $scopeConfig->getValue(self::XML_PATH_CATEGORY_URL_SUFFIX);
        $this->categoryFactory = $categoryFactory;
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

        $categoryData = [
            'title'      => $documentSource['name'],
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
        $chunkPath  = $this->getChunkedPath($category);
        $breadcrumb = [];

        foreach ($chunkPath as $categoryId) {
            $breadcrumb[] = $this->getCategoryNameById($categoryId, $category->getStoreId());
        }

        return implode(' > ', $breadcrumb);
    }

    /**
     * Return chunked (if needed) path for a category
     *
     * A chunked path is the first 2 highest ancestors and the 2 lowests levels of path
     *
     * If path is not longer than 4, complete path is used
     *
     * @param \Magento\Catalog\Model\Category $category The category
     *
     * @return array
     */
    private function getChunkedPath(\Magento\Catalog\Model\Category $category)
    {
        $path    = $category->getPath();
        $rawPath = explode('/', $path);

        // First occurence is root category (1), second is root category of store.
        $rawPath = array_slice($rawPath, 2);

        // Last occurence is the category displayed.
        array_pop($rawPath);

        $chunkedPath = $rawPath;

        if (count($rawPath) > (self::START_BREADCRUMB_OFFSET + self::END_BREADCRUMB_OFFSET)) {
            $chunkedPath = array_merge(
                array_slice($rawPath, 0, self::START_BREADCRUMB_OFFSET),
                [self::CHUNK_STRING],
                array_slice($rawPath, -self::END_BREADCRUMB_OFFSET)
            );
        }

        return $chunkedPath;
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
        if ($categoryId == self::CHUNK_STRING) {
            return self::CHUNK_STRING;
        }

        if (!isset($this->categoryNames[$categoryId])) {
            $category = $this->categoryFactory->create();
            $categoryResource = $category->getResource();
            $this->categoryNames[$categoryId] = $categoryResource->getAttributeRawValue($categoryId, "name", $storeId);
        }

        return $this->categoryNames[$categoryId];
    }
}
