<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Api\Client;

/**
 * This interface provides the search engine configuration params.
 *
 * @category Smile_Elasticsuite
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface ClientConfigurationInterface
{
    /**
     * Return the list of configured ES Servers (client nodes).
     *
     * @return array
     */
    public function getServerList();

    /**
     * Indicates whether the debug node is enabled or not.
     *
     * @return boolean
     */
    public function isDebugModeEnabled();

    /**
     * Default connect timeout for the ES client.
     *
     * @return integer
     */
    public function getConnectionTimeout();

    /**
     * Indicates the protocol scheme used (http/https).
     *
     * @return string
     */
    public function getScheme();

    /**
     * Indicates whether basic HTTP authentication on the node is enabled or not.
     *
     * @return boolean
     */
    public function isHttpAuthEnabled();

    /**
     * Return the basic HTTP authentication user.
     *
     * @return string
     */
    public function getHttpAuthUser();

    /**
     * Return the basic HTTP authentication password.
     *
     * @return string
     */
    public function getHttpAuthPassword();
}
