<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Search;

use Magento\Store\Model\StoreManagerInterface;

/**
 * Elasticsuite Search Context
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Context
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
     * @var null|int
     */
    private $storeId = null;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

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
     * Set current category to Search Context.
     *
     * @param \Magento\Catalog\Api\Data\CategoryInterface $category Current Category
     *
     * @throws \Exception
     *
     * @return $this
     */
    public function setCurrentCategory(\Magento\Catalog\Api\Data\CategoryInterface $category)
    {
        if ($this->category !== null && ((int) $this->category->getId() === (int) $category->getId())) {
            return $this;
        }

        if ($this->searchQuery !== null || $this->category !== null) {
            throw new \RuntimeException("Search context cannot vary once created.");
        }

        $this->category = $category;

        return $this;
    }

    /**
     * Set current search query to Search Context
     *
     * @param \Magento\Search\Model\QueryInterface $query Current Search Query
     *
     * @throws \Exception
     *
     * @return $this
     */
    public function setCurrentSearchQuery(\Magento\Search\Model\QueryInterface $query)
    {
        if ($this->searchQuery !== null && ($this->searchQuery->getQueryText() === $query->getQueryText())) {
            return $this;
        }

        if ($this->category !== null || $this->searchQuery !== null) {
            throw new \RuntimeException("Search context cannot vary once created.");
        }

        $this->searchQuery = $query;

        return $this;
    }

    /**
     * Set Store Id
     *
     * @param int $storeId Store Id
     *
     * @throws \Exception
     *
     * @return $this
     */
    public function setStoreId(int $storeId)
    {
        if ($this->storeId !== null) {
            throw new \Exception("Search context cannot vary once created.");
        }

        $this->storeId = $storeId;

        return $this;
    }

    /**
     * @return \Magento\Catalog\Api\Data\CategoryInterface
     */
    public function getCurrentCategory()
    {
        return $this->category;
    }

    /**
     * @return \Magento\Search\Model\QueryInterface
     */
    public function getCurrentSearchQuery()
    {
        return $this->searchQuery;
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        if ($this->storeId === null) {
            $this->storeId = $this->storeManager->getStore()->getId();
        }

        return $this->storeId;
    }
}
