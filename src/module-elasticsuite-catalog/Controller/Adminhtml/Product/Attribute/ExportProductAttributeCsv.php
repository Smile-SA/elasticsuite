<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2023 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Controller\Adminhtml\Product\Attribute;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\View\Result\PageFactory;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Smile\ElasticsuiteCatalog\Model\Import\ProductAttribute as ProductAttributeImport;

/**
 * Product attribute export to CSV controller.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class ExportProductAttributeCsv extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * @var CollectionFactory
     */
    protected $attributeCollectionFactory;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var array
     */
    private $columns;

    /**
     * Constructor.
     *
     * @param Context                $context                    Application context.
     * @param PageFactory            $resultPageFactory          Result Page factory.
     * @param FileFactory            $fileFactory                File Factory.
     * @param Filesystem             $filesystem                 File System.
     * @param CollectionFactory      $attributeCollectionFactory Attribute Collection Factory.
     * @param ProductAttributeImport $productAttributeImport     Product Attribute Import Model.
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        FileFactory $fileFactory,
        Filesystem $filesystem,
        CollectionFactory $attributeCollectionFactory,
        ProductAttributeImport $productAttributeImport
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->fileFactory = $fileFactory;
        $this->filesystem = $filesystem;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->columns = $productAttributeImport->getValidColumnNames();
    }

    /**
     * Execute.
     *
     * @return ResponseInterface
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        // Prepare product attributes grid collection.
        $attributeCollectionFactory = $this->attributeCollectionFactory->create();
        $attributeCollection = $attributeCollectionFactory->addVisibleFilter()
            ->setOrder('attribute_code', 'ASC');

        $content = [];

        // Add header row.
        $header = [];
        foreach ($this->columns as $column) {
            $header[] = $column;
        }
        $content[] = $header;

        // Add content row.
        foreach ($attributeCollection as $attribute) {
            $row = [
                $attribute->getAttributeCode(),
                $attribute->getDefaultFrontendLabel(),
                $attribute->getIsSearchable(),
                $attribute->getSearchWeight(),
                $attribute->getIsUsedInSpellcheck(),
                $attribute->getIsDisplayedInAutocomplete(),
                $attribute->getIsFilterable(),
                $attribute->getIsFilterableInSearch(),
                $attribute->getIsUsedForPromoRules(),
                $attribute->getUsedForSortBy(),
                $attribute->getIsDisplayRelNofollow(),
                $attribute->getFacetMaxSize(),
                $attribute->getFacetSortOrder(),
                $attribute->getFacetMinCoverageRate(),
                $attribute->getFacetBooleanLogic(),
                $attribute->getPosition(),
                $attribute->getDefaultAnalyzer(),
                $attribute->getNormsDisabled(),
                $attribute->getIsSpannable(),
                $attribute->getIncludeZeroFalseValues(),
            ];
            $content[] = $row;
        }

        // Prepare and send the CSV file to the browser.
        $date = date('Ymd_His');
        $fileName = 'elasticsuite_product_attribute-' . $date . '.csv';
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $stream = $directory->openFile($fileName, 'w+');
        foreach ($content as $line) {
            $stream->writeCsv($line);
        }
        $stream->close();

        return $this->fileFactory->create(
            $fileName,
            [
                'type' => 'filename',
                'value' => $fileName,
                'rm' => true,
            ],
            DirectoryList::VAR_DIR,
            'application/csv'
        );
    }
}
