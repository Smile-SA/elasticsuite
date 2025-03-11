<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\Elasticsuite
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Model\Event\Processor;

use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Smile\ElasticsuiteTracker\Api\EventProcessorInterface;

/**
 * Event date processor: adds the current date if it is lacking in the event data.
 * Useful to be able to copy it to the order items.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 */
class EventDate implements EventProcessorInterface
{
    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * Constructor.
     *
     * @param DateTimeFactory $dateTimeFactory DateTime factory.
     */
    public function __construct(DateTimeFactory $dateTimeFactory)
    {
        $this->dateTimeFactory = $dateTimeFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function process($eventData)
    {
        if (!isset($eventData['date'])) {
            $eventData['date'] = $this->dateTimeFactory->create()->date();
        }

        return $eventData;
    }
}
