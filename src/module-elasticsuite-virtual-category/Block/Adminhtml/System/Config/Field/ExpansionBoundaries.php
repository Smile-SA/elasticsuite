<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Block\Adminhtml\System\Config\Field;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Phrase;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Smile\ElasticsuiteVirtualCategory\Helper\Config;

/**
 * Virtual category rules expansion boundaries setting: lists the configured categories under the field, with a warning for
 * the ones that would not behave as expected (unknown, not anchor, virtual).
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class ExpansionBoundaries extends Field
{
    /** @var CategoryCollectionFactory */
    private $categoryCollectionFactory;

    /** @var Config */
    private $config;

    /**
     * Constructor.
     *
     * @param Context                   $context                   Context.
     * @param CategoryCollectionFactory $categoryCollectionFactory Category collection factory.
     * @param Config                    $config                    Virtual category configuration.
     * @param array                     $data                      Data.
     * @param SecureHtmlRenderer|null   $secureRenderer            Secure renderer.
     */
    public function __construct(
        Context $context,
        CategoryCollectionFactory $categoryCollectionFactory,
        Config $config,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ) {
        parent::__construct($context, $data, $secureRenderer);
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->config = $config;
    }

    /**
     * Input field with a category chooser, followed by the configured categories.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @param AbstractElement $element Form element.
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $html = $this->getChooserHtml($element, parent::_getElementHtml($element));

        foreach ($this->getLines((string) $element->getValue()) as $line) {
            $html .= '<p class="note"><span>' . $line . '</span></p>';
        }

        return $html;
    }

    /**
     * Input field with a category chooser, as in the rule conditions: a folder icon opening a checkbox category tree
     * built from the ids in the input field, which writes the ids of the checked categories back into it, and an apply
     * icon closing it.
     *
     * @param AbstractElement $element     Form element.
     * @param string          $elementHtml Input field HTML.
     *
     * @return string
     */
    private function getChooserHtml(AbstractElement $element, string $elementHtml): string
    {
        $jsFormObject = 'rulesBoundaryCategoriesChooser';
        $chooser      = $this->getLayout()->createBlock(
            Template::class,
            '',
            [
                'data' => [
                    'template'       => 'Smile_ElasticsuiteVirtualCategory::system/config/field/boundary-categories-chooser.phtml',
                    'input_id'       => $element->getHtmlId(),
                    'element_html'   => $elementHtml,
                    'js_form_object' => $jsFormObject,
                    'chooser_url'    => $this->getUrl(
                        'catalog_rule/promo_widget/chooser',
                        ['attribute' => 'category_ids', 'form' => $jsFormObject]
                    ),
                ],
            ]
        );

        return $chooser->toHtml();
    }

    /**
     * Description of the configured categories, one line each, plus the ignored values.
     *
     * @param string $value Setting value.
     *
     * @return string[] HTML lines.
     */
    private function getLines(string $value): array
    {
        $lines       = [];
        $categoryIds = $this->config->parseVirtualRulesExpansionBoundaries($value);
        $categories  = $this->getCategories($categoryIds);
        $names       = $this->getPathNames($categories);

        foreach ($categoryIds as $categoryId) {
            $category = $categories->getItemById($categoryId);
            if ($category === null) {
                $lines[] = $this->getWarning(__('%1: unknown category.', $categoryId));
                continue;
            }

            $path    = $this->getPath($category, $names);
            $lines[] = $this->escapeHtml(sprintf('%d: %s', $categoryId, $path));
            if ((bool) $category->getIsVirtualCategory()) {
                $lines[] = $this->getWarning(
                    __(
                        '%1 is a virtual category: when used by other categories, its rule is ignored and only the'
                        . ' products assigned to it are used, usually none.',
                        $category->getName()
                    )
                );
            } elseif (!(bool) $category->getIsAnchor()) {
                $lines[] = $this->getWarning(
                    __(
                        '%1 is not an anchor category: when used by other categories, only the products directly'
                        . ' assigned to it are used.',
                        $category->getName()
                    )
                );
            }
        }

        $ignoredValues = array_diff(
            array_filter(array_map('trim', explode(',', $value)), 'strlen'),
            array_map('strval', $categoryIds)
        );
        if (!empty($ignoredValues)) {
            $lines[] = $this->getWarning(__('Ignored values: %1.', implode(', ', array_unique($ignoredValues))));
        }

        return $lines;
    }

    /**
     * Configured categories.
     *
     * @param int[] $categoryIds Category ids.
     *
     * @return CategoryCollection
     */
    private function getCategories(array $categoryIds): CategoryCollection
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->setStoreId(0)
            ->addAttributeToSelect(['name', 'is_anchor', 'is_virtual_category'])
            ->addIdFilter(empty($categoryIds) ? [0] : $categoryIds);

        return $collection;
    }

    /**
     * Names of the categories in the path of the configured ones, by id.
     *
     * @param CategoryCollection $categories Configured categories.
     *
     * @return string[]
     */
    private function getPathNames(CategoryCollection $categories): array
    {
        $pathIds = [];
        foreach ($categories as $category) {
            $pathIds = array_merge($pathIds, $category->getPathIds());
        }

        $names = [];
        if (!empty($pathIds)) {
            $collection = $this->categoryCollectionFactory->create();
            $collection->setStoreId(0)->addAttributeToSelect('name')->addIdFilter(array_unique($pathIds));
            foreach ($collection as $category) {
                $names[(int) $category->getId()] = (string) $category->getName();
            }
        }

        return $names;
    }

    /**
     * Path of a category, from its store root category.
     *
     * @param Category $category Category.
     * @param string[] $names    Names of the categories, by id.
     *
     * @return string
     */
    private function getPath(Category $category, array $names): string
    {
        // Skip the tree root ("Root Catalog"), shared by all the store root categories.
        $pathIds = array_slice($category->getPathIds(), 1);

        return implode(' > ', array_map(function ($categoryId) use ($names) {
            return $names[(int) $categoryId] ?? $categoryId;
        }, $pathIds));
    }

    /**
     * Warning line.
     *
     * @param Phrase $message Message.
     *
     * @return string HTML.
     */
    private function getWarning(Phrase $message): string
    {
        return '<strong>' . $this->escapeHtml((string) $message) . '</strong>';
    }
}
