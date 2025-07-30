<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Pierre Gauthier <pigau@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Model\Index;

use Magento\Framework\Model\AbstractModel;
use Smile\ElasticsuiteCore\Model\ResourceModel\Index\BulkError as ResourceModel;

/**
 * Model for bulk errors.
 */
class BulkError extends AbstractModel
{
    /**
     * Get entity id.
     *
     * @return int|null
     */
    public function getEntityId(): ?int
    {
        return $this->getData('entity_id');
    }

    /**
     * Get store code.
     *
     * @return string
     */
    public function getStoreCode(): string
    {
        return $this->getData('store_code');
    }

    /**
     * Set store code.
     *
     * @param string $storeCode Store code.
     * @return self
     */
    public function setStoreCode(string $storeCode): self
    {
        return $this->setData('store_code', $storeCode);
    }

    /**
     * Get index identifier.
     *
     * @return string
     */
    public function getIndexIdentifier(): string
    {
        return $this->getData('index_identifier');
    }

    /**
     * Set index identifier.
     *
     * @param string $indexIdentifier Index identifier.
     * @return self
     */
    public function setIndexIdentifier(string $indexIdentifier): self
    {
        return $this->setData('index_identifier', $indexIdentifier);
    }

    /**
     * Get error type.
     *
     * @return string
     */
    public function getErrorType(): string
    {
        return $this->getData('error_type');
    }

    /**
     * Set error type.
     *
     * @param string $errorType Error type.
     * @return self
     */
    public function setErrorType(string $errorType): self
    {
        return $this->setData('error_type', $errorType);
    }

    /**
     * Get error reason.
     *
     * @return string
     */
    public function getReason(): string
    {
        return $this->getData('reason');
    }

    /**
     * Set error reason.
     *
     * @param string $reason Error reason.
     * @return self
     */
    public function setReason(string $reason): self
    {
        return $this->setData('reason', $reason);
    }

    /**
     * Get error document sample ids.
     *
     * @return string
     */
    public function getSampleIds(): string
    {
        return $this->getData('sample_ids');
    }

    /**
     * Set error document sample ids.
     *
     * @param string $sampleIds Error sample document ids.
     * @return self
     */
    public function setSampleIds(string $sampleIds): self
    {
        return $this->setData('sample_ids', $sampleIds);
    }

    /**
     * Get error count.
     *
     * @return int
     */
    public function getCount(): int
    {
        return $this->getData('count');
    }

    /**
     * Set error count.
     *
     * @param int $count Error count.
     * @return self
     */
    public function setCount(int $count): self
    {
        return $this->setData('count', $count);
    }

    /**
     * Get created at.
     *
     * @return string
     */
    public function getCreatedAt(): string
    {
        return $this->getData('created_at');
    }

    /**
     * Set created at.
     *
     * @param string $createdAt created at.
     * @return self
     */
    public function setCreatedAt(string $createdAt): self
    {
        return $this->setData('created_at', $createdAt);
    }

    /**
     * Get updated at.
     *
     * @return string
     */
    public function getUpdatedAt(): string
    {
        return $this->getData('updated_at');
    }

    /**
     * Set updated at.
     *
     * @param string $updatedAt Updated at.
     * @return self
     */
    public function setUpdatedAt(string $updatedAt): self
    {
        return $this->setData('updated_at', $updatedAt);
    }

    /**
     * Bulk error model constructor.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }
}
