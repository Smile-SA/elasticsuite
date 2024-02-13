<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Api\Search;

/**
 * Search Context Interface
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
interface ContextInterface
{
    /**
     * Set current category to Search Context.
     *
     * @param \Magento\Catalog\Api\Data\CategoryInterface $category Current Category
     *
     * @return $this
     */
    public function setCurrentCategory(\Magento\Catalog\Api\Data\CategoryInterface $category);

    /**
     * Set current search query to Search Context
     *
     * @param \Magento\Search\Model\QueryInterface $query Current Search Query
     *
     * @return $this
     */
    public function setCurrentSearchQuery(\Magento\Search\Model\QueryInterface $query);

    /**
     * Set Store Id
     *
     * @param int $storeId Store Id
     *
     * @return $this
     */
    public function setStoreId(int $storeId);

    /**
     * Set Customer Group Id
     *
     * @param int $customerGroupId Customer Group Id
     *
     * @return $this
     */
    public function setCustomerGroupId(int $customerGroupId);

    /**
     * Return the current category
     * Either the current category being viewed in catalog navigation,
     * or the current applied category filter in search.
     *
     * @return \Magento\Catalog\Api\Data\CategoryInterface
     */
    public function getCurrentCategory();

    /**
     * Return the current search query.
     *
     * @return \Magento\Search\Model\QueryInterface
     */
    public function getCurrentSearchQuery();

    /**
     * Return the current store id.
     * Used to determine the applicable optimizers.
     *
     * @return int
     */
    public function getStoreId();

    /**
     * Return the current customer group id.
     * Used to determine which prices to filter on.
     *
     * @return int|null
     */
    public function getCustomerGroupId();

    /**
     * Returns true if the merchandisers' blacklisting mechanism should be applied.
     *
     * @return bool
     */
    public function isBlacklistingApplied(): bool;

    /**
     * Sets if the merchandisers' blacklisting mechanism should be applied.
     *
     * @param bool $blacklistingApplied Whether to applied blacklisting or not.
     *
     * @return bool
     */
    public function setIsBlacklistingApplied(bool $blacklistingApplied):  ContextInterface;
}
