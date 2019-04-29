<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Smile_ElasticsuiteCore search engine configuration default implementation.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
abstract class AbstractConfiguration extends AbstractHelper
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Constructor.
     *
     * @param Context               $context      Helper context.
     * @param StoreManagerInterface $storeManager Store manager.
     */
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
     * Read a configuration param under the BASE_CONFIG_XML_PREFIX ('smile_elasticsuite_core_base_settings').
     *
     * @param string $configField Configuration field name.
     *
     * @return mixed
     */
    protected function getElasticSuiteConfigParam($configField)
    {
        $path = self::BASE_CONFIG_XML_PREFIX . '/' . $configField;

        return $this->scopeConfig->getValue($path);
    }
}
