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

namespace Smile\ElasticsuiteCore\Api\Client;

/**
 * This interface provides the search engine configuration params.
 *
 * @category Smile
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
     * Indicates whether the HTTP Authorization header should be base64 encoded or not.
     *
     * @return boolean
     */
    public function isHttpAuthEncodingEnabled();

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

    /**
     * Get the maximum number of HTTP curl requests that the client can parallelize
     *
     * @return int
     */
    public function getMaxParallelHandles();

    /**
     * Get the maximum number of HTTP curl request retries the client will make
     *
     * @return int
     */
    public function getMaxRetries();

    /**
     * Check if SSL verification is enabled.
     *
     * @return bool
     */
    public function isVerifyEnabled();

    /**
     * Check if AWS Sig4 verification is enabled.
     *
     * @return bool
     */
    public function isAwsSig4Enabled();

    /**
     * Fetch the AWS Service to be used
     *
     * @return string
     */
    public function getAwsService();

    /**
     * Get the AWS Region
     *
     * @return string
     */
    public function getAwsRegion();

    /**
     * Get the AWS Sig4 Key
     *
     * @return string
     */
    public function getAwsSig4Key();

    /**
     * Get the AWS Sig4 Secret
     *
     * @return string
     */
    public function getAwsSig4Secret();

    /**
     * Client config options.
     *
     * @return array
     */
    public function getOptions();
}
