<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteAnalytics
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Model\Data\Fixer\Event;

use Magento\Framework\View\Layout\PageType\Config as PageTypeConfig;
use Smile\ElasticsuiteCore\Api\Client\ClientInterface;
use Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder as QueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteTracker\Api\EventIndexInterface;
use Smile\ElasticsuiteTracker\Model\Data\Fixer\DataFixerInterface;

/**
 * Behavioral data fixer for mistyped checkout_cart_add events where the page type identifier
 * is located in "page.identifier" instead of "page.type.identifier".
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 */
class MistypedAddToCartAddEvent implements DataFixerInterface
{
    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var IndexSettingsInterface
     */
    private $indexSettings;

    /**
     * @var PageTypeConfig
     */
    private $pageTypeConfig;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * Constructor.
     *
     * @param QueryFactory           $queryFactory   Query factory.
     * @param QueryBuilder           $queryBuilder   Query Builder.
     * @param IndexSettingsInterface $indexSettings  Index settings.
     * @param PageTypeConfig         $pageTypeConfig Layout page types config.
     * @param ClientInterface        $client         Elasticsearch client.
     */
    public function __construct(
        QueryFactory $queryFactory,
        QueryBuilder $queryBuilder,
        IndexSettingsInterface $indexSettings,
        PageTypeConfig $pageTypeConfig,
        ClientInterface $client
    ) {
        $this->queryFactory = $queryFactory;
        $this->queryBuilder = $queryBuilder;
        $this->indexSettings = $indexSettings;
        $this->pageTypeConfig = $pageTypeConfig;
        $this->client = $client;
    }

    /**
     * {@inheritDoc}
     */
    public function fixInvalidData(int $storeId): int
    {
        $result = DataFixerInterface::FIX_COMPLETE;

        try {
            $indexAlias = $this->indexSettings->getIndexAliasFromIdentifier(
                EventIndexInterface::INDEX_IDENTIFIER,
                $storeId
            );

            $query = $this->queryBuilder->buildQuery(
                $this->queryFactory->create(
                    QueryInterface::TYPE_BOOL,
                    [
                        'must' => [
                            $this->queryFactory->create(
                                QueryInterface::TYPE_MISSING,
                                ['field' => 'page.type.identifier']
                            ),
                            $this->queryFactory->create(
                                QueryInterface::TYPE_TERM,
                                ['field' => 'page.identifier', 'value' => 'checkout_cart_add']
                            ),
                        ],
                    ]
                )
            );

            $updatePageIdentifier = <<<EOF
if (!ctx._source.page.containsKey('type')) { ctx._source.page.type = params.typeInfo; }
else { ctx._source.page.type.identifier=params.typeInfo.identifier; ctx._source.page.type.label=params.typeInfo.label; }
ctx._source.page.remove('identifier');
ctx._source.page.remove('label');
EOF;
            $indicesNames = $this->client->getIndicesNameByAlias($indexAlias);
            foreach ($indicesNames as $indexName) {
                $params = [
                    'index' => $indexName,
                    'body' => [
                        'query' => $query,
                        'script' => [
                            'source' => $updatePageIdentifier,
                            'lang' => 'painless',
                            'params' => [
                                'typeInfo' => [
                                    'identifier' => 'checkout_cart_add',
                                    'label' => stripslashes($this->getPageTypeLabel('checkout_cart_add')),
                                ],
                            ],
                        ],
                        'conflicts' => 'proceed',
                    ],
                ];
                $this->client->updateByQuery($params);
            }
        } catch (\Exception $e) {
            $result = DataFixerInterface::FIX_FAILURE;
        }

        return $result;
    }

    /**
     * Human-readable version of the page type identifier.
     *
     * @param string $pageTypeIdentifier Page type identifier.
     *
     * @return string
     */
    private function getPageTypeLabel($pageTypeIdentifier)
    {
        foreach ($this->pageTypeConfig->getPageTypes() as $identifier => $pageType) {
            if ($pageTypeIdentifier === $identifier) {
                return $pageType['label'];
            }
        }

        return '';
    }
}
