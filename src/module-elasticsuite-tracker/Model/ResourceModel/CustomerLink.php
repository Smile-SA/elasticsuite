<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteTracker\Model\ResourceModel;

/**
 * Customer Link Resource Model.
 * Used to save correlation between tracker visitor_id, session_id and customer_id.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class CustomerLink extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Save customer link.
     *
     * @param array $linkData array containing visitor_id, session_id, customer_id and delete_after.
     */
    public function saveLink($linkData)
    {
        $connection = $this->getConnection();

        if (isset($linkData['delete_after']) && ($linkData['delete_after'] instanceof \DateTime)) {
            $linkData['delete_after'] = $this->formatDate($linkData['delete_after']);
        }

        $connection->insertOnDuplicate(
            $this->getMainTable(),
            $linkData,
            array_keys($linkData)
        );
    }

    /**
     * Delete all link data for a given customer id.
     *
     * @param int $customerId the customer Id
     */
    public function deleteByCustomerId($customerId)
    {
        $connection = $this->getConnection();
        $connection->delete($this->getMainTable(), $connection->quoteInto('customer_id = ?', $customerId));
    }

    /**
     * Delete all link data that are having a "delete_after" column which is lteq than the one passed in parameter.
     *
     * @param \DateTime $dateTime The date
     */
    public function deleteByDeleteAfter(\DateTime $dateTime)
    {
        $connection  = $this->getConnection();
        $deleteAfter = $this->formatDate($dateTime);
        $connection->delete($this->getMainTable(), $connection->quoteInto('delete_after <= ?', $deleteAfter));
    }

    /**
     * Set the delete after field for a given customer Id.
     *
     * @param int       $customerId The customer Id
     * @param \DateTime $dateTime   The date to set
     */
    public function setDeleteAfter(int $customerId, \DateTime $dateTime)
    {
        $connection  = $this->getConnection();
        $deleteAfter = $this->formatDate($dateTime);
        $connection->update(
            $this->getMainTable(),
            ['delete_after' => $deleteAfter],
            $connection->quoteInto('customer_id = ?', $customerId)
        );
    }

    /**
     * Retrieve all visitor ids used by a given customer Id.
     *
     * @param int $customerId The customer Id
     *
     * @return array
     */
    public function getVisitorIds($customerId)
    {
        $connection = $this->getConnection();

        $select = $connection->select()
            ->from($this->getMainTable(), ['visitor_id'])
            ->where($connection->quoteInto('customer_id = ?', $customerId));

        return array_unique($connection->fetchCol($select));
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _construct()
    {
        $this->_init('elasticsuite_tracker_log_customer_link', 'customer_id');
    }

    /**
     * Format a DateTime object to proper format for DB saving.
     *
     * @param \DateTime $dateTime Date time
     *
     * @return string
     */
    private function formatDate(\DateTime $dateTime)
    {
        return $dateTime->format(\Magento\Framework\DB\Adapter\Pdo\Mysql::DATE_FORMAT);
    }
}
