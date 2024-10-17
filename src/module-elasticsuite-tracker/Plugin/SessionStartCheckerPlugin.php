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
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Plugin;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Session\SessionStartChecker;

/**
 * Prevent session creation when going through a tracker hit URL.
 * Session creation can have performance issues when several ajax calls are sent in parallel.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class SessionStartCheckerPlugin
{
    /**
     * @var Http
     */
    private $request;

    /**
     * @param Http $request HTTP Request
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function __construct(
        Http $request
    ) {
        $this->request = $request;
    }

    /**
     * Prevents session starting when going through a tracker hit URL.
     *
     * @param SessionStartChecker $subject Session start checker
     * @param bool                $result  Legacy result
     *
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCheck(SessionStartChecker $subject, bool $result): bool
    {
        if ($result === false) {
            return false;
        }

        $requestPath = trim($this->request->getPathInfo(), '/');

        if ($requestPath === 'elasticsuite/tracker/hit/image/h.png') {
            $result = false;
        } elseif ($requestPath === 'rest/V1/elasticsuite-tracker/hit') {
            $result = false;
        }

        return $result;
    }
}
