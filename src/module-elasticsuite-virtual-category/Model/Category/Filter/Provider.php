<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Model\Category\Filter;

use Magento\Catalog\Api\Data\CategoryInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteVirtualCategory\Api\Data\VirtualRuleInterface;

/**
 * Category filter provider
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Provider extends \Smile\ElasticsuiteCatalog\Model\Category\Filter\Provider
{
    /**
     * @var \Smile\ElasticsuiteVirtualCategory\Model\Category\Attribute\VirtualRule\ReadHandler
     */
    private $readHandler;

    /**
     * @var \Smile\ElasticsuiteVirtualCategory\Helper\Rule
     */
    private $helper;

    /**
     * Provider constructor.
     *
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory                           $queryFactory Query Factory
     * @param \Smile\ElasticsuiteVirtualCategory\Model\Category\Attribute\VirtualRule\ReadHandler $readHandler  Read Handler
     * @param \Smile\ElasticsuiteVirtualCategory\Helper\Rule                                      $helper       Rule Helper
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory,
        \Smile\ElasticsuiteVirtualCategory\Model\Category\Attribute\VirtualRule\ReadHandler $readHandler,
        \Smile\ElasticsuiteVirtualCategory\Helper\Rule $helper
    ) {
        parent::__construct($queryFactory);
        $this->readHandler = $readHandler;
        $this->helper      = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function getConditionValue(CategoryInterface $category)
    {
        return $this->getCategorySearchQuery($category);
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryFilter(CategoryInterface $category)
    {
        return $this->getCategorySearchQuery($category);
    }

    /**
     * Get category search query
     *
     * @param \Magento\Catalog\Api\Data\CategoryInterface $category Category
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\QueryInterface
     */
    private function getCategorySearchQuery(CategoryInterface $category)
    {
        $virtualRule = $category->getVirtualRule();
        if (!($virtualRule instanceof VirtualRuleInterface)) {
            return $this->loadVirtualRule($category)->getCategorySearchQuery($category);
        }

        return $this->helper->loadUsingCache($category, 'getCategorySearchQuery');
    }

    /**
     * Load virtual rule of a category. Can occurs when data is set directly as array to the category
     * (Eg. when the category edit form is submitted with error and populated from session data).
     *
     * @param CategoryInterface $category Category
     *
     * @return \Smile\ElasticsuiteVirtualCategory\Api\Data\VirtualRuleInterface
     */
    private function loadVirtualRule(CategoryInterface $category)
    {
        $this->readHandler->execute($category);

        return $category->getVirtualRule();
    }
}
