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
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Composer\ComposerFactory;

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
     * @param \Magento\Framework\Composer\ComposerFactory $composerFactory Composer Factory
     */
    public function __construct(ComposerFactory $composerFactory)
    {
        parent::__construct($composerFactory);

        $this->composerFactory = $composerFactory;
    }

    /**
     * Collect all system packages from composer.lock
     *
     * @return array
     */
    public function getSystemPackages()
    {
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

        return $packages;
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
        if (preg_match('/smile\/elasticsuite/', $packageName) == 1) {
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
