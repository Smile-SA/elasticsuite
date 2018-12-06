<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteAnalytics\Block\Adminhtml\Search\Usage;

class SearchTerms extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magento\Search\Model\QueryFactory
     */
    private $queryFactory;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Search\Model\QueryFactory $queryFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->queryFactory = $queryFactory;
    }

    public function getTermsData()
    {
        $data = $this->getReport()->getData();

        foreach ($data as &$value) {
            $value['url'] = $this->getMerchandiserUrl($value['term']);
        }

        return $data;
    }

    private function getMerchandiserUrl($term)
    {
        $query = $this->queryFactory->create();
        $query->loadByQueryText($term);

        return $this->getUrl('search/term_merchandiser/edit', ['id' => $query->getId()]);
    }
}
