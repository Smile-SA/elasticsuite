<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Plugin\Deprecation\Index;

/**
 * Implements backward compatibility of indices settings with ES 5.x
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class IndexSettings
{
    /**
     * @var string
     */
    private $serverVersion;

    /**
     * Constructor.
     *
     * @param \Smile\ElasticsuiteCore\Api\Cluster\ClusterInfoInterface $clusterInfo Cluster information API.
     */
    public function __construct(\Smile\ElasticsuiteCore\Api\Cluster\ClusterInfoInterface $clusterInfo)
    {
        $this->serverVersion = $clusterInfo->getServerVersion();
    }

    /**
     * Remove empty stemmer_override rules for ES 5.x since it does not accept it.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @param \Smile\ElasticsuiteCore\Index\IndexSettings           $subject Index Settings.
     * @param array                                                 $result  The result.
     * @param integer|string|\Magento\Store\Api\Data\StoreInterface $store   Store.
     *
     * @return array
     */
    public function afterGetAnalysisSettings($subject, $result, $store)
    {
        if (strcmp($this->serverVersion, "6") < 0) {
            if (isset($result['filter'])) {
                $ignoredFilters = [];
                foreach ($result['filter'] as $filterName => $filterConfig) {
                    if (isset($filterConfig['type']) && ($filterConfig['type'] === 'stemmer_override')) {
                        if (isset($filterConfig['rules']) && empty($filterConfig['rules'])) {
                            $ignoredFilters[] = $filterName;
                            unset($result['filter'][$filterName]);
                        }
                    }
                }

                if (!empty($ignoredFilters) && isset($result['analyzer'])) {
                    foreach ($result['analyzer'] as &$analyzer) {
                        if (isset($analyzer['filter']) && is_array($analyzer['filter'])) {
                            $analyzer['filter'] = array_filter(
                                $analyzer['filter'],
                                function ($filter) use ($ignoredFilters) {
                                    return (!in_array($filter, $ignoredFilters));
                                }
                            );
                        }
                    }
                }
            }
        }

        return $result;
    }
}
