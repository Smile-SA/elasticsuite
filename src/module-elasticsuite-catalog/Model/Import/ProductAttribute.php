<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2023 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Import;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Model\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\ImportExport\Helper\Data as ImportHelper;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\ResourceModel\Helper;
use Magento\ImportExport\Model\ResourceModel\Import\Data;

/**
 * Product attribute import model.
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.ElseExpression)
 *
 * @category Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 */
class ProductAttribute extends AbstractEntity
{
    /**
     * Entity type code.
     */
    const ENTITY_TYPE_CODE = 'elasticsuite_product_attribute';

    /**
     * Permanent entity columns.
     *
     * @var array
     */
    protected $_permanentAttributes = [
        'attribute_code',
        'attribute_label',
    ];

    /**
     * Valid column names.
     *
     * @var array
     */
    protected $validColumnNames = [
        'attribute_code',
        'attribute_label',
        'is_searchable',
        'search_weight',
        'is_used_in_spellcheck',
        'is_displayed_in_autocomplete',
        'is_filterable',
        'is_filterable_in_search',
        'is_used_for_promo_rules',
        'used_for_sort_by',
        'is_display_rel_nofollow',
        'facet_max_size',
        'facet_sort_order',
        'facet_min_coverage_rate',
        'facet_boolean_logic',
        'position',
        'default_analyzer',
        'norms_disabled',
        'is_spannable',
        'include_zero_false_values',
    ];

    /**
     * Count if updated items.
     *
     * @var integer
     */
    protected $countItemsUpdated = 0;

    /**
     * Need to log in import history.
     *
     * @var boolean
     */
    protected $logInHistory = true;

    /**
     * @var Config
     */
    private $_eavConfig;

    /**
     * Import constructor.
     *
     * @param JsonHelper                         $jsonHelper       Json Helper.
     * @param ImportHelper                       $importExportData Import Helper.
     * @param Data                               $importData       Import Data.
     * @param Config                             $eavConfig        EAV Config.
     * @param Helper                             $resourceHelper   Resource Helper.
     * @param ProcessingErrorAggregatorInterface $errorAggregator  Error Aggregator.
     */
    public function __construct(
        JsonHelper $jsonHelper,
        ImportHelper $importExportData,
        Data $importData,
        Config $eavConfig,
        Helper $resourceHelper,
        ProcessingErrorAggregatorInterface $errorAggregator
    ) {
        $this->jsonHelper          = $jsonHelper;
        $this->_importExportData   = $importExportData;
        $this->_dataSourceModel    = $importData;
        $this->_eavConfig          = $eavConfig;
        $this->_resourceHelper     = $resourceHelper;
        $this->errorAggregator     = $errorAggregator;
    }

    /**
     * Entity type code getter.
     *
     * @return string
     */
    public function getEntityTypeCode(): string
    {
        return self::ENTITY_TYPE_CODE;
    }

    /**
     * Get available columns.
     *
     * @return array
     */
    public function getValidColumnNames(): array
    {
        return $this->validColumnNames;
    }

    /**
     * Validate data row.
     *
     * @param array $rowData Data.
     * @param int   $rowNum  Row number.
     *
     * @return bool
     * @throws LocalizedException
     */
    public function validateRow(array $rowData, $rowNum)
    {
        $errors = [];

        // Validate if attribute exists.
        $attributeCode = isset($rowData['attribute_code']) ? trim($rowData['attribute_code']) : '';
        if (!$attributeCode) {
            $errors[] = __('Attribute code is required.');
        } else {
            $attribute = $this->_eavConfig->getAttribute('catalog_product', $attributeCode);
            if (!$attribute->getId()) {
                $errors[] = __('Attribute with code %1 does not exist.', $attributeCode);
            }
        }

        // Check if all the required columns are present.
        foreach ($this->validColumnNames as $columnName) {
            if (!isset($rowData[$columnName])) {
                $errors[] = __('Column %1 is missing.', $columnName);
            }
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addRowError($error, $rowNum);
            }

            return false;
        }

        return true;
    }

    /**
     * Import data rows.
     *
     * @return bool
     * @throws LocalizedException
     */
    protected function _importData()
    {
        // Add import logic.
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            foreach ($bunch as $rowData) {
                $attributeCode = isset($rowData['attribute_code']) ? trim($rowData['attribute_code']) : '';
                $attribute = $this->_eavConfig->getAttribute('catalog_product', $attributeCode);
                $result = $this->updateAttributeData($attribute, $rowData);
                if ($result) {
                    $this->countItemsUpdated++;
                }
            }
        }

        return true;
    }

    /**
     * Update attribute data with new values from CSV.
     *
     * @param Attribute $attribute Attribute.
     * @param array     $rowData   Row Data.
     * @return bool
     */
    private function updateAttributeData($attribute, $rowData)
    {
        $dataChanged = false;

        foreach ($rowData as $key => $value) {
            // Skip permanent attributes.
            if (in_array($key, $this->_permanentAttributes)) {
                continue;
            }

            // Skip empty values.
            if (!isset($value) || $value === '') {
                continue;
            }

            // Update attribute data if new value is different from current value.
            if ($attribute->getData($key) != $value) {
                $attribute->setData($key, $value);
                $dataChanged = true;
            }
        }

        // Check if attribute data has changed.
        if ($dataChanged) {
            try {
                $attribute->save();
            } catch (\Exception $e) {
                $this->_errors[] = __(
                    'Row with attribute_code "%1" cannot be updated. Error: %2',
                    $attribute->getAttributeCode(),
                    $e->getMessage()
                );

                return false;
            }
        }

        return true;
    }
}
