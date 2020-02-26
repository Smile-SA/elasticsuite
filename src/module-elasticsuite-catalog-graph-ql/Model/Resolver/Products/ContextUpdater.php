<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogGraphQl
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogGraphQl\Model\Resolver\Products;

use Magento\Catalog\Api\CategoryRepositoryInterfaceFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Search\Model\QueryFactory;
use Smile\ElasticsuiteCore\Api\Search\ContextInterface;

/**
 * Elasticsuite context updater for product related GraphQL queries.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogGraphQl
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ContextUpdater
{
    /**
     * @var \Smile\ElasticsuiteCore\Api\Search\ContextInterface
     */
    private $context;

    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @var CategoryRepositoryInterfaceFactory
     */
    private $categoryRepositoryFactory;

    /**
     * @param ContextInterface                   $context                   Elasticsuite Context
     * @param QueryFactory                       $queryFactory              Query Factory
     * @param CategoryRepositoryInterfaceFactory $categoryRepositoryFactory Category Repository Factory
     */
    public function __construct(
        ContextInterface $context,
        QueryFactory $queryFactory,
        CategoryRepositoryInterfaceFactory $categoryRepositoryFactory
    ) {
        $this->context                   = $context;
        $this->queryFactory              = $queryFactory;
        $this->categoryRepositoryFactory = $categoryRepositoryFactory;
    }

    /**
     * Update search context according to current search.
     *
     * @param array $args GraphQL request arguments.
     */
    public function updateSearchContext($args)
    {
        if (!empty($args['search'])) {
            try {
                $query = $this->queryFactory->create()->loadByQueryText($args['search']);
            } catch (\Magento\Framework\Exception\LocalizedException $exception) {
                $query = $this->queryFactory->create();
            }

            $this->context->setCurrentSearchQuery($query);
        } elseif (!empty($args['filter']) && !empty($args['filter']['category_id'])) {
            if (isset($args['filter']['category_id']['eq'])) {
                try {
                    /** @var CategoryRepositoryInterface $categoryRepository */
                    $categoryRepository = $this->categoryRepositoryFactory->create();
                    $category           = $categoryRepository->get($args['filter']['category_id']['eq']);
                    if ($category->getId()) {
                        $this->context->setCurrentCategory($category);
                    }
                } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
                    ;
                }
            }
        }
    }
}
