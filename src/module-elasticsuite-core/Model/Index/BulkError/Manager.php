<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Pierre Gauthier <pigau@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Model\Index\BulkError;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCore\Helper\IndexSettings as IndexSettingsHelper;
use Smile\ElasticsuiteCore\Model\ResourceModel\Index\BulkError as ResourceModel;

/**
 * Manager class for index bulk error.
 */
class Manager
{
    /** @var string */
    const BULK_ERROR_LOGGING_ENABLED_XML_PATH = 'smile_elasticsuite_core_base_settings/bulk_error_logging/enable';

    /** @var ResourceModel */
    private $resource;

    /** @var IndexSettingsHelper */
    private $indexSettingsHelper;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /**
     * Constructor.
     *
     * @param ResourceModel         $resource            Bulk error ressource model.
     * @param IndexSettingsHelper   $indexSettingsHelper Index settings helper.
     * @param StoreManagerInterface $storeManager        Store manager.
     * @param ScopeConfigInterface  $scopeConfig         Scope config.
     */
    public function __construct(
        ResourceModel $resource,
        IndexSettingsHelper $indexSettingsHelper,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->resource = $resource;
        $this->indexSettingsHelper = $indexSettingsHelper;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Save bulk error in database.
     *
     * @param string $index        Index name.
     * @param string $operation    Operation type.
     * @param string $errorType    Error type.
     * @param string $simpleReason Simplified version of the error reason, with no record-specific data.
     * @param string $reason       Error reason.
     * @param int    $occurrences  Number of times an error with the same reason has been seen.
     * @param string $sampleIds    Error sample document ids.
     *
     * @return void
     *
     * @throws LocalizedException
     */
    public function recordError(
        string $index,
        string $operation,
        string $errorType,
        string $simpleReason,
        string $reason,
        int    $occurrences,
        string $sampleIds
    ): void {
        if ($this->scopeConfig->isSetFlag(self::BULK_ERROR_LOGGING_ENABLED_XML_PATH)) {
            $data = $this->indexSettingsHelper->parseIndexName($index);

            $this->resource->recordError(
                $data['store_code'],
                $data['index_identifier'],
                $operation,
                $errorType,
                $simpleReason,
                $reason,
                $occurrences,
                $sampleIds,
            );
        }
    }

    /**
     * Clean bulk error for given store and index identifier.
     *
     * @param integer|string|StoreInterface $store           Store (id, identifier or object).
     * @param string                        $indexIdentifier Index identifier.
     *
     * @return void
     *
     * @throws LocalizedException
     */
    public function cleanBulkErrors($store, string $indexIdentifier): void
    {
        try {
            $storeCode = $this->storeManager->getStore($store)->getCode();
        } catch (NoSuchEntityException $e) {
            $storeCode = null;
        }

        if ($storeCode !== null) {
            $this->resource->cleanBulkErrors($storeCode, $indexIdentifier);
        }
    }
}
