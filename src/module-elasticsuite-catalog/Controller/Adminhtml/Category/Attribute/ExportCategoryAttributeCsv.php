<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Controller\Adminhtml\Category\Attribute;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\ImportExport\Model\Export\Adapter\CsvFactory;
use Smile\ElasticsuiteCatalog\Model\Export\CategoryAttribute as CategoryAttributeExport;

/**
 * Category attribute export to CSV controller.
 *
 * Triggered from the "Export to:" dropdown on the admin Product Attributes grid, alongside the
 * product attribute CSV export. Reuses the same export entity as the generic System > Data
 * Transfer > Export screen (Model/Export/CategoryAttribute.php) instead of duplicating its logic.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 */
class ExportCategoryAttributeCsv extends Action
{
    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var CsvFactory
     */
    private $csvFactory;

    /**
     * @var CategoryAttributeExport
     */
    private $categoryAttributeExport;

    /**
     * Constructor.
     *
     * @param Context                 $context                 Application context.
     * @param FileFactory             $fileFactory             File Factory.
     * @param CsvFactory              $csvFactory              Csv Writer Factory.
     * @param CategoryAttributeExport $categoryAttributeExport Category Attribute Export Model.
     */
    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        CsvFactory $csvFactory,
        CategoryAttributeExport $categoryAttributeExport
    ) {
        parent::__construct($context);
        $this->fileFactory             = $fileFactory;
        $this->csvFactory              = $csvFactory;
        $this->categoryAttributeExport = $categoryAttributeExport;
    }

    /**
     * Execute.
     *
     * @return ResponseInterface
     *
     * @throws LocalizedException
     */
    public function execute()
    {
        $this->categoryAttributeExport->setWriter($this->csvFactory->create());
        $content = $this->categoryAttributeExport->export();

        $fileName = 'elasticsuite_category_attribute-' . date('Ymd_His') . '.csv';

        return $this->fileFactory->create($fileName, $content, DirectoryList::VAR_DIR, 'text/csv');
    }
}
