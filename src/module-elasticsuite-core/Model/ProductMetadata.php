<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Composer\ComposerFactory;
use \Smile\ElasticsuiteCore\Model\ProductMetadata\ComposerInformation;
use Magento\Framework\Composer\ComposerJsonFinder;

/**
 * Composer Information model.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ProductMetadata
{
    /**
     * Elasticsuite product edition
     */
    const EDITION_NAME  = 'Open Source';

    /**
     * Magento product name
     */
    const PRODUCT_NAME  = 'Elasticsuite';

    /**
     * Magento version cache key
     */
    const VERSION_CACHE_KEY = 'elasticsuite-version';

    /**
     * Product version
     *
     * @var string
     */
    protected $version;

    /**
     * @var \Magento\Framework\Composer\ComposerJsonFinder
     */
    protected $composerJsonFinder;

    /**
     * @var \Smile\ElasticsuiteCore\Model\ProductMetadata\ComposerInformation
     */
    private $composerInformation;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * ProductMetadata constructor.
     *
     * @param ComposerJsonFinder                    $composerJsonFinder Composer JSON finder
     * @param \Magento\Framework\App\CacheInterface $cache              Cache interface
     */
    public function __construct(
        ComposerJsonFinder $composerJsonFinder,
        CacheInterface $cache = null
    ) {
        $this->composerJsonFinder = $composerJsonFinder;
        $this->cache              = $cache ?: ObjectManager::getInstance()->get(CacheInterface::class);
    }

    /**
     * Get Product version
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @return string
     */
    public function getVersion()
    {
        $this->version = $this->version ?: $this->cache->load(self::VERSION_CACHE_KEY);

        if (!$this->version) {
            if (!($this->version = $this->getSystemPackageVersion())) {
                if ($this->getComposerInformation()->isMagentoRoot()) {
                    $this->version = $this->getComposerInformation()->getRootPackage()->getPrettyVersion();
                } else {
                    $this->version = 'UNKNOWN';
                }
            }
            $this->cache->save($this->version, self::VERSION_CACHE_KEY, [Config::CACHE_TAG]);
        }

        return $this->version;
    }

    /**
     * Get Product edition
     *
     * @return string
     */
    public function getEdition()
    {
        return self::EDITION_NAME;
    }

    /**
     * Get Product name
     *
     * @return string
     */
    public function getName()
    {
        return self::PRODUCT_NAME;
    }

    /**
     * Get version from system package
     *
     * @return string
     */
    private function getSystemPackageVersion()
    {
        $packages = $this->getComposerInformation()->getSystemPackages();

        foreach ($packages as $package) {
            if (isset($package['name']) && isset($package['version'])) {
                return $package['version'];
            }
        }

        return '';
    }

    /**
     * Load composerInformation
     *
     * @return ComposerInformation
     */
    private function getComposerInformation()
    {
        if (!$this->composerInformation) {
            $directoryList              = new DirectoryList(BP);
            $composerFactory            = new ComposerFactory($directoryList, $this->composerJsonFinder);
            $this->composerInformation  = new ComposerInformation($composerFactory);
        }

        return $this->composerInformation;
    }
}
