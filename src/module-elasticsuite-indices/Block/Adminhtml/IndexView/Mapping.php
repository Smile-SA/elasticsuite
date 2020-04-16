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
use Smile\ElasticsuiteIndices\Model\ResourceModel\IndexMapping\Collection;
use Smile\ElasticsuiteIndices\Model\ResourceModel\IndexMapping\CollectionFactory as IndexMappingFactory;

/**
 * Adminhtml Index mapping items grid
 *
 * @category Smile
 * @package  Smile\ElasticsuiteIndices
 * @author   Dmytro ANDROSHCHUK <dmand@smile.fr>
 */
class Mapping extends Template
{
    /**
     * @var IndexMappingFactory
     */
    protected $indexMappingFactory;

    /**
     * Index mapping items constructor.
     *
     * @param Template\Context    $context             The current context.
     * @param IndexMappingFactory $indexMappingFactory Index mapping factory.
     * @param array               $data                Data.
     */
    public function __construct(
        Template\Context $context,
        IndexMappingFactory $indexMappingFactory,
        array $data = []
    ) {
        $this->indexMappingFactory = $indexMappingFactory;
        parent::__construct($context, $data);
    }

    /**
     * Index mapping items collection
     *
     * @return Collection
     */
    public function getItemsCollection(): Collection
    {
        return $this->indexMappingFactory->create(['name' => $this->getRequest()->getParam('name')])->load();
    }
}
