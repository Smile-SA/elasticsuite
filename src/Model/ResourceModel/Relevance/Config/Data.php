<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuite________
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Model\ResourceModel\Relevance\Config;

/**
 * _________________________________________________
 *
 * @category Smile
 * @package  Smile_ElasticSuite______________
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Data extends \Magento\Config\Model\ResourceModel\Config\Data
{

    /**
     * Define main table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('smile_elasticsuite_relevance_config_data', 'config_id');
    }

    public function save(\Magento\Framework\Model\AbstractModel $object)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/debug.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info('Your text message');
        $logger->debug("BEFORE SAVE");
        //$logger->debug(print_r($object->getData(), true));
        parent::save($object);
        $logger->debug("AFTER SAVE ");
    }
}
