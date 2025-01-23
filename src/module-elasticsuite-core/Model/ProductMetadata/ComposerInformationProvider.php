<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\Elasticsuite
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Model\ProductMetadata;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Composer\ComposerFactory;
use Magento\Framework\Composer\ComposerJsonFinder;
use Smile\ElasticsuiteCore\Helper\Cache;

/**
 * Composer information model provider.
 * Helps sharing the same properly initialized composer information model (correct composer factory) between multiple models.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 */
class ComposerInformationProvider
{
    /** @var ComposerJsonFinder */
    private $composerJsonFinder;

    /** @var Cache */
    private $cache;

    /** @var ComposerInformation */
    private $composerInformation;

    /**
     * Constructor
     *
     * @param ComposerJsonFinder $composerJsonFinder Composer JSON finder
     * @param Cache              $cache              Elasticsuite cache helper
     */
    public function __construct(
        ComposerJsonFinder $composerJsonFinder,
        Cache $cache
    ) {
        $this->composerJsonFinder = $composerJsonFinder;
        $this->cache = $cache;
    }

    /**
     * Return a properly initialized Elasticsuite Composer Information model.
     *
     * @return ComposerInformation
     */
    public function getComposerInformation(): ComposerInformation
    {
        if (null === $this->composerInformation) {
            $this->composerInformation = new ComposerInformation(
                new ComposerFactory(new DirectoryList(BP), $this->composerJsonFinder),
                $this->cache
            );
        }

        return $this->composerInformation;
    }
}
