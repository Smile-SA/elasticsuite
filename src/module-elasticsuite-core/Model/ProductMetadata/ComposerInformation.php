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

namespace Smile\ElasticsuiteCore\Model\ProductMetadata;

use Composer\Package\CompletePackageInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Composer\ComposerFactory;
use Smile\ElasticsuiteCore\Helper\Cache;

/**
 * Composer Information model.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ComposerInformation extends \Magento\Framework\Composer\ComposerInformation
{
    /**
     * Elasticsuite packages list cache key
     */
    const PACKAGES_CACHE_KEY = 'elasticsuite-packages';

    /**
     * @var \Composer\Composer
     */
    private $composer;

    /**
     * @var \Composer\Package\Locker
     */
    private $locker;

    /**
     * @var ComposerFactory
     */
    private $composerFactory;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var array
     */
    private $packages;

    /**
     * Constructor.
     *
     * @param ComposerFactory $composerFactory Composer Factory
     * @param Cache           $cache           Elasticsuite cache helper
     */
    public function __construct(
        ComposerFactory $composerFactory,
        Cache $cache
    ) {
        parent::__construct($composerFactory);

        $this->composerFactory = $composerFactory;
        $this->cache = $cache;
    }

    /**
     * Collect all system packages from composer.lock
     *
     * @return array
     */
    public function getSystemPackages()
    {
        if (null === $this->packages) {
            $packages = $this->cache->loadCache(self::PACKAGES_CACHE_KEY);
            if (!is_array($packages)) {
                $packages = [];
                /** @var CompletePackageInterface $package */
                foreach ($this->getLocker()->getLockedRepository()->getPackages() as $package) {
                    if ($this->isSystemPackage($package->getName())) {
                        $packages[$package->getName()] = [
                            'name'    => $package->getName(),
                            'type'    => $package->getType(),
                            'version' => $package->getPrettyVersion(),
                        ];
                    }
                }
                $this->cache->saveCache(self::PACKAGES_CACHE_KEY, $packages, [Config::CACHE_TAG]);
            }

            $this->packages = $packages;
        }

        return $this->packages;
    }

    /**
     * Checks if the passed packaged is system package
     *
     * @param string $packageName Package name
     *
     * @return bool
     */
    public function isSystemPackage($packageName = '')
    {
        if (preg_match('/smile\/(module-)?elasticsuite/', $packageName) == 1) {
            return true;
        }

        return false;
    }

    /**
     * Load composerFactory
     *
     * @return ComposerFactory
     */
    private function getComposerFactory()
    {
        return $this->composerFactory;
    }

    /**
     * Load composer
     *
     * @return \Composer\Composer
     */
    private function getComposer()
    {
        if (!$this->composer) {
            $this->composer = $this->getComposerFactory()->create();
        }

        return $this->composer;
    }

    /**
     * Load locker
     *
     * @return \Composer\Package\Locker
     */
    private function getLocker()
    {
        if (!$this->locker) {
            $this->locker = $this->getComposer()->getLocker();
        }

        return $this->locker;
    }
}
