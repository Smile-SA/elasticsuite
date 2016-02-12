<?php
/**
 * Smile_ElasticSuiteCore search engine configuration default implementation.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCore\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\StoreManager;

/**
 * Smile_ElasticSuiteCore search engine configuration default implementation.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
abstract class ConfigurationAbstract extends AbstractHelper
{
    /**
     * @var Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    public function __construct(Context $context, StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * Location of ElasticSuite base settings configuration.
     *
     * @var string
     */
    const BASE_CONFIG_XML_PREFIX = 'smile_elasticsuite_core_base_settings';

    /**
     * Read a configuration param under the BASE_CONFIG_XML_PREFIX ('smile_elasticsuite_core_base_settings/').
     *
     * @param string $configField
     *
     * @return mixed
     */
    protected function getElasticSuiteConfigParam($configField)
    {
        $path = self::BASE_CONFIG_XML_PREFIX . '/' . $configField;
        return $this->scopeConfig->getValue($path);
    }

}