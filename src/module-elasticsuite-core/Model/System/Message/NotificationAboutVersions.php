<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2022 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Model\System\Message;

use Magento\Analytics\Model\SubscriptionStatusProvider;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;
use Smile\ElasticsuiteCore\Model\ProductMetadata;

/**
 * ElasticSuite Notification
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class NotificationAboutVersions implements MessageInterface
{
    /**
     * @var \Smile\ElasticsuiteCore\Model\ProductMetadata
     */
    private $productMetadata;

    /**
     * @param ProductMetadata $productMetadata Elasticsuite metadata (versions).
     */
    public function __construct(\Smile\ElasticsuiteCore\Model\ProductMetadata $productMetadata)
    {
        $this->productMetadata = $productMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentity()
    {
        return hash('sha256', 'ELASTICSUITE_NOTIFICATION');
    }

    /**
     * {@inheritdoc}
     */
    public function isDisplayed()
    {
        return ($this->productMetadata->getEdition() === ProductMetadata::EDITION_NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function getText()
    {
        $messageDetails = '';

        // @codingStandardsIgnoreStart
        $messageDetails .= __('You are using ElasticSuite Open Source Edition as your search engine.') . '<br/>';
        $messageDetails .= __('Do you know that you could do <strong>behavioral merchandising, bestseller boosts, automated recommendations, A/B testing</strong> and even more with our enhanced edition ?') . '<br/>';
        $messageDetails .= __('Elastic Suite Premium Edition brings carefully crafted features that will turbo charge search results and bring light on carefully merchandised products.') . '<br/>';
        $messageDetails .= __('Check all our features on ');
        $messageDetails .= '<strong><a href="' . 'https://elasticsuite.io/elastic-suite-features/' . '">' . __('the ElasticSuite Website.') . '</a></strong>';
        // @codingStandardsIgnoreEnd

        return $messageDetails;
    }

    /**
     * {@inheritdoc}
     */
    public function getSeverity()
    {
        return self::SEVERITY_NOTICE;
    }
}
