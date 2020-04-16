<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteThesaurus
 * @author    Dmytro ANDROSHCHUK <dmand@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteThesaurus\Controller\Adminhtml\Thesaurus;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Smile\ElasticsuiteThesaurus\Model\Import\Thesaurus as ThesaurusImport;
use Smile\ElasticsuiteThesaurus\Api\ThesaurusRepositoryInterface;
use Smile\ElasticsuiteThesaurus\Controller\Adminhtml\AbstractThesaurus as ThesaurusController;
use Smile\ElasticsuiteThesaurus\Model\ThesaurusFactory;

/**
 * Thesaurus export to csv controller
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Dmytro ANDROSHCHUK <dmand@smile.fr>
 */
class ExportCsv extends ThesaurusController
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory|null
     */
    protected $resultPageFactory = null;

    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * @var WriteInterface
     */
    protected $directory;

    /**
     * @var array
     */
    private $columns;

    /**
     * Export csv constructor.
     *
     * @param Context                      $context             Application context
     * @param PageFactory                  $resultPageFactory   Result Page factory
     * @param Registry                     $coreRegistry        Application registry
     * @param ThesaurusRepositoryInterface $thesaurusRepository Thesaurus Repository
     * @param ThesaurusFactory             $thesaurusFactory    Thesaurus Factory
     * @param FileFactory                  $fileFactory         File Factory
     * @param Filesystem                   $filesystem          File System
     * @param ThesaurusImport              $thesaurusImport     Thesaurus Import Model
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Registry $coreRegistry,
        ThesaurusRepositoryInterface $thesaurusRepository,
        ThesaurusFactory $thesaurusFactory,
        FileFactory $fileFactory,
        Filesystem $filesystem,
        ThesaurusImport $thesaurusImport
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->fileFactory       = $fileFactory;
        $this->directory         = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->columns           = $thesaurusImport->getValidColumnNames();
        parent::__construct($context, $resultPageFactory, $coreRegistry, $thesaurusRepository, $thesaurusFactory);
    }

    /**
     * Export thesaurus data to CSV format
     *
     * @return ResponseInterface
     */
    public function execute()
    {
        $date = date('Ymd_His');
        $filepath = 'export/export-thesaurus-' . $date . '.csv';
        $this->directory->create('export');

        $stream = $this->directory->openFile($filepath, 'w+');
        $stream->lock();

        $header = [];
        foreach ($this->columns as $column) {
            $header[] = $column;
        }

        $stream->writeCsv($header);

        $thesaurusFactory = $this->thesaurusFactory->create();
        $thesaurusCollection = $thesaurusFactory->getCollection()->getItems();

        foreach ($thesaurusCollection as $item) {
            $itemData = [];
            $itemData[] = $item->getData('thesaurus_id');
            $itemData[] = $item->getData('name');
            $itemData[] = $item->getData('type');
            $itemData[] = $item->getData('terms_export');
            $itemData[] = $item->getData('store_codes');
            $itemData[] = $item->getData('is_active');
            $stream->writeCsv($itemData);
        }

        $content = [];
        $content['type'] = 'filename';
        $content['value'] = $filepath;
        $content['rm'] = true;

        $filename = 'thesaurus-export-' . $date . '.csv';

        return $this->fileFactory->create($filename, $content, DirectoryList::VAR_DIR);
    }
}
