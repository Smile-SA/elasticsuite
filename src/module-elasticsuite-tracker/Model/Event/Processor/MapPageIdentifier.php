<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Model\Event\Processor;

use Smile\ElasticsuiteTracker\Api\EventProcessorInterface;

/**
 * Map page identifier to harmonize event data.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class MapPageIdentifier implements EventProcessorInterface
{
    protected const MAPPING_REPLACE_IDENTIFIER = [
        'multishipping_checkout_success' => 'checkout_onepage_success',
        'multishipping_checkout_billing' => 'checkout_index_index',
        'multishipping_checkout_shipping' => 'checkout_index_index',
    ];

    /**
     * @var array
     */
    private $mapping = [];

    /**
     * @param array $mapping an additional mapping of pages identifiers (coming from the DI)
     */
    public function __construct($mapping = [])
    {
        $this->mapping = array_merge_recursive(self::MAPPING_REPLACE_IDENTIFIER, $mapping);
    }

    /**
     * {@inheritDoc}
     */
    public function process($eventData)
    {
        if (isset($eventData['page']['type']['identifier'])) {
            $pageIdentifier = $eventData['page']['type']['identifier'];
            if (isset($this->mapping[$pageIdentifier])) {
                $eventData['page']['type']['identifier'] = $this->mapping[$pageIdentifier];
            }
        }

        return $eventData;
    }
}
