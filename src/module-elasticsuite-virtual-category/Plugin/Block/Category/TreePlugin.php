<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Plugin\Block\Category;

use Magento\Catalog\Block\Adminhtml\Category\Tree;
use Magento\Catalog\Model\ResourceModel\Category\Collection\Factory as CategoryCollectionFactory;
use Magento\Framework\Json\DecoderInterface;
use Magento\Framework\Json\EncoderInterface;
use Magento\UrlRewrite\Block\Catalog\Category\Tree as UrlRewriteCategoryTree;
use Psr\Log\LoggerInterface;

/**
 * Category tree plugin that decorates virtual categories with a specific CSS class.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 */
class TreePlugin
{
    /**
     * @var CategoryCollectionFactory
     */
    private $collectionFactory;

    /**
     * @var DecoderInterface
     */
    private $jsonDecoder;

    /**
     * @var EncoderInterface
     */
    private $jsonEncoder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array|null
     */
    private $virtualCategoriesIds;

    /**
     * @var string
     */
    private $cssClass = '';

    /**
     * Constructor.
     *
     * @param CategoryCollectionFactory $collectionFactory Collection factory.
     * @param DecoderInterface          $jsonDecoder       JSON decoder.
     * @param EncoderInterface          $jsonEncoder       JSON encoder.
     * @param LoggerInterface           $logger            Logger.
     * @param string                    $cssClass          CSS class to add for virtual categories.
     */
    public function __construct(
        CategoryCollectionFactory $collectionFactory,
        DecoderInterface $jsonDecoder,
        EncoderInterface $jsonEncoder,
        LoggerInterface $logger,
        $cssClass = 'es-esfeature__logo'
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->jsonDecoder = $jsonDecoder;
        $this->jsonEncoder = $jsonEncoder;
        $this->logger = $logger;
        $this->cssClass = $cssClass;
    }

    /**
     * After plugin - Get tree.
     * Add decoration for virtual categories.
     *
     * @param Tree       $subject           Original admin category tree block.
     * @param array      $result            Tree data as an array.
     * @param mixed|null $parenNodeCategory Parent node category.
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetTree(Tree $subject, $result, $parenNodeCategory = null)
    {
        if (!empty($result) && !empty($this->getAllVirtualCategoriesIds())) {
            $result = $this->getDecoratedTree($result);
        }

        return $result;
    }

    /**
     * After plugin - Get tree JSON.
     * Add decoration for virtual categories.
     *
     * @param Tree       $subject           Original admin category tree block.
     * @param string     $result            Tree data as an JSON encoded array.
     * @param mixed|null $parenNodeCategory Parent node category.
     *
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetTreeJson(Tree $subject, $result, $parenNodeCategory = null)
    {
        if (!empty($result) && !empty($this->getAllVirtualCategoriesIds())) {
            $result = $this->getDecoratedJsonTree($result);
        }

        return $result;
    }

    /**
     * After plugin - Get (URL rewrite edit screen) category tree.
     *
     * @param UrlRewriteCategoryTree $subject        Original URL rewrite edit category tree block.
     * @param array|string           $result         Original generated category tree.
     * @param int|null               $parentId       Parent/root category id.
     * @param bool|null              $asJson         Whether the generated category tree was requested in JSON.
     * @param int                    $recursionLevel Max depth/recursion level.
     *
     * @return array|string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function afterGetTreeArray(UrlRewriteCategoryTree $subject, $result, $parentId = null, $asJson = false, $recursionLevel = 3)
    {
        if (!empty($result) && !empty($this->getAllVirtualCategoriesIds())) {
            if ($asJson) {
                $result = $this->getDecoratedJsonTree($result);
            } else {
                $result = $this->getDecoratedTree($result);
            }
        }

        return $result;
    }

    /**
     * Return the decorated version of a category tree.
     *
     * @param array $tree Category tree which could start with a root node or not.
     *
     * @return array
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function getDecoratedTree($tree)
    {
        $originalTree = $tree;
        try {
            if (array_key_exists('id', $tree)) {
                $this->decorateNode($tree);
            } else {
                foreach ($tree as &$rootNode) {
                    $this->decorateNode($rootNode);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Error decorating the category tree for virtual categories: %s', $e->getMessage())
            );
            $this->logger->error($e->getMessage());
            $tree = $originalTree;
        }

        return $tree;
    }

    /**
     * Return the decorated version of a category tree in JSON format.
     *
     * @param string $jsonTree Category tree as a JSON encoded string.
     *
     * @return string
     */
    private function getDecoratedJsonTree($jsonTree)
    {
        $originalJsonTree = $jsonTree;
        try {
            $categoryTree = $this->jsonDecoder->decode($jsonTree);
            $categoryTree = $this->getDecoratedTree($categoryTree);
            $jsonTree = $this->jsonEncoder->encode($categoryTree);
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Error decorating the JSON category tree for virtual categories: %s', $e->getMessage())
            );
            $jsonTree = $originalJsonTree;
        }

        return $jsonTree;
    }

    /**
     * Decorate a category tree node if it is virtual and then dig into the children.
     *
     * Supports both legacy (`cls`) and jsTree (`li_attr.class`) formats
     * to ensure compatibility across Magento versions.
     *
     * @param array $node Category tree node.
     *
     * @return void
     */
    private function decorateNode(array &$node): void
    {
        $categoryId = $node['id'] ?? 0;

        if (array_key_exists($categoryId, $this->getAllVirtualCategoriesIds())) {
            // Legacy Magento key (<= 2.4.7).
            $legacyClasses = $node['cls'] ?? '';
            if ($legacyClasses !== '') {
                $legacyClasses .= ' ';
            }
            $legacyClasses .= $this->cssClass;
            $node['cls'] = $legacyClasses;

            // JsTree standard key (>= 2.4.8).
            if (!isset($node['li_attr']) || !is_array($node['li_attr'])) {
                $node['li_attr'] = [];
            }

            $liAttrClasses = $node['li_attr']['class'] ?? '';
            if ($liAttrClasses !== '') {
                $liAttrClasses .= ' ';
            }
            $liAttrClasses .= $this->cssClass;
            $node['li_attr']['class'] = $liAttrClasses;
        }

        if (!empty($node['children']) && is_array($node['children'])) {
            foreach ($node['children'] as &$childNode) {
                $this->decorateNode($childNode);
            }
        }
    }

    /**
     * Get the ids of all virtual categories.
     *
     * @return array
     */
    private function getAllVirtualCategoriesIds()
    {
        if (null === $this->virtualCategoriesIds) {
            $this->virtualCategoriesIds = [];

            try {
                $collection = $this->collectionFactory->create();
                $collection->addAttributeToFilter('is_virtual_category', 1);

                $this->virtualCategoriesIds = array_flip($collection->getAllIds());
            } catch (\Exception $e) {
                ;
            }
        }

        return $this->virtualCategoriesIds;
    }
}
