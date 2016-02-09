<?php
/**
 * Smile_ElasticSuiteCore search engine configuration interface.
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
namespace Smile\ElasticSuiteCore\Api\Client;

/**
 * This interface provides the search engine configuration params.
 *
 * @spi
 */
interface ClientConfigurationInterface
{
    /**
     * @return array
     */
    public function getServerList();

    /**
     * @return boolean
     */
    public function isDebugModeEnabled();

    /**
     * @return int
     */
    public function getConnectionTimeout();
}