<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteIndices
 * @author    Dmytro ANDROSHCHUK <dmand@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteIndices\Model;

use Smile\ElasticsuiteCore\Api\Client\ClientInterface;

/**
 * Class IndexSettingsProvider
 *
 * @category Smile
 * @package  Smile\ElasticsuiteIndices
 * @author   Dmytro ANDROSHCHUK <dmand@smile.fr>
 */
class IndexSettingsProvider
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * Constructor.
     *
     * @param ClientInterface $client ES client.
     */
    public function __construct(
        ClientInterface $client
    ) {
        $this->client = $client;
    }

    /**
     * Get a mapping of index.
     *
     * @param string $indexName Index name.
     * @return array
     */
    public function getSettings($indexName): array
    {
        return $this->client->getSettings($indexName);
    }
}
