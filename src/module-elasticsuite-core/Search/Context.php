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
namespace Smile\ElasticsuiteCore\Search;

use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCore\Api\Search\ContextInterface;

/**
 * Elasticsuite Search Context
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Context implements \Smile\ElasticsuiteCore\Api\Search\ContextInterface
{
    /**
     * @var \Magento\Catalog\Api\Data\CategoryInterface
     */
    private $category = null;

    /**
     * @var \Magento\Search\Model\QueryInterface
     */
    private $searchQuery = null;

    /**
     * @var null|integer
     */
    private $storeId = null;

    /**
     * @var null|integer
     */
    private $customerGroupId = null;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var boolean
     */
    private $isBlacklistingApplied = true;

    /**
     * Context constructor.
     *
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager Store Manager
     */
    public function __construct(\Magento\Store\Model\StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function setCurrentCategory(\Magento\Catalog\Api\Data\CategoryInterface $category)
    {
        if ($this->category !== null && ((int) $this->category->getId() === (int) $category->getId())) {
            return $this;
        }

        $this->category = $category;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setCurrentSearchQuery(\Magento\Search\Model\QueryInterface $query)
    {
        if ($this->searchQuery !== null && ($this->searchQuery->getQueryText() === $query->getQueryText())) {
            return $this;
        }

        $this->searchQuery = $query;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setStoreId(int $storeId)
    {
        $this->storeId = $storeId;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setCustomerGroupId(int $customerGroupId)
    {
        $this->customerGroupId = $customerGroupId;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentCategory()
    {
        return $this->category;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentSearchQuery()
    {
        return $this->searchQuery;
    }

    /**
     * {@inheritdoc}
     */
    public function getStoreId()
    {
        if ($this->storeId === null) {
            $this->storeId = $this->storeManager->getStore()->getId();
        }

        return $this->storeId;
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomerGroupId()
    {
        return $this->customerGroupId;
    }

    /**
     * {@inheritdoc}
     */
    public function isBlacklistingApplied(): bool
    {
        return $this->isBlacklistingApplied;
    }

    /**
     * {@inheritdoc}
     */
    public function setIsBlacklistingApplied(bool $blacklistingApplied): ContextInterface
    {
        $this->isBlacklistingApplied = $blacklistingApplied;

        return $this;
    }
}
