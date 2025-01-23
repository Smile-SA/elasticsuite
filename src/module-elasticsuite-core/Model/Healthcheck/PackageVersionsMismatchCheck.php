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

namespace Smile\ElasticsuiteCore\Model\Healthcheck;

use Smile\ElasticsuiteCore\Api\Healthcheck\CheckInterface;
use Smile\ElasticsuiteCore\Model\ProductMetadata;
use Smile\ElasticsuiteCore\Model\ProductMetadata\ComposerInformationProvider;

/**
 * Elasticsuite packages versions mismatch check.
 * Checks that known Elasticsuite packages that require to be in the same version as the core package
 * are actually complying with that requirement.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 */
class PackageVersionsMismatchCheck implements CheckInterface
{
    /** @var ProductMetadata */
    private $productMetadata;

    /** @var ComposerInformationProvider */
    private $composerInformationProvider;

    /** @var string */
    private $packagesToCheck;

    /** @var array */
    private $packagesErrors;

    /**
     * Constructor.
     *
     * @param ProductMetadata             $productMetadata             Elasticsuite product metadata.
     * @param ComposerInformationProvider $composerInformationProvider Composer information provider.
     * @param array                       $packagesToCheck             List of packages names to check.
     */
    public function __construct(
        ProductMetadata $productMetadata,
        ComposerInformationProvider $composerInformationProvider,
        array $packagesToCheck = []
    ) {
        $this->productMetadata = $productMetadata;
        $this->composerInformationProvider = $composerInformationProvider;
        $this->packagesToCheck = $packagesToCheck;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier(): string
    {
        return 'packages_version_check';
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
            'All additional Elasticsuite packages are in the same version as the Elasticsuite core package (smile/elasticsuite).'
        );

        if ($this->hasPackagesErrors()) {
            $errors = [];
            $errors[] = '<ul>';
            foreach ($this->getPackagesErrors() as $packageName => $packageVersion) {
                $errors[] = sprintf("<li>%s (<em>%s</em>)</li>", $packageName, $packageVersion);
            }
            $errors[] = '</ul>';

            // @codingStandardsIgnoreStart
            $description = implode(
                '<br />',
                [
                    __(
                        'Some additional Elasticsuite packages are <strong>not in the same version</strong> as the Elasticsuite core package <strong>smile/elasticsuite</strong> which is in version <strong>%1</strong>.',
                        $this->productMetadata->getVersion()
                    ),
                    __(
                        'You should <strong>update</strong> either the core or those additional <strong>Elasticsuite packages through composer</strong> so they share the exact same version.'
                    ),
                    __(
                        'Those composer packages are: %1',
                        implode(' ', $errors)
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
    public function isDisplayed(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getSortOrder(): int
    {
        return 40; // Adjust as necessary.
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
            if (!empty($this->packagesToCheck)) {
                $corePackageVersion = $this->productMetadata->getVersion();
                $systemPackages = $this->composerInformationProvider->getComposerInformation()->getSystemPackages();
                foreach ($this->packagesToCheck as $packageName) {
                    $packageVersion = $systemPackages[$packageName] ?? 'N/A';
                    if ($packageVersion !== $corePackageVersion) {
                        $this->packagesErrors[$packageName] = $packageVersion;
                    }
                }
                ksort($this->packagesErrors);
            }
        }

        return $this->packagesErrors;
    }
}
