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
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Search\Model\QueryFactory;
use Smile\ElasticsuiteCore\Api\Search\ContextInterface;

/**
 * Elasticsuite context updater for product related GraphQL queries.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogGraphQl
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 *
 * @SuppressWarnings(PHPMD.LongVariable)
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

    /** @var Uid */
    private $uidEncoder;

    /**
     * @param ContextInterface                   $context                   Elasticsuite Context
     * @param QueryFactory                       $queryFactory              Query Factory
     * @param CategoryRepositoryInterfaceFactory $categoryRepositoryFactory Category Repository Factory
     * @param Uid                                $uidEncoder                Encoder Uid
     */
    public function __construct(
        ContextInterface $context,
        QueryFactory $queryFactory,
        CategoryRepositoryInterfaceFactory $categoryRepositoryFactory,
        Uid $uidEncoder
    ) {
        $this->context                   = $context;
        $this->queryFactory              = $queryFactory;
        $this->categoryRepositoryFactory = $categoryRepositoryFactory;
        $this->uidEncoder                = $uidEncoder;
    }

    /**
     * Update search context according to current search.
     *
     * @param array $args GraphQL request arguments.
     *
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlInputException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
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
        }

        if (!empty($args['filter']) && (!empty($args['filter']['category_id']) || !empty($args['filter']['category_uid']))) {
            if (isset($args['filter']['category_uid']) && isset($args['filter']['category_uid']['eq'])) {
                $categoryUid = $this->uidEncoder->decode($args['filter']['category_uid']['eq']);
            }
            $categoryId = $args['filter']['category_id']['eq'] ?? $categoryUid ?? false;

            if ($categoryId) {
                try {
                    /** @var CategoryRepositoryInterface $categoryRepository */
                    $categoryRepository = $this->categoryRepositoryFactory->create();
                    $category           = $categoryRepository->get($categoryId);
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
