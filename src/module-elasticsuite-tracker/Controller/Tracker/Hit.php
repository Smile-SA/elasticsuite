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

namespace Smile\ElasticsuiteTracker\Controller\Tracker;

/**
 * Hit event collector.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Hit extends \Magento\Framework\App\Action\Action
{
    /**
     * @var string
     */
    const PIXEL = '6wzwc+flkuJiYGDg9fRwCQLSjCDMwQQkJ5QH3wNSbCVBfsEMYJC3jH0ikOLxdHEMqZiTnJCQAOSxMDB+E7cIBcl7uvq5rHNKaAIA';

    /**
     * @var \Smile\ElasticsuiteTracker\Api\CustomerTrackingServiceInterface
     */
    private $trackingService;

    /**
     * Constructor.
     *
     * @param \Magento\Framework\App\Action\Context                           $context         Context.
     * @param \Smile\ElasticsuiteTracker\Api\CustomerTrackingServiceInterface $trackingService Tracking Service.
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Smile\ElasticsuiteTracker\Api\CustomerTrackingServiceInterface $trackingService
    ) {
        parent::__construct($context);
        $this->trackingService = $trackingService;
    }

    /**
     * {@inheritDoc}
     *
     * @return void
     */
    public function execute()
    {
        $this->getResponse()->setBody(base64_decode(self::PIXEL));
        $this->getResponse()->setHeader('Content-Type', 'image/png');
        $this->getResponse()->sendResponse();

        $eventData = $this->decodeParams($this->getRequest()->getParams());

        $this->trackingService->addEvent($eventData);
    }

    /**
     * Decode URI params.
     *
     * @param mixed $params Params.
     *
     * @return mixed
     */
    private function decodeParams($params)
    {
        if (is_string($params)) {
            $params = urldecode($params);
        } elseif (is_array($params)) {
            foreach ($params as &$currentParam) {
                $currentParam = $this->decodeParams($currentParam);
            }
        }

        return $params;
    }
}
