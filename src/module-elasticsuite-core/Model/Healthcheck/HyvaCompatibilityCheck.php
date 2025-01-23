<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Model\Healthcheck;

use Smile\ElasticsuiteCore\Api\Healthcheck\CheckInterface;
use Smile\ElasticsuiteCore\Model\ProductMetadata\ComposerInformationProvider;

/**
 * Presence of Elasticsuite Hyva compatibility module(s) check.
 * Checks that, if Hyva theme package is installed,
 * the specific Elasticsuite/Hyva compatibility module(s) are also installed.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 */
class HyvaCompatibilityCheck implements CheckInterface
{
    /** @var ComposerInformationProvider */
    private $composerInformationProvider;

    /** @var string */
    private $triggerPackage;

    /** @var boolean */
    private $isHyvaInstalled;

    /** @var array */
    private $packagesToCheck;

    /** @var array */
    private $packagesErrors;

    /**
     * Constructor.
     *
     * @param ComposerInformationProvider $composerInformationProvider Composer information provider.
     * @param string                      $triggerPackage              Hÿva package name triggering the check.
     * @param array                       $packagesToCheck             Required Hÿva compatibility packages.
     */
    public function __construct(
        ComposerInformationProvider $composerInformationProvider,
        string $triggerPackage = 'hyva-themes/magento2-default-theme',
        array $packagesToCheck = []
    ) {
        $this->composerInformationProvider = $composerInformationProvider;
        $this->triggerPackage = $triggerPackage;
        $this->packagesToCheck = $packagesToCheck;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier(): string
    {
        return 'hyva_compatibility_check';
    }

    /**
     * {@inheritDoc}
     */
    public function getStatus(): string
    {
        return ($this->hasPackagesErrors() ? 'warning' : 'success');
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        $description = __(
            'All the required Hÿva/Elasticsuite compatibility modules (%1) are correctly installed.',
            implode(', ', $this->packagesToCheck)
        );
        if (!$this->isHyvaInstalled()) {
            $description = __(
                'Your site is not using the Hÿva theme, there are no specific requirements to fulfill.'
            );
        }

        if ($this->hasPackagesErrors()) {
            $errors = implode(', ', $this->getPackagesErrors());
            // @codingStandardsIgnoreStart
            $description = implode(
                '<br />',
                [
                    __(
                        'Your site uses the Hÿva theme through package <strong>%1</strong>. To work properly with Hÿva, Elasticsuite requires the installation of additional <strong>compatibility modules (%2)</strong>.',
                        $this->triggerPackage,
                        implode(', ', $this->packagesToCheck)
                    ),
                    ((count($this->getPackagesErrors()) > 1) ?
                        __(
                            'The compatibility modules <strong>%1 are missing</strong>. Please install them through composer.',
                            $errors,
                        ) :
                        __(
                            'The compatibility module <strong>%1 is missing</strong>. Please install it through composer.',
                            $errors,
                        )
                    ),
                ]
            );
            // @codingStandardsIgnoreEnd
        }

        return $description;
    }

    /**
     * {@inheritDoc}
     */
    public function getSortOrder(): int
    {
        return 40; // Adjust as necessary.
    }

    /**
     * Returns true if the Hÿva theme package is installed.
     *
     * @return bool
     */
    private function isHyvaInstalled(): bool
    {
        if (null === $this->isHyvaInstalled) {
            $this->isHyvaInstalled = array_key_exists(
                $this->triggerPackage,
                $this->composerInformationProvider->getComposerInformation()->getSystemPackages()
            );
        }

        return $this->isHyvaInstalled;
    }

    /**
     * Return true if there is at least one system package having a mismatched composer version.
     *
     * @return bool
     */
    private function hasPackagesErrors(): bool
    {
        return !empty($this->getPackagesErrors());
    }

    /**
     * Return the list of packages having a mismatched composer version.
     *
     * @return array
     */
    private function getPackagesErrors(): array
    {
        if (null === $this->packagesErrors) {
            $this->packagesErrors = [];
            if (!empty($this->packagesToCheck)) {
                if ($this->isHyvaInstalled()) {
                    $systemPackages = $this->composerInformationProvider->getComposerInformation()->getSystemPackages();
                    foreach ($this->packagesToCheck as $packageName) {
                        if (false === array_key_exists($packageName, $systemPackages)) {
                            $this->packagesErrors[$packageName] = $packageName;
                        }
                    }
                    ksort($this->packagesErrors);
                }
            }
        }

        return $this->packagesErrors;
    }
}
