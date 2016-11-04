<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Ui\Component\Optimizer\Form;

use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Type;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Ui\DataProvider\EavValidationRules;

//use Smile\ElasticsuiteCatalogOptimizer\Api\Data\SellerAttributeInterface;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;
use Smile\ElasticsuiteCatalogOptimizer\Api\OptimizerRepositoryInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\CollectionFactory as OptimizerCollectionFactory;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer;

/**
 * Seller Data provider for adminhtml edit form
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Fanny DECLERCK <fadec@smile.fr>
 */
class DataProvider extends AbstractDataProvider
{
    /**
     * @var array
     */
    private $loadedData;

    /**
     * EAV attribute properties to fetch from meta storage
     *
     * @var array
     */
    private $metaProperties = [
        'dataType'  => 'frontend_input',
        'visible'   => 'is_visible',
        'required'  => 'is_required',
        'label'     => 'frontend_label',
        'sortOrder' => 'sort_order',
        'notice'    => 'note',
        'default'   => 'default_value',
        'size'      => 'multiline_count',
    ];

    /**
     * Form element mapping
     *
     * @var array
     */
    private $formElement = [
        'text'    => 'input',
        'boolean' => 'checkbox',
    ];

    /**
     * List of fields that should not be added into the form
     *
     * @var array
     */
    private $ignoreFields = [];

    /**
     * @var EavValidationRules
     */
    private $eavValidationRules;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * @var OptimizerRepositoryInterface
     */
    private $optimizerRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Data Provider Request Scope Parameter Identifier name
     *
     * @var string
     */
    private $requestScopeFieldName = 'store';

    /**
     * DataProvider constructor
     *
     * @param string                       $name                       Component Name
     * @param string                       $primaryFieldName           Primary Field Name
     * @param string                       $requestFieldName           Request Field Name
     * @param EavValidationRules           $eavValidationRules         EAV Validation Rules
     * @param OptimizerCollectionFactory   $optimizerCollectionFactory Optimizer Collection Factory
     * @param StoreManagerInterface        $storeManager               Store Manager Interface
     * @param Registry                     $registry                   The Registry
     * @param Config                       $eavConfig                  EAV Configuration
     * @param RequestInterface             $request                    The Request
     * @param OptimizerRepositoryInterface $optimizerRepository        The Optimizer Repository
     * @param array                        $meta                       Component Metadata
     * @param array                        $data                       Component Data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        EavValidationRules $eavValidationRules,
        OptimizerCollectionFactory $optimizerCollectionFactory,
        StoreManagerInterface $storeManager,
        Registry $registry,
        Config $eavConfig,
        RequestInterface $request,
        OptimizerRepositoryInterface $optimizerRepository,
        array $meta = [],
        array $data = []
    ) {
        $this->eavValidationRules = $eavValidationRules;

        $this->collection = $optimizerCollectionFactory->create();

        $this->eavConfig = $eavConfig;
        $this->registry = $registry;
        $this->request = $request;
        $this->optimizerRepository = $optimizerRepository;
        $this->storeManager = $storeManager;

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);

//        $this->meta = $this->prepareMeta($this->meta);
    }

    /**
     * Get Component data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $optimizer = $this->getCurrentOptimizer();

        if ($optimizer) {
            $optimizerData = $optimizer->getData();
            $optimizerData = $this->filterFields($optimizerData);
            if (!empty($optimizerData)) {
                $this->loadedData[$optimizer->getId()] = $optimizerData;
            }
        }

        return $this->loadedData;
    }

    /**
     * Prepare meta data
     *
     * @param array $meta The meta data
     *
     * @return array
     */
//    private function prepareMeta($meta)
//    {
//        $meta = array_replace_recursive(
//            $meta,
//            $this->prepareFieldsMeta(
//                $this->getFieldsMap(),
//                $this->getAttributesMeta($this->eavConfig->getEntityType(SellerInterface::ENTITY))
//            )
//        );
//
//        return $meta;
//    }

    /**
     * Get attributes meta
     *
     * @param Type $entityType The entity type
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
//    private function getAttributesMeta(Type $entityType)
//    {
//        $meta = [];
//        $attributes = $entityType->getAttributeCollection();
//
//        /* @var \Smile\Seller\Model\ResourceModel\Seller\Attribute $attribute */
//        foreach ($attributes as $attribute) {
//            $code = $attribute->getAttributeCode();
//            // Use getDataUsingMethod, since some getters are defined and apply additional processing of returning value.
//            foreach ($this->metaProperties as $metaName => $origName) {
//                $value = $attribute->getDataUsingMethod($origName);
//                $meta[$code][$metaName] = $value;
//
//                if ('frontend_input' === $origName) {
//                    $meta[$code]['formElement'] = isset($this->formElement[$value]) ? $this->formElement[$value] : $value;
//                }
//
//                if ($attribute->usesSource()) {
//                    $meta[$code]['options'] = $attribute->getSource()->getAllOptions();
//                }
//            }
//
//            $rules = $this->eavValidationRules->build($attribute, $meta[$code]);
//            if (!empty($rules)) {
//                $meta[$code]['validation'] = $rules;
//            }
//
//            $meta[$code]['scopeLabel'] = $this->getScopeLabel($attribute);
//            $meta[$code]['componentType'] = Field::NAME;
//        }
//
//        $result = [];
//        foreach ($meta as $key => $item) {
//            $result[$key] = $item;
//            $result[$key]['sortOrder'] = 0;
//        }
//
//        return $result;
//    }

    /**
     * Get current optimizer
     *
     * @return Optimizer
     * @throws NoSuchEntityException
     */
    private function getCurrentOptimizer()
    {
        $seller = $this->registry->registry('current_optimizer');
        if ($seller) {
            return $seller;
        }

        $requestId    = $this->request->getParam($this->requestFieldName);

        if ($requestId) {
            $optimizer = $this->optimizerRepository->getById($requestId);
        }

        if (!$optimizer || !$optimizer->getId()) {
            $optimizer = $this->collection->getNewEmptyItem();
        }

        return $optimizer;
    }

    /**
     * Filter fields
     *
     * @param array $sellerData The seller data
     *
     * @return array
     */
    private function filterFields($sellerData)
    {
        return array_diff_key($sellerData, array_flip($this->ignoreFields));
    }

    /**
     * @return array
     */
    private function getFieldsMap()
    {
        return [
            'general' => [
                'seller_code',
                'name',
                'is_active',
            ],
        ];
    }

    /**
     * Prepare fields meta based on xml declaration of form and fields metadata
     *
     * @param array $fieldsMap  The field Map
     * @param array $fieldsMeta The fields meta
     *
     * @return array
     */
    private function prepareFieldsMeta($fieldsMap, $fieldsMeta)
    {
        $result = [];
        foreach ($fieldsMap as $fieldSet => $fields) {
            foreach ($fields as $field) {
                if (isset($fieldsMeta[$field])) {
                    $result[$fieldSet]['children'][$field]['arguments']['data']['config'] = $fieldsMeta[$field];
                }
            }
        }

        return $result;
    }
}
