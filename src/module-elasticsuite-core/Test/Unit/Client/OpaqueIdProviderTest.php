<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Test\Unit\Client;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCore\Client\OpaqueIdProvider;

/**
 * X-Opaque-Id provider unit tests.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class OpaqueIdProviderTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    /**
     * @var State|MockObject
     */
    private $appState;

    /**
     * @var HttpRequest|MockObject
     */
    private $request;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->scopeConfig  = $this->createMock(ScopeConfigInterface::class);
        $this->appState     = $this->createMock(State::class);
        $this->request      = $this->createMock(HttpRequest::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);

        $this->scopeConfig->method('isSetFlag')
            ->with(OpaqueIdProvider::ENABLED_CONFIG_XML_PATH)
            ->willReturn(true);
    }

    /**
     * Test that no opaque id is provided when the feature is disabled.
     *
     * @return void
     */
    public function testDisabled(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('isSetFlag')->willReturn(false);
        $provider = new OpaqueIdProvider($scopeConfig, $this->appState, $this->request, $this->storeManager, 'cli');

        $this->assertNull($provider->getOpaqueId(['cnt' => 'catalog_view_container']));
    }

    /**
     * Test the opaque id of a routed web request, with additional details.
     *
     * @return void
     */
    public function testWebRequest(): void
    {
        $this->appState->method('getAreaCode')->willReturn('frontend');
        $this->request->method('getRouteName')->willReturn('catalog');
        $this->request->method('getFullActionName')->willReturn('catalog_category_view');
        $this->request->expects($this->never())->method('getServer');
        $this->mockStoreCode('de');

        $provider = $this->getProvider('fpm-fcgi');

        $this->assertEquals(
            'area=frontend;act=catalog_category_view;str=de;cnt=catalog_view_container;cat=4',
            $provider->getOpaqueId(['cnt' => 'catalog_view_container', 'cat' => 4])
        );
    }

    /**
     * Test that the action is omitted when the web request has not been routed (yet).
     *
     * @return void
     */
    public function testUnroutedWebRequest(): void
    {
        $this->appState->method('getAreaCode')->willReturn('graphql');
        $this->request->method('getRouteName')->willReturn(null);
        $this->request->expects($this->never())->method('getFullActionName');
        $this->mockStoreCode('default');

        $this->assertEquals('area=graphql;str=default', $this->getProvider('fpm-fcgi')->getOpaqueId());
    }

    /**
     * Test the opaque id of a CLI command run without any area, and with options before the command name.
     *
     * @dataProvider cliCommandDataProvider
     *
     * @param array       $argv     Command line arguments.
     * @param string|null $expected Expected opaque id.
     *
     * @return void
     */
    public function testCliCommand(array $argv, ?string $expected): void
    {
        $this->appState->method('getAreaCode')
            ->willThrowException(new LocalizedException(new Phrase('Area code is not set')));
        $this->request->method('getServer')->with('argv')->willReturn($argv);
        $this->request->expects($this->never())->method('getFullActionName');
        $this->storeManager->expects($this->never())->method('getStore');

        $this->assertSame($expected, $this->getProvider('cli')->getOpaqueId());
    }

    /**
     * Data provider for testCliCommand.
     *
     * @return array
     */
    public function cliCommandDataProvider(): array
    {
        return [
            [['bin/magento', 'indexer:reindex', 'catalogsearch_fulltext'], 'cli=indexer:reindex'],
            [['bin/magento', '-vvv', 'indexer:reindex'], 'cli=indexer:reindex'],
            [['bin/magento'], null],
        ];
    }

    /**
     * Test that the running cron job is part of the opaque id, until unset.
     *
     * @return void
     */
    public function testCronJob(): void
    {
        $this->appState->method('getAreaCode')->willReturn('crontab');
        $this->request->method('getServer')->with('argv')->willReturn(['bin/magento', 'cron:run']);

        $provider = $this->getProvider('cli');
        $provider->setCronJob('index/indexer_reindex_all_invalid');
        $this->assertEquals(
            'area=crontab;cli=cron:run;cron=index/indexer_reindex_all_invalid',
            $provider->getOpaqueId()
        );

        $provider->setCronJob(null);
        $this->assertEquals('area=crontab;cli=cron:run', $provider->getOpaqueId());
    }

    /**
     * Test that in CLI, the store is only the one given by the caller, at its usual position.
     *
     * @return void
     */
    public function testCliCallerStore(): void
    {
        $this->appState->method('getAreaCode')->willReturn('adminhtml');
        $this->request->method('getServer')->with('argv')->willReturn(['bin/magento', 'indexer:reindex']);
        $this->storeManager->expects($this->never())->method('getStore');

        $this->assertEquals(
            'area=adminhtml;cli=indexer:reindex;str=de;cnt=catalog_view_container',
            $this->getProvider('cli')->getOpaqueId(['cnt' => 'catalog_view_container', 'str' => 'de'])
        );
    }

    /**
     * Test that an unavailable store is omitted.
     *
     * @return void
     */
    public function testUnavailableStore(): void
    {
        $this->appState->method('getAreaCode')->willReturn('graphql');
        $this->request->method('getRouteName')->willReturn(null);
        $this->storeManager->method('getStore')->willThrowException(new NoSuchEntityException());

        $this->assertEquals('area=graphql', $this->getProvider('fpm-fcgi')->getOpaqueId());
    }

    /**
     * Test that unsafe characters are replaced in every value and that the opaque id is truncated.
     *
     * @return void
     */
    public function testSanitizedAndTruncated(): void
    {
        $this->appState->method('getAreaCode')->willReturn('frontend');
        $this->request->method('getRouteName')->willReturn('catalog');
        $this->request->method('getFullActionName')->willReturn("catalog_category_view\r\nX-Injected: 1");
        $this->mockStoreCode('default');

        $provider = $this->getProvider('fpm-fcgi');

        $this->assertEquals(
            'area=frontend;act=catalog_category_view__X-Injected:_1;str=default;cnt=a_b_c_d',
            $provider->getOpaqueId(['cnt' => 'a=b;c d'])
        );

        $opaqueId = $provider->getOpaqueId(['cnt' => str_repeat('x', 500)]);
        $this->assertEquals(OpaqueIdProvider::MAX_LENGTH, strlen($opaqueId));
    }

    /**
     * Return the tested provider.
     *
     * @param string $sapi PHP SAPI name.
     *
     * @return OpaqueIdProvider
     */
    private function getProvider(string $sapi): OpaqueIdProvider
    {
        return new OpaqueIdProvider($this->scopeConfig, $this->appState, $this->request, $this->storeManager, $sapi);
    }

    /**
     * Make the store manager return a store with the given code.
     *
     * @param string $code Store code.
     *
     * @return void
     */
    private function mockStoreCode(string $code): void
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('getCode')->willReturn($code);
        $this->storeManager->method('getStore')->willReturn($store);
    }
}
