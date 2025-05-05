<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\Healthcheck;

use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCore\Api\Healthcheck\CheckInterface;
use Smile\ElasticsuiteCore\Model\Healthcheck\AbstractCheck;

/**
 * Checks that, verify if after installation of Elasticsuite:
 * - the default value of the 'is_anchor' category attribute is still as 1 and its frontend input is still correct as 'hidden';
 * - look for / list for categories (with id <> 1) with is_anchor = 0.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 */

class CategoryIsAnchorConfigCheck extends AbstractCheck
{
    /**
     * Maximum number of non-anchor categories to display in the description output.
     */
    private const MAX_DISPLAYED_NON_ANCHOR_CATEGORIES_LIMIT = 5;

    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Constructor.
     *
     * @param Config                    $eavConfig                 EAV config.
     * @param CategoryCollectionFactory $categoryCollectionFactory Category collection factory.
     * @param StoreManagerInterface     $storeManager              Store manager.
     * @param UrlInterface              $urlBuilder                URL builder.
     * @param int                       $sortOrder                 Sort order (default: 70).
     * @param int                       $severity                  Severity level.
     */
    public function __construct(
        Config $eavConfig,
        CategoryCollectionFactory $categoryCollectionFactory,
        StoreManagerInterface $storeManager,
        UrlInterface $urlBuilder,
        int $sortOrder = 70,
        int $severity = MessageInterface::SEVERITY_CRITICAL
    ) {
        parent::__construct($urlBuilder, $sortOrder, $severity);
        $this->eavConfig = $eavConfig;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier(): string
    {
        return 'category_is_anchor_config_check';
    }

    /**
     * {@inheritDoc}
     * @throws LocalizedException
     */
    public function getDescription(): string
    {
        $description = '';
        $defaultText = __(
            'For showing products in categories, Elasticsuite relies on making sure all categories are "anchor" categories,
             that is displaying both their "own" products and the products associated with their subcategories.'
        );

        // If the attribute configuration check failed.
        if (!$this->isAnchorAttributeConfigValid()) {
            // @codingStandardsIgnoreStart
            $description = implode(
                '<br />',
                [
                    $defaultText,
                    __(
                        'This may cause some issues in the catalog navigation and search results pages.'
                    ),
                ]
            );
            // @codingStandardsIgnoreEnd
        }

        $nonAnchorCount = $this->getNonAnchorCategoryCount();

        // If the category configuration and attribute configuration check are successful.
        if ($nonAnchorCount === 0 && $this->isAnchorAttributeConfigValid()) {
            // @codingStandardsIgnoreStart
            $description = implode(
                '<br />',
                [
                    $defaultText,
                    __(
                        'All categories are correctly configured as "anchor" categories.'
                    ),
                ]
            );
            // @codingStandardsIgnoreEnd
        }

        // If the category configuration check failed.
        if ($nonAnchorCount > 0) {
            $nonAnchorCategories = $this->getNonAnchorCategoryCollection();

            // If the nonAnchorCategories is ≤ 5 categories.
            if ($nonAnchorCount <= self::MAX_DISPLAYED_NON_ANCHOR_CATEGORIES_LIMIT) {
                $itemsList = $this->renderCategoriesList($nonAnchorCategories);

                // @codingStandardsIgnoreStart
                $description = implode(
                    '<br />',
                    [
                        $defaultText,
                        __(
                            'Some of your categories are not configured as "anchor" categories, this may cause some unexpected issues in the catalog navigation and search result pages.'
                        ),
                        __(
                            'This is the list of categories: %1',
                            [$itemsList]
                        ),
                    ]
                );
                // @codingStandardsIgnoreEnd
            } else {
            // If nonAnchorCategories is > 5 categories.
                $itemsList = $this->renderCategoriesList($nonAnchorCategories, self::MAX_DISPLAYED_NON_ANCHOR_CATEGORIES_LIMIT);
                $remainingCount = $nonAnchorCount - self::MAX_DISPLAYED_NON_ANCHOR_CATEGORIES_LIMIT;

                // @codingStandardsIgnoreStart
                $description = implode(
                    '<br />',
                    [
                        $defaultText,
                        __(
                            'Some of your categories are not configured as "anchor" categories, this may cause some unexpected issues in the catalog navigation and search result pages.'
                        ),
                        __(
                            'Here are the first 5 categories: %1',
                            [$itemsList]
                        ),
                    ]
                );

                // The final line 'There are X more categories…' should not be preceded by a <br /> because it visually breaks the flow after the list.
                $description .= __(
                    'There are %1 more categories in that situation.',
                    $remainingCount
                );
                // @codingStandardsIgnoreEnd
            }
        }

        return $description;
    }

    /**
     * {@inheritDoc}
     * @throws LocalizedException
     */
    public function getStatus(): string
    {
        return (
            $this->isAnchorAttributeConfigValid() &&
            $this->getNonAnchorCategoryCount() === 0
        ) ? CheckInterface::STATUS_PASSED : CheckInterface::STATUS_FAILED;
    }

    /**
     * Validates the configuration of the "is_anchor" category attribute.
     * Ensures the default value is 1 and frontend input is hidden.
     *
     * @return bool
     */
    private function isAnchorAttributeConfigValid(): bool
    {
        try {
            $attribute = $this->eavConfig->getAttribute('catalog_category', 'is_anchor');

            return ((int) $attribute->getDefaultValue() === 1) && ($attribute->getFrontendInput() === 'hidden');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Returns the collection of non-anchor categories (excluding root).
     *
     * @return Collection
     * @throws LocalizedException
     */
    private function getNonAnchorCategoryCollection(): Collection
    {
        $storeId = (int) $this->storeManager->getStore()->getId();

        return $this->categoryCollectionFactory->create()
            ->addAttributeToSelect(['name'])
            ->addAttributeToFilter('is_anchor', ['eq' => 0])
            ->addAttributeToFilter('entity_id', ['neq' => 1])
            ->setStoreId($storeId);
    }

    /**
     * Returns the total number of non-anchor categories.
     *
     * @return int
     * @throws LocalizedException
     */
    private function getNonAnchorCategoryCount(): int
    {
        return (int) $this->getNonAnchorCategoryCollection()->getSize();
    }

    /**
     * Render categories list as an HTML list.
     *
     * @param Collection $categories Category collection.
     * @param int|null   $limit      Optional display limit.
     *
     * @return string
     */
    private function renderCategoriesList(Collection $categories, ?int $limit = null): string
    {
        $items = ['<ul>'];

        $count = 0;
        foreach ($categories as $category) {
            if ($limit !== null && $count++ >= $limit) {
                break;
            }

            $url = $this->urlBuilder->getUrl('catalog/category/edit', ['id' => $category->getId()]);
            $name = $category->getName();
            $items[] = sprintf('<li><a href="%s" target="_blank">%s</a></li>', $url, $name);
        }

        $items[] = '</ul>';

        return implode('', $items);
    }
}
