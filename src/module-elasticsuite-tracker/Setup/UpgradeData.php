<?php

namespace Yotpo\Yotpo\Setup;

use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\Notification\NotifierInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Store\Model\ScopeInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Yotpo\Yotpo\Model\Config as YotpoConfig;

/**
 * Upgrade Data script
 *
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var YotpoConfig
     */
    private $yotpoConfig;

    /**
     * @var NotifierInterface
     */
    private $notifierPool;

    /**
     * @var ConsoleOutput
     */
    private $output;

    /**
     * @method __construct
     * @param  ReinitableConfigInterface $appConfig
     * @param  YotpoConfig               $yotpoConfig
     * @param  NotifierInterface         $notifierPool
     * @param  ConsoleOutput             $output
     */
    public function __construct(
        YotpoConfig $yotpoConfig,
        NotifierInterface $notifierPool,
        ConsoleOutput $output
    ) {
        $this->yotpoConfig = $yotpoConfig;
        $this->notifierPool = $notifierPool;
        $this->output = $output;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if ($context->getVersion() && version_compare($context->getVersion(), '1.2.1') < 0) {
            $this->notifierPool->addNotice(
                $this->getNoticeTitle(),
                $this->getNoticeDescription()
            );
        }

        $setup->endSetup();
    }

    /**
     * Get notice title.
     *
     * @return string
     */
    private function getNoticeTitle(): string
    {
        return 'Help us improve Elasticsuite';
    }

    /**
     * Get notice title.
     *
     * @return string
     */
    private function getNoticeDescription(): string
    {
        return 'Help us improve Elasticsuite';
    }
}
