<?php
/**
 * ElasticSearch client factory interface.
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
 * Provides a simple way to retrieve an ElasticSearch client.
 *
 * @api
 */
interface ClientFactoryInterface {

    /**
     * Create an ES client
     *
     * @return Elasticsearch\Client
     */
    public function createClient();

}