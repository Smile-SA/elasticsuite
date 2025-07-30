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

namespace Smile\ElasticsuiteCore\Model\ResourceModel\Index;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Bulk error ressource model.
 */
class BulkError extends AbstractDb
{
    /**
     * Record error in database.
     *
     * @param string $storeCode       Store code.
     * @param string $indexIdentifier Index identifier.
     * @param string $operationType   Operation type.
     * @param string $errorType       Error type.
     * @param string $simpleReason    Simplified version of the error reason, with no record-specific data.
     * @param string $reason          Error reason.
     * @param int    $count           Number of times that error has been seen.
     * @param string $sampleIds       Error sample document ids.
     * @return void
     *
     * @throws LocalizedException
     */
    public function recordError(
        string $storeCode,
        string $indexIdentifier,
        string $operationType,
        string $errorType,
        string $simpleReason,
        string $reason,
        int $count,
        string $sampleIds
    ): void {
        $connection = $this->getConnection();
        $table = $this->getMainTable();
        $textLimit = 255;

        $connection->insertOnDuplicate(
            $table,
            [
                'store_code'       => $storeCode,
                'index_identifier' => $indexIdentifier,
                'operation'        => $operationType,
                'error_type'       => $errorType,
                'reason_simple'    => mb_substr($simpleReason, 0, $textLimit),
                'reason'           => $reason,
                // phpcs:ignore
                'sample_ids'       => mb_strlen($sampleIds) > $textLimit ?
                    (mb_substr($sampleIds, 0, $textLimit - 3) . '...') : $sampleIds,
                'count'            => $count,
                'created_at'       => new \Zend_Db_Expr('CURRENT_TIMESTAMP'),
                'updated_at'       => new \Zend_Db_Expr('CURRENT_TIMESTAMP'),
            ],
            [
                'count' => new \Zend_Db_Expr("count + {$count}"),
                'sample_ids' => new \Zend_Db_Expr(
                    'IF(CHAR_LENGTH(sample_ids) >= ' . $textLimit . ',' .
                    ' sample_ids,' .
                    ' IF(CHAR_LENGTH(CONCAT(sample_ids, ",", VALUES(sample_ids))) > ' . $textLimit . ',' .
                    '     CONCAT(LEFT(CONCAT(sample_ids, ",", VALUES(sample_ids)), ' . ($textLimit - 3) . '), "..."),' .
                    '     CONCAT(sample_ids, ",", VALUES(sample_ids))' .
                    ' )' .
                    ')'
                ),
                'updated_at' => new \Zend_Db_Expr('CURRENT_TIMESTAMP'),
            ]
        );
    }

    /**
     * Remove all errors in database for given store code and index identifier.
     *
     * @param string $storeCode       Store code.
     * @param string $indexIdentifier Index identifier.
     * @return void
     *
     * @throws LocalizedException
     */
    public function cleanBulkErrors(string $storeCode, string $indexIdentifier): void
    {
        $connection = $this->getConnection();
        $connection->delete(
            $this->getMainTable(),
            [
                'store_code = ?' => $storeCode,
                'index_identifier = ?' => $indexIdentifier,
            ]
        );
    }

    /**
     * Resource model constructor.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _construct()
    {
        $this->_init('smile_elasticsuite_index_bulk_error', 'entity_id');
    }
}
