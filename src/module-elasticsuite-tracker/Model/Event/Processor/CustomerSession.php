<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Searchandising Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Model\Event\Processor;

use Smile\ElasticsuiteTracker\Api\EventProcessorInterface;

/**
 * Add the customer id to the logged events.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class CustomerSession implements EventProcessorInterface
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * Constructor.
     *
     * @param \Magento\Customer\Model\Session $customerSession Customer session.
     */
    public function __construct(\Magento\Customer\Model\Session $customerSession)
    {
        $this->customerSession = $customerSession;
    }

    /**
     * {@inheritDoc}
     */
    public function process($eventData)
    {
        if ($this->customerSession->getCustomerId() !== null) {
            $eventData['session']['customer_id'] = $this->customerSession->getCustomerId();
        }

        return $eventData;
    }
}
