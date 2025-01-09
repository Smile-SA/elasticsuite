<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Request\Http;

/**
 * BotDetector helper.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 */
class BotDetector extends AbstractHelper
{
    /**
     * @var Http
     */
    protected $request;

    /**
     * @var array
     */
    protected $botUserAgents = [];

    /**
     * Constructor.
     *
     * @param Context $context       Context.
     * @param Http    $request       HTTP request.
     * @param array   $botUserAgents Bot user agents list.
     */
    public function __construct(Context $context, Http $request, $botUserAgents = [])
    {
        parent::__construct($context);
        $this->request = $request;
        $this->botUserAgents = $botUserAgents;
    }

    /**
     * Get the current user agent from the request
     *
     * @return string
     */
    public function getUserAgent()
    {
        return $this->request->getHeader('User-Agent') ?: '';
    }

    /**
     * Check if the current user agent belongs to a bot
     *
     * @return bool
     */
    public function isBot()
    {
        $userAgent = strtolower($this->getUserAgent());
        foreach ($this->botUserAgents as $bot) {
            if (strpos($userAgent, $bot) !== false) {
                return true;
            }
        }

        return false;
    }
}
