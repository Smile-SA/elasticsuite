<?php
/**
 * Client factory test case.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile_ElasticSuite
 * @package   Smile_ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCore\Test\Unit\Client;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Smile\ElasticSuiteCore\Test\Unit\ClientConfiguration;
use Psr\Log\NullLogger as Logger;

/**
 * Client factory test case.
 *
 * @category  Smile_ElasticSuite
 * @package   Smile_ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class ClientFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Smile\ElasticSuiteCore\Api\Client\ClientFactoryInterface
     */
    private $clientFactory;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->clientFactory = $objectManager->getObject(
            'Smile\ElasticSuiteCore\Client\ClientFactory',
            [new ClientConfiguration(), new Logger()]
        );
    }

    /**
     * Test for client factory return type.
     *
     * @return void
     */
    public function testReturnType()
    {
        $client = $this->clientFactory->createClient();
        $this->assertInstanceOf('\Elasticsearch\Client', $client);
    }
}
