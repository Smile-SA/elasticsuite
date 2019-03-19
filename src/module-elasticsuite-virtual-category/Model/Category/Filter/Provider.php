<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
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

    public function __construct(
        \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory,
        \Smile\ElasticsuiteVirtualCategory\Model\Category\Attribute\VirtualRule\ReadHandler $readHandler
    ) {
        parent::__construct($queryFactory);
        $this->readHandler = $readHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function getConditionValue(CategoryInterface $category)
    {
        return $this->getVirtualRule($category)->getCategorySearchQuery($category);
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryFilter(CategoryInterface $category)
    {
        return $this->getVirtualRule($category)->getCategorySearchQuery($category);
    }

    /**
     * Load virtual rule of a category. Can occurs when data is set directly as array to the category
     * (Eg. when the category edit form is submitted with error and populated from session data).
     *
     * @param CategoryInterface $category Category
     *
     * @return \Smile\ElasticsuiteVirtualCategory\Api\Data\VirtualRuleInterface
     */
    private function getVirtualRule(CategoryInterface $category)
    {
        $this->readHandler->execute($category);

        return $category->getVirtualRule();
    }
}
