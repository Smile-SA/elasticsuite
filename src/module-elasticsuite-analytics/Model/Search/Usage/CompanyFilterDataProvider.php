<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteAnalytics
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteAnalytics\Model\Search\Usage;

use Magento\Framework\Api\Filter;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface;
use Smile\ElasticsuiteAnalytics\Model\Report\Context as ReportContext;

/**
 * Data provider for the search usage dashboard's company filter field.
 *
 * Only ever supplies the currently selected company_id (or none), so the field pre-fills correctly -
 * see CompanyFilterOptions for how its label is seeded.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 */
class CompanyFilterDataProvider implements DataProviderInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $primaryFieldName;

    /**
     * @var string
     */
    private $requestFieldName;

    /**
     * @var ReportContext
     */
    private $reportContext;

    /**
     * @var array
     */
    private $meta;

    /**
     * @var array
     */
    private $data;

    /**
     * Constructor.
     *
     * @param string        $name             Data provider name.
     * @param string        $primaryFieldName Primary field name.
     * @param string        $requestFieldName Request field name.
     * @param ReportContext $reportContext    Report context.
     * @param array         $meta             Meta data (unused, no dynamic meta needed).
     * @param array         $data             Data provider configuration data.
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportContext $reportContext,
        array $meta = [],
        array $data = []
    ) {
        $this->name = $name;
        $this->primaryFieldName = $primaryFieldName;
        $this->requestFieldName = $requestFieldName;
        $this->reportContext = $reportContext;
        $this->meta = $meta;
        $this->data = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrimaryFieldName()
    {
        return $this->primaryFieldName;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestFieldName()
    {
        return $this->requestFieldName;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigData()
    {
        return $this->data['config'] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function setConfigData($config)
    {
        $this->data['config'] = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getMeta()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getFieldMetaInfo($fieldSetName, $fieldName)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getFieldSetMetaInfo($fieldSetName)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getFieldsMetaInfo($fieldSetName)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        $companyId = $this->reportContext->getCustomerCompanyId();
        if (!$companyId || $companyId === 'all') {
            $companyId = '';
        }

        // Form::getDataSourceData() looks up $data[$id]; $id is always null here (requestFieldName is
        // never present), so key on '' explicitly rather than writing a literal null key.
        return ['' => ['company_id' => $companyId]];
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @return void
     */
    public function addFilter(Filter $filter)
    {
        // Nothing to filter: this data provider always supplies a single, non-collection record.
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @return void
     */
    public function addOrder($field, $direction)
    {
        // Nothing to order: this data provider always supplies a single, non-collection record.
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setLimit($offset, $size)
    {
        // Nothing to limit: this data provider always supplies a single, non-collection record.
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchCriteria()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchResult()
    {
        return null;
    }
}
