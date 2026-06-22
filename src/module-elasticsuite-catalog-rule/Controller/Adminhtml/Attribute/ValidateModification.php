<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogRule
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types = 1);

namespace Smile\ElasticsuiteCatalogRule\Controller\Adminhtml\Attribute;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Smile\ElasticsuiteCatalogRule\Model\Attribute\AttributeModificationValidator;

/**
 * Async API endpoint used by admin UI to validate attribute changes before saving.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class ValidateModification extends Action
{
    /**
     * Authorization level of a basic admin session.
     */
    public const ADMIN_RESOURCE = 'Magento_Catalog::attributes_attributes';

    /**
     * @var JsonFactory
     */
    private JsonFactory $resultJsonFactory;

    /**
     * @var AttributeFactory
     */
    private AttributeFactory $attributeFactory;

    /**
     * @var AttributeModificationValidator
     */
    private AttributeModificationValidator $validator;

    /**
     * Constructor.
     *
     * @param Context                        $context           Action context data.
     * @param JsonFactory                    $resultJsonFactory Result Json factory.
     * @param AttributeFactory               $attributeFactory  Attribute factory.
     * @param AttributeModificationValidator $validator         Attribute modification validator.
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        AttributeFactory $attributeFactory,
        AttributeModificationValidator $validator
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->attributeFactory = $attributeFactory;
        $this->validator = $validator;
    }

    /**
     * Execute AJAX validation.
     *
     * @return Json
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();
        $attributeId = $this->getRequest()->getParam('attribute_id');

        if (empty($attributeId)) {
            return $resultJson->setData(['allowed' => true, 'message' => '']);
        }

        try {
            // Load the attribute model to extract its actual code identifier.
            $attribute = $this->attributeFactory->create()->load((int) $attributeId);
            $attributeCode = $attribute->getAttributeCode();

            if (empty($attributeCode)) {
                return $resultJson->setData(['allowed' => true, 'message' => '']);
            }

            $allowed = $this->validator->canBeModified((string) $attributeCode);

            $message = '';
            if (!$allowed) {
                $message = $this->validator->getBlockedMessage();
            }

            return $resultJson->setData([
                'allowed' => $allowed,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData(['allowed' => true, 'message' => '']);
        }
    }
}
