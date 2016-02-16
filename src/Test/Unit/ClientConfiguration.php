<?php
/**
 * Configuration mock for ES testing.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile_ElasticSuite
 * @package   Smile\ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Test\Unit;

use Smile\ElasticSuiteCore\Api\Client\ClientConfigurationInterface;

/**
 * Configuration mock for ES testing.
 *
 * @category Smile_ElasticSuite
 * @package  Smile\ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class ClientConfiguration implements ClientConfigurationInterface
{
    /**
     * @var array
     */
    private $serverList = ['localhost:9200'];

    /**
     * @var integer
     */
    private $connectionTimeout = 1;

    /**
     * {@inheritDoc}
     */
    public function getServerList()
    {
        return $this->serverList;
    }

    /**
     * {@inheritDoc}
     */
    public function isDebugModeEnabled()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getConnectionTimeout()
    {
        return $this->connectionTimeout;
    }
}
