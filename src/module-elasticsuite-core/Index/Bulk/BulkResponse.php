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
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Index\Bulk;

use Smile\ElasticsuiteCore\Api\Index\Bulk\BulkResponseInterface;

/**
 * Default implementation for ES bulk (Smile\ElasticsuiteCore\Api\Index\BulkInterface).
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class BulkResponse implements BulkResponseInterface
{
    /**
     * @var array
     */
    private $rawResponse;

    /**
     * Constructor.
     *
     * @param array $rawResponse ES raw response.
     */
    public function __construct(array $rawResponse)
    {
        $this->rawResponse = $rawResponse;
    }

    /**
     * {@inheritDoc}
     */
    public function hasErrors()
    {
        return (bool) $this->rawResponse['errors'];
    }

    /**
     * {@inheritDoc}
     */
    public function getErrorItems()
    {
        $errors = array_filter($this->rawResponse['items'], function ($item) {
            return isset(current($item)['error']);
        });

        return $errors;
    }

    /**
     * {@inheritDoc}
     */
    public function getSuccessItems()
    {
        $successes = array_filter($this->rawResponse['items'], function ($item) {
            return !isset(current($item)['error']);
        });

        return $successes;
    }

    /**
     * {@inheritDoc}
     */
    public function countErrors()
    {
        return count($this->getErrorItems());
    }

    /**
     * {@inheritDoc}
     */
    public function countSuccess()
    {
        return count($this->getSuccessItems());
    }

    /**
     * {@inheritDoc}
     */
    public function aggregateErrorsByReason()
    {
        $errorByReason = [];

        foreach ($this->getErrorItems() as $item) {
            $operationType = current(array_keys($item));
            $itemData      = $item[$operationType];
            $index         = $itemData['_index'];
            $documentType  = $itemData['_type'] ?? '_doc';
            $errorData     = $itemData['error'];
            $errorKey      = $operationType . $errorData['type'] . $errorData['reason'] . $index . $documentType;

            if (!isset($errorByReason[$errorKey])) {
                $errorByReason[$errorKey] = [
                    'index'         => $itemData['_index'],
                    'document_type' => $itemData['_type'] ?? '_doc',
                    'operation'     => $operationType,
                    'error'         => ['type' => $errorData['type'], 'reason' => $errorData['reason']],
                    'count'         => 0,
                ];
            }

            $errorByReason[$errorKey]['count']++;
            $errorByReason[$errorKey]['document_ids'][] = $itemData['_id'];
        }

        return array_values($errorByReason);
    }
}
