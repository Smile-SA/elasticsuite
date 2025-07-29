<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Ui\Component\Optimizer\Form\Modifier;

/**
 * Optimizer Ui Component Modifier.
 *
 * Used to populate search queries dynamicRows.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class SearchTerms implements \Magento\Ui\DataProvider\Modifier\ModifierInterface
{
    /**
     * @var \Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Locator\LocatorInterface
     */
    private $locator;

    /**
     * @var \Magento\Search\Model\ResourceModel\Query\CollectionFactory
     */
    private $queryCollection;

    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    private $yesNo;

    /**
     * Search Terms constructor.
     *
     * @param \Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Locator\LocatorInterface $locator                Locator
     * @param \Magento\Search\Model\ResourceModel\Query\CollectionFactory                  $queryCollectionFactory Search Collection Factory
     * @param \Magento\Config\Model\Config\Source\Yesno                                    $yesNo                  Yes/No source value.
     */
    public function __construct(
        \Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Locator\LocatorInterface $locator,
        \Magento\Search\Model\ResourceModel\Query\CollectionFactory $queryCollectionFactory,
        \Magento\Config\Model\Config\Source\Yesno $yesNo
    ) {
        $this->locator         = $locator;
        $this->yesNo           = $yesNo;
        $this->queryCollection = $queryCollectionFactory->create();
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        $optimizer = $this->locator->getOptimizer();

        if ($optimizer && $optimizer->getId() && isset($data[$optimizer->getId()])) {
            if (isset($data[$optimizer->getId()]['quick_search_container'])
                && isset($data[$optimizer->getId()]['quick_search_container']['query_ids'])) {
                $queriesData = $this->fillQueryData($data[$optimizer->getId()]['quick_search_container']['query_ids']);
                $data[$optimizer->getId()]['quick_search_container']['query_ids'] = [];
                $data[$optimizer->getId()]['quick_search_container']['apply_to'] = (int) false;
                if (!empty($queriesData)) {
                    $data[$optimizer->getId()]['quick_search_container']['query_ids'] = $queriesData;
                    $data[$optimizer->getId()]['quick_search_container']['apply_to'] = (int) true;
                }
            }
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        return $meta;
    }

    /**
     * Get query data to fill the dynamicRows in Ui Component Form.
     *
     * @param integer[] $queryIds The query ids
     *
     * @return array
     */
    private function fillQueryData($queryIds)
    {
        $data = [];

        $collection  = $this->queryCollection->addFieldToFilter('query_id', $queryIds);
        $yesNoValues = $this->yesNo->toArray();

        foreach ($collection as $query) {
            /** @var \Magento\Search\Model\Query $query */
            $data[] = [
                'id'              => $query->getId(),
                'query_text'      => $query->getQueryText(),
                'is_spellchecked' => $yesNoValues[(int) $query->getIsSpellchecked()],
                'popularity'      => $query->getPopularity(),
                'num_results'     => $query->getNumResults(),
            ];
        }

        return $data;
    }
}
