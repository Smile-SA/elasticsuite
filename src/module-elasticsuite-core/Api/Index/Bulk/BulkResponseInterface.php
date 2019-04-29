<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Api\Index\Bulk;

/**
 * Bulk operation response representation interface.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface BulkResponseInterface
{
    /**
     * Check if the bulk has errors.
     *
     * @return boolean
     */
    public function hasErrors();

    /**
     * Get all items with an errors.
     *
     * @return array
     */
    public function getErrorItems();

    /**
     * Get all successfull items.
     *
     * @return array
     */
    public function getSuccessItems();

    /**
     * Count items with an error.
     *
     * @return integer
     */
    public function countErrors();

    /**
     * Count successfull items.
     *
     * @return integer
     */
    public function countSuccess();

    /**
     * Aggregate all errors by index, document_type, error type and reason.
     * Used to log erros in compact mode.
     *
     * @return array
     */
    public function aggregateErrorsByReason();
}
