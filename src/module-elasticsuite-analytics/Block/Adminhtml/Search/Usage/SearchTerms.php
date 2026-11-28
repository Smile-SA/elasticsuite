<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteAnalytics
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteAnalytics\Block\Adminhtml\Search\Usage;

/**
 * Block used to display search terms in the search usage dashboard.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 */
class SearchTerms extends \Magento\Backend\Block\Template
{
    /**
     * Constant for the max visible terms when the report is first displayed.
     */
    const MAX_VISIBLE_TERMS = 15;

    /**
     * @var \Magento\Search\Model\QueryFactory
     */
    private $queryFactory;

    /**
     * SearchTerms constructor.
     * @param \Magento\Backend\Block\Template\Context $context      Context.
     * @param \Magento\Search\Model\QueryFactory      $queryFactory Query factory.
     * @param array                                   $data         Data.
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Search\Model\QueryFactory $queryFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->queryFactory = $queryFactory;
    }

    /**
     * Get terms data from the report.
     *
     * @return mixed
     */
    public function getTermsData()
    {
        $data = [];

        try {
            $data = $this->getReport()->getData();
        } catch (\LogicException $e) {
            ;
        }

        if ($data) {
            $terms = [];
            foreach ($data as $key => $value) {
                if (isset($value['term'])) {
                    $terms[$key] = (string) $value['term'];
                }
            }
            $queryIds = $terms ? $this->getQueryIds($terms) : [];
            foreach ($data as $key => &$value) {
                $queryId = $queryIds[$key] ?? null;
                $value['url'] = $queryId ? $this->getUrl('search/term/edit', ['id' => $queryId]) : null;
            }
        }

        return $data;
    }

    /**
     * Return the number of max initially visible terms.
     *
     * @return int
     */
    public function getMaxVisibleTerms()
    {
        return self::MAX_VISIBLE_TERMS;
    }

    /**
     * Get query IDs using the database comparison rules for each report term.
     *
     * @param string[] $terms Search terms.
     *
     * @return array
     */
    private function getQueryIds(array $terms)
    {
        $query = $this->queryFactory->create();
        $resource = $query->getResource();
        $connection = $resource->getConnection();
        $queryIds = [];
        foreach (array_chunk($terms, 100, true) as $batch) {
            $columns = [];
            $aliases = [];
            foreach ($batch as $key => $term) {
                $alias = 'term_' . count($columns);
                $aliases[$key] = $alias;
                $columns[$alias] = new \Zend_Db_Expr(
                    'MAX(CASE WHEN query_text = ' . $connection->quote((string) $term) . ' THEN query_id END)'
                );
            }
            $select = $connection->select()->from($resource->getMainTable(), $columns)
                ->where('store_id = ?', $query->getStoreId())
                ->where('query_text IN (?)', array_values($batch));
            $result = $connection->fetchRow($select);
            foreach ($aliases as $key => $alias) {
                $queryIds[$key] = $result[$alias] ?? null;
            }
        }

        return $queryIds;
    }
}
