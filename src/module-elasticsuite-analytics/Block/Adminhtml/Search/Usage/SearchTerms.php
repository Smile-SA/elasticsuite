<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteAnalytics
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2018 Smile
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

        foreach ($data as &$value) {
            $value['url'] = $this->getMerchandiserUrl($value['term']);
        }

        return $data;
    }

    /**
     * Get the term merchandiser URL for a given search term.
     *
     * @param string $term Search term.
     *
     * @return string
     */
    private function getMerchandiserUrl($term)
    {
        $query = $this->queryFactory->create();
        $query->loadByQueryText($term);

        return $this->getUrl('search/term_merchandiser/edit', ['id' => $query->getId()]);
    }
}
