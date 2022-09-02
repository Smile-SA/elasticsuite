<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Setup;

use Magento\Search\Model\SearchEngine\ValidatorInterface;
use Smile\ElasticsuiteCore\Api\Client\ClientConfigurationInterface;
use Smile\ElasticsuiteCore\Api\Client\ClientInterface;

/**
 * Elasticsuite configuration validator
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Validator implements ValidatorInterface
{
    /**
     * Validator constructor.
     *
     * @param \Smile\ElasticsuiteCore\Api\Client\ClientInterface $client ES Client (injected as proxy in DI).
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritDoc}
     */
    public function validate(): array
    {
        $errors = [];

        try {
            $this->client->info();
        } catch (\Exception $e) {
            $errors[] = "ElasticSuite : Unable to validate connection to Elasticsearch server : {$e->getMessage()}";
        }

        return $errors;
    }
}
