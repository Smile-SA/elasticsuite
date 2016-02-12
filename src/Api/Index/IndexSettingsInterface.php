<?php
/**
 *
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile_ElasticSuite
 * @package   Smile\ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCore\Api\Index;

interface IndexSettingsInterface
{

    /**
     * @return string
     */
    public function getIndexAliasFromIdentifier($indexIdentifier, $store);

    /**
     * @return string
     */
    public function createIndexNameFromIdentifier($indexIdentifier, $store);

    public function getAnalysisSettings($store);

    /**
     * @return arra[]
     */
    public function getCreateIndexSettings();

    /**
     * @return array[]
     */
    public function getInstallIndexSettings();

    /**
     * @return array[]
     */
    public function getIndicesConfig();

    /**
     * @return int
     */
    public function getBatchIndexingSize();
}