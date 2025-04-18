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
use Magento\Framework\App\ObjectManager;
use Smile\ElasticsuiteCore\Model\ProductMetadata\ComposerInformation;
use Smile\ElasticsuiteCore\Model\ProductMetadata\ComposerInformationProvider;

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
     * Elasticsuite version cache key
     */
    const VERSION_CACHE_KEY = 'elasticsuite-version';

    /**
     * Product version
     *
     * @var string
     */
    protected $version;

    /**
     * @var \Smile\ElasticsuiteCore\Model\ProductMetadata\ComposerInformationProvider
     */
    protected $composerInformationProvider;

    /**
     * @var \Smile\ElasticsuiteCore\Model\ProductMetadata\ComposerInformation
     */
    private $composerInformation;

    /**
     * @var string
     */
    private $packageName;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * ProductMetadata constructor.
     *
     * @param ComposerInformationProvider $composerInformationProvider Composer Information provider
     * @param string                      $packageName                 Self package name
     * @param CacheInterface|null         $cache                       Cache interface
     */
    public function __construct(
        ComposerInformationProvider $composerInformationProvider,
        string $packageName = 'smile/elasticsuite',
        ?CacheInterface $cache = null
    ) {
        $this->composerInformationProvider = $composerInformationProvider;
        $this->packageName = $packageName;
        $this->cache       = $cache ?: ObjectManager::getInstance()->get(CacheInterface::class);
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
            if (isset($package['name']) && ($package['name'] === $this->packageName)) {
                return $package['version'] ?? '';
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
            $this->composerInformation = $this->composerInformationProvider->getComposerInformation();
        }

        return $this->composerInformation;
    }
}
