<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Plugin\Search;

/**
 * Query Factory Plugin.
 * Used to init Search Context when query is retrieved.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class QueryFactoryPlugin
{
    /**
     * @var \Smile\ElasticsuiteCore\Search\Context
     */
    private $searchContext;

    /**
     * CategoryPlugin constructor.
     *
     * @param \Smile\ElasticsuiteCore\Search\Context $searchContext Search Context
     */
    public function __construct(\Smile\ElasticsuiteCore\Search\Context $searchContext)
    {
        $this->searchContext = $searchContext;
    }

    /**
     * Set the current search query into the Search context after being retrieved.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param \Magento\Search\Model\QueryFactory $queryFactory The Query Factory
     * @param \Magento\Search\Model\Query        $query        The Query
     *
     * @return \Magento\Search\Model\Query
     */
    public function afterGet(\Magento\Search\Model\QueryFactory $queryFactory, \Magento\Search\Model\Query $query)
    {
        if ($query && $query->getQueryText() !== '') {
            $this->searchContext->setCurrentSearchQuery($query);
        }

        return $query;
    }
}
