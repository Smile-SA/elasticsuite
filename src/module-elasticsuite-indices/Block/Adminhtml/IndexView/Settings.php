<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteIndices
 * @author    Dmytro ANDROSHCHUK <dmand@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteIndices\Block\Adminhtml\IndexView;

use Magento\Backend\Block\Template;
use Smile\ElasticsuiteIndices\Model\ResourceModel\IndexSettings\Collection;
use Smile\ElasticsuiteIndices\Model\ResourceModel\IndexSettings\CollectionFactory as IndexSettingsFactory;

/**
 * Adminhtml Index mapping items grid
 *
 * @category Smile
 * @package  Smile\ElasticsuiteIndices
 * @author   Dmytro ANDROSHCHUK <dmand@smile.fr>
 */
class Settings extends Template
{
    /**
     * @var IndexSettingsFactory
     */
    protected $indexSettingsFactory;

    /**
     * Index mapping items constructor.
     *
     * @param Template\Context     $context              The current context.
     * @param IndexSettingsFactory $indexSettingsFactory Index mapping factory.
     * @param array                $data                 Data.
     */
    public function __construct(
        Template\Context $context,
        IndexSettingsFactory $indexSettingsFactory,
        array $data = []
    ) {
        $this->indexSettingsFactory = $indexSettingsFactory;
        parent::__construct($context, $data);
    }

    /**
     * Index mapping items collection
     *
     * @return Collection
     */
    public function getItemsCollection(): Collection
    {
        return $this->indexSettingsFactory->create(['name' => $this->getRequest()->getParam('name')])->load();
    }
}
