<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Api\Data;

/**
 * Elasticsuite Catalog Optimizer Interface
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Fanny DECLERCK <fadec@smile.fr>
 */
/**
 * Interface OptimizerInterface
 * @package Smile\ElasticsuiteCatalogOptimizer\Api\Data
 */
interface OptimizerInterface
{
    /**
     * Name of the main Mysql Table
     */
    const TABLE_NAME = 'smile_elasticsuite_optimizer';

    /**
     * Name of the join Mysql Table
     */
    const TABLE_NAME_SEARCH_CONTAINER = 'smile_elasticsuite_optimizer_search_container';

    /**
     * Constant for field optimizer_id
     */
    const OPTIMIZER_ID = 'optimizer_id';

    /**
     * Constant for field name
     */
    const NAME = 'name';

    /**
     * Constant for field is_active
     */
    const IS_ACTIVE = 'is_active';

    /**
     * Constant for field model
     */
    const MODEL = 'model';

    /**
     * Constant for field config
     */
    const CONFIG = 'config';

    /**
     * Constant for field store_id
     */
    const STORE_ID = 'store_id';

    /**
     * Constant for field from_date
     */
    const FROM_DATE = 'from_date';

    /**
     * Constant for field to_date
     */
    const TO_DATE = 'to_date';

    /**
     * Constant for field search_container
     */
    const SEARCH_CONTAINER = 'search_container';

    /**
     * Constant for field rule_condition
     */
    const RULE_CONDITION = 'rule_condition';

    /**
     * Get Optimizer ID
     *
     * @return int|null
     */
    public function getId();

    /**
     * Get name
     *
     * @return string
     */
    public function getName();

    /**
     * Get Optimizer status
     *
     * @return bool
     */
    public function isActive();

    /**
     * Get model
     *
     * @return string
     */
    public function getModel();

    /**
     * Get config
     *
     * @return string
     */
    public function getConfig();

    /**
     * Get store id
     *
     * @return int
     */
    public function getStoreId();

    /**
     * Get from_date
     *
     * @return string
     */
    public function getFromDate();

    /**
     * Get to_date
     *
     * @return string
     */
    public function getToDate();

    /**
     * Get search_container
     *
     * @return string
     */
    public function getSearchContainer();

    /**
     * Get rule_condition
     *
     * @return \Smile\ElasticsuiteVirtualCategory\Api\Data\VirtualRuleInterface
     */
    public function getRuleCondition();

    /**
     * Set id
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     *
     * @param int $id Optimizer id.
     *
     * @return OptimizerInterface
     */
    public function setId($id);

    /**
     * Set name
     *
     * @param string $name the value to save
     *
     * @return OptimizerInterface
     */
    public function setName($name);

    /**
     * Set Optimizer status
     *
     * @param bool $status The optimizer status
     *
     * @return OptimizerInterface
     */
    public function setIsActive($status);

    /**
     * Set model
     *
     * @param string $model The model of optimizer to save
     *
     * @return OptimizerInterface
     */
    public function setModel($model);

    /**
     * Set config
     *
     * @param string $config The config of optimizer to save
     *
     * @return OptimizerInterface
     */
    public function setConfig($config);

    /**
     * Set store id
     *
     * @param int $storeId the store id
     *
     * @return OptimizerInterface
     */
    public function setStoreId($storeId);

    /**
     * Set from_date
     *
     * @param string|null $fromDate The from date.
     *
     * @return OptimizerInterface
     */
    public function setFromDate($fromDate);

    /**
     * Set to_date
     *
     * @param string|null $toDate The to date
     *
     * @return OptimizerInterface
     */
    public function setToDate($toDate);

    /**
     * Set search container.
     *
     * @param string $searchContainer The value to search container.
     *
     * @return OptimizerInterface
     */
    public function setSearchContainer($searchContainer);

    /**
     * Set rule_condition
     *
     * @param string $ruleCondition The value to rule_condition.
     *
     * @return string
     */
    public function setRuleCondition($ruleCondition);
}
