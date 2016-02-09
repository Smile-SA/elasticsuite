<?php

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
     * return arra[]
     */
    public function getCreateIndexSettings();

    /**
     * return array[]
     */
    public function getInstallIndexSettings();

    /**
     * return array[]
     */
    public function getIndicesConfig();
}