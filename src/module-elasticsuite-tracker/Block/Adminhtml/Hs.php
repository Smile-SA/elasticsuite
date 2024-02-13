<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteTracker\Block\Adminhtml;

/**
 * HS Form block.
 *
 * @SuppressWarnings(PHPMD.ShortClassName)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Hs extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    private \Magento\Backend\Model\Auth\Session $authSession;

    /**
     * Block for HS form.
     *
     * @param \Magento\Backend\Block\Template\Context $context     Adminhtml context
     * @param \Magento\Backend\Model\Auth\Session     $authSession Adminhtml session
     * @param array                                   $data        Block data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        array $data = []
    ) {
        $this->authSession = $authSession;
        parent::__construct($context, $data);
    }

    /**
     * Get current user email.
     *
     * @return string
     */
    public function getEmail()
    {
        return (string) $this->authSession->getUser()->getEmail();
    }
}
