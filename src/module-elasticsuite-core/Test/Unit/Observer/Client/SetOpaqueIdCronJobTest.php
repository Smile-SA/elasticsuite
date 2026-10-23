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

namespace Smile\ElasticsuiteCore\Test\Unit\Observer\Client;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCore\Api\Client\OpaqueIdProviderInterface;
use Smile\ElasticsuiteCore\Observer\Client\SetOpaqueIdCronJob;

/**
 * Cron job X-Opaque-Id observer unit tests.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class SetOpaqueIdCronJobTest extends TestCase
{
    /**
     * Test the cron job code given to the X-Opaque-Id provider.
     *
     * @dataProvider jobNameDataProvider
     *
     * @param string|null $jobName  Event job name.
     * @param string|null $expected Expected cron job code.
     *
     * @return void
     */
    public function testExecute(?string $jobName, ?string $expected): void
    {
        $provider = $this->createMock(OpaqueIdProviderInterface::class);
        $provider->expects($this->once())->method('setCronJob')->with($expected);

        $observer = new Observer(['event' => new Event(['job_name' => $jobName])]);
        (new SetOpaqueIdCronJob($provider))->execute($observer);
    }

    /**
     * Data provider for testExecute.
     *
     * @return array
     */
    public function jobNameDataProvider(): array
    {
        return [
            ['cron/index/indexer_reindex_all_invalid', 'index/indexer_reindex_all_invalid'],
            ['cron/default/elasticsuite_index_log_event', 'default/elasticsuite_index_log_event'],
            ['index/indexer_update_all_views', 'index/indexer_update_all_views'],
            [null, null],
        ];
    }
}
