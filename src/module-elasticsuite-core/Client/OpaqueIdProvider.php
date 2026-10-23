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

namespace Smile\ElasticsuiteCore\Client;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCore\Api\Client\OpaqueIdPartProviderInterface;
use Smile\ElasticsuiteCore\Api\Client\OpaqueIdProviderInterface;

/**
 * Default X-Opaque-Id provider.
 * The value is computed on each call since the area and the current store can be emulated during a request.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class OpaqueIdProvider implements OpaqueIdProviderInterface
{
    /**
     * @var string
     */
    const ENABLED_CONFIG_XML_PATH = 'smile_elasticsuite_core_base_settings/es_client/enable_opaque_id';

    /**
     * @var integer
     */
    const MAX_LENGTH = 256;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var State
     */
    private $appState;

    /**
     * @var HttpRequest
     */
    private $request;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var string
     */
    private $sapi;

    /**
     * @var OpaqueIdPartProviderInterface[]
     */
    private $partProviders;

    /**
     * @var string|null
     */
    private $cronJob = null;

    /**
     * Constructor.
     *
     * @param ScopeConfigInterface            $scopeConfig   Scope config.
     * @param State                           $appState      Application state.
     * @param HttpRequest                     $request       Request.
     * @param StoreManagerInterface           $storeManager  Store manager.
     * @param string                          $sapi          PHP SAPI name.
     * @param OpaqueIdPartProviderInterface[] $partProviders Additional context part providers.
     *
     * @throws \InvalidArgumentException When a part provider does not implement OpaqueIdPartProviderInterface.
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        State $appState,
        HttpRequest $request,
        StoreManagerInterface $storeManager,
        string $sapi = PHP_SAPI,
        array $partProviders = []
    ) {
        foreach ($partProviders as $name => $partProvider) {
            if (!$partProvider instanceof OpaqueIdPartProviderInterface) {
                throw new \InvalidArgumentException(
                    sprintf('X-Opaque-Id part provider "%s" must implement %s.', $name, OpaqueIdPartProviderInterface::class)
                );
            }
        }

        $this->scopeConfig   = $scopeConfig;
        $this->appState      = $appState;
        $this->request       = $request;
        $this->storeManager  = $storeManager;
        $this->sapi          = $sapi;
        $this->partProviders = $partProviders;
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getOpaqueId(array $details = []): ?string
    {
        if (!$this->scopeConfig->isSetFlag(self::ENABLED_CONFIG_XML_PATH)) {
            return null;
        }

        $isCli = ($this->sapi === 'cli');
        $parts = [
            'area' => $this->getAreaCode(),
            'act'  => $isCli ? null : $this->getFullActionName(),
            'cli'  => $isCli ? $this->getCliCommand() : null,
            'cron' => $this->cronJob,
            // In CLI, the current store is not the one being worked on (e.g. a reindex goes through every store):
            // callers knowing it pass it in the details.
            'str'  => $isCli ? null : $this->getStoreCode(),
        ];

        foreach ($this->partProviders as $partProvider) {
            $parts = array_merge($parts, $partProvider->getParts());
        }

        $parts = array_merge($parts, $details);

        $pairs = [];
        foreach ($parts as $key => $value) {
            if ($value !== null && $value !== '') {
                $pairs[] = $key . '=' . $this->sanitize((string) $value);
            }
        }

        return empty($pairs) ? null : substr(implode(';', $pairs), 0, self::MAX_LENGTH);
    }

    /**
     * {@inheritDoc}
     */
    public function setCronJob(?string $jobName): void
    {
        $this->cronJob = $jobName;
    }

    /**
     * Replace any character that is not safe in a header value or that would break the "key=value;" format.
     *
     * @param string $value Value.
     *
     * @return string
     */
    private function sanitize(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9_.:\/,-]/', '_', $value);
    }

    /**
     * Return the current area code, if any.
     *
     * @return string|null
     */
    private function getAreaCode(): ?string
    {
        try {
            return $this->appState->getAreaCode();
        } catch (LocalizedException $exception) {
            return null;
        }
    }

    /**
     * Return the full action name of the current web request, if it has been routed.
     *
     * @return string|null
     */
    private function getFullActionName(): ?string
    {
        return $this->request->getRouteName() ? $this->request->getFullActionName() : null;
    }

    /**
     * Return the name of the current CLI command (e.g. "indexer:reindex"), if any.
     *
     * @return string|null
     */
    private function getCliCommand(): ?string
    {
        $argv = $this->request->getServer('argv');

        // The first argument that is not an option (e.g. "bin/magento -v indexer:reindex").
        foreach (array_slice(is_array($argv) ? $argv : [], 1) as $argument) {
            if (!str_starts_with((string) $argument, '-')) {
                return (string) $argument;
            }
        }

        return null;
    }

    /**
     * Return the current store code, if available.
     *
     * @return string|null
     */
    private function getStoreCode(): ?string
    {
        try {
            return $this->storeManager->getStore()->getCode();
        } catch (\Exception $exception) {
            return null;
        }
    }
}
