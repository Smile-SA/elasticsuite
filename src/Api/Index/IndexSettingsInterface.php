<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile_ElasticSuite
 * @package   Smile\ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Api\Index;

/**
 * Provides acces to indices related settings / configuration.
 *
 * @category Smile_ElasticSuite
 * @package  Smile\ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface IndexSettingsInterface
{
    /**
     *
     *
     * @param string                                                $indexIdentifier
     * @param integer|string|\Magento\Store\Api\Data\StoreInterface $store
     *
     * @return string
     */
    public function getIndexAliasFromIdentifier($indexIdentifier, $store);

    /**
     *
     *
     * @param string                                                $indexIdentifier
     * @param integer|string|\Magento\Store\Api\Data\StoreInterface $store
     *
     * @return string
     */
    public function createIndexNameFromIdentifier($indexIdentifier, $store);

    /**
     *
     * @param integer|string|\Magento\Store\Api\Data\StoreInterface $store
     *
     * @return array
     */
    public function getAnalysisSettings($store);

    /**
     * @return array
     */
    public function getCreateIndexSettings();

    /**
     * @return array
     */
    public function getInstallIndexSettings();

    /**
     * @return array
     */
    public function getIndicesConfig();

    /**
     * @return integer
     */
    public function getBatchIndexingSize();
}
