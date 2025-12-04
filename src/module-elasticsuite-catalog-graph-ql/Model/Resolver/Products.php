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

namespace Smile\ElasticsuiteCatalogGraphQl\Model\Resolver;

use Magento\Catalog\Model\Layer\Resolver;
use Magento\CatalogGraphQl\Model\Resolver\Products\Query\ProductQueryInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ArgumentsProcessorInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Smile\ElasticsuiteCatalogGraphQl\DataProvider\Product\SearchCriteriaBuilder;
use Smile\ElasticsuiteCatalogGraphQl\Model\Resolver\Products\ContextUpdater;
use Smile\ElasticsuiteCatalogGraphQl\Model\Resolver\Products\Query\Search;
use Magento\CatalogGraphQl\DataProvider\Product\SearchCriteriaBuilder as MagentoSearchCriteriaBuilder;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Deferred\Product as ProductDataProvider;
use Magento\CatalogGraphQl\Model\Resolver\Product\ProductFieldsSelector;
use Magento\CatalogGraphQl\Model\AttributesJoiner;

/**
 * Elasticsuite custom implementation of GraphQL Products Resolver
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogGraphQl
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Products extends \Magento\CatalogGraphQl\Model\Resolver\Products  implements ResolverInterface
{
    /**
     * @var ProductQueryInterface
     */
    private $searchQuery;

    /**
     * @var \Smile\ElasticsuiteCatalogGraphQl\Model\Resolver\Products\ContextUpdater
     */
    private $contextUpdater;

    /**
     * @var ArgumentsProcessorInterface
     */
    private $argsProcessor;

    /**
     * @param ProductQueryInterface                         $searchQuery                Search Query
     * @param ContextUpdater                                $contextUpdater             Context Updater
     * @param ArgumentsProcessorInterface|null              $argumentProcessor          Args Processor
     * @param MagentoSearchCriteriaBuilder|null             $searchApiCriteriaBuilder   Core SearchCriteria Builder 
     * @param ProductDataProvider|null                      $productDataProvider        Product Data Provicer
     * @param AttributesJoiner|null                         $attributesJoiner           Attributes Joiner
     */
    public function __construct(
        ProductQueryInterface $searchQuery,
        ContextUpdater $contextUpdater,
        ?ArgumentsProcessorInterface $argumentProcessor = null,
        ?MagentoSearchCriteriaBuilder $searchApiCriteriaBuilder = null,
	?ProductDataProvider $productDataProvider = null,
	?AttributesJoiner $attributesJoiner = null
    ) {
        parent::__construct($searchQuery, $searchApiCriteriaBuilder);
	$this->productDataProvider = $productDataProvider ?? \Magento\Framework\App\ObjectManager::getInstance()->get(ProductDataProvider::class);
        $this->attributesJoiner = $attributesJoiner ?? \Magento\Framework\App\ObjectManager::getInstance()->get(AttributesJoiner::class);
        $this->searchQuery    = $searchQuery;
        $this->contextUpdater = $contextUpdater;
        $this->argsProcessor  = $argumentProcessor ?: ObjectManager::getInstance()->get(ArgumentsProcessorInterface::class);
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $args = $this->getProcessedArgs($info, $args);
        $this->validateArgs($args);
        if (isset($args['filter']['sku'])) {
            if (isset($args['filter']['sku']['eq'])) {
                $sku = $args['filter']['sku']['eq'];
                // Add product SKU and required fields to the data provider
                $this->productDataProvider->addProductSku($sku);
                $fields = $this->attributesJoiner->getQueryFields($info->fieldNodes[0]->selectionSet->selections[0], $info);
                $this->productDataProvider->addEavAttributes($fields);

                // Get product data synchronously
                $productData = $this->productDataProvider->getProductBySku($sku);

                if (empty($productData)) {
                    return [
                        'total_count'   => 0,
                        'items'         => [],
                        'suggestions'   => [],
                        'page_info'     => [
                            'page_size'    => 1,
                            'current_page' => 1,
                            'total_pages'  => 1,
                            'is_spellchecked' => false,
                            'query_id'     => 0
                        ],
                        'search_result' => null,
                        'layer_type'    => Resolver::CATALOG_LAYER_CATEGORY,
                    ];
                }

                // Format product data similar to how Products resolver returns it
                /** @var \Magento\Catalog\Model\Product $productModel */
                $productModel = $productData['model'];
                $formattedProduct = $productModel->getData();
                $formattedProduct['model'] = $productModel;

                // Add custom attributes
                if (!empty($productModel->getCustomAttributes())) {
                    foreach ($productModel->getCustomAttributes() as $customAttribute) {
                        if (!isset($formattedProduct[$customAttribute->getAttributeCode()])) {
                            $formattedProduct[$customAttribute->getAttributeCode()] = $customAttribute->getValue();
                        }
                    }
                }

                return [
                    'total_count'   => 1,
                    'items'         => [$formattedProduct],
                    'suggestions'   => [],
                    'suggestions'   =>
                    'page_info'     => [
                        'page_size'    => 1,
                        'current_page' => 1,
                        'total_pages'  => 1,
                        'is_spellchecked' => false,
                        'query_id'     => 0
                    ],
                    'search_result' => null,
                    'layer_type'    => Resolver::CATALOG_LAYER_CATEGORY,
                ];
            }
            return parent::resolve($field, $context, $info, $value, $args);
        }
        $this->contextUpdater->updateSearchContext($args);

        $searchResult = $this->searchQuery->getResult($args, $info, $context);
        $layerType    = Resolver::CATALOG_LAYER_CATEGORY;

        if (isset($args['search']) && (!empty($args['search']))) {
            $layerType = Resolver::CATALOG_LAYER_SEARCH;
        } 

        return [
            'total_count'   => $searchResult->getTotalCount(),
            'items'         => $searchResult->getProductsSearchResult(),
            'suggestions'   => $searchResult->getSuggestions(),
            'page_info'     => [
                'page_size'    => $searchResult->getPageSize(),
                'current_page' => $searchResult->getCurrentPage(),
                'total_pages'  => $searchResult->getTotalPages(),
                'is_spellchecked' => $searchResult->isSpellchecked(),
                'query_id'     => $searchResult->getQueryId(),
            ],
            'search_result' => $searchResult,
            'layer_type'    => $layerType,
        ];
    }

    /**
     * Validate GraphQL query arguments and throw exception if needed.
     *
     * @param array $args GraphQL query arguments
     *
     * @throws GraphQlInputException
     */
    private function validateArgs(array $args)
    {
        if (!isset($args['search']) && !isset($args['filter'])) {
            throw new GraphQlInputException(
                __("'search' or 'filter' input argument is required.")
            );
        }
    }

    /**
     * Process and return query arguments.
     *
     * @param ResolveInfo $info Resolve Info.
     * @param array|null  $args Unprocessed arguments.
     *
     * @return array
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlInputException
     */
    private function getProcessedArgs(ResolveInfo $info, ?array $args = null): array
    {
        if (null === $args) {
            return [];
        }

        return $this->argsProcessor->process((string) ($info->fieldName ?? ""), $args);
    }
}
