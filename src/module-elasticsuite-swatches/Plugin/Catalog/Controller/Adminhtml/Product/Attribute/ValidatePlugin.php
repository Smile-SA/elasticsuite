<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile\Elasticsuite
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteSwatches\Plugin\Catalog\Controller\Adminhtml\Product\Attribute;

use Smile\ElasticsuiteSwatches\Model\Serialize\Serializer\FormData;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Catalog\Controller\Adminhtml\Product\Attribute;

/**
 * Plugin to force deserialization of product attribute options if in a version < 2.2.6 where it was introduced.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteSwatches
 */
class ValidatePlugin
{
    /**
     * @var FormData
     */
    private $formDataSerializer;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * ValidatePlugin constructor.
     *
     * @param FormData                 $formDataSerializer Form data serializer/deserializer
     * @param ProductMetadataInterface $productMetadata    Product metadata interface
     */
    public function __construct(FormData $formDataSerializer, ProductMetadataInterface $productMetadata)
    {
        $this->formDataSerializer   = $formDataSerializer;
        $this->productMetadata      = $productMetadata;
    }

    /**
     * Before Plugin : if Magento version is < 2.2.6, deserialize attributes options
     * before re-inserting them in the request
     *
     * @param Attribute\Validate $subject Controller
     *
     * @return void
     */
    public function beforeExecute(Attribute\Validate $subject)
    {
        if (version_compare($this->productMetadata->getVersion(), '2.2.6', '<')) {
            try {
                $optionData = $this->formDataSerializer->unserialize(
                    $subject->getRequest()->getParam('serialized_options', '[]')
                );
            } catch (\InvalidArgumentException $e) {
                return;
            }

            $data = $subject->getRequest()->getPostValue();
            unset($data['serialized_options']);
            $data = array_replace_recursive(
                $data,
                $optionData
            );

            $subject->getRequest()->setPostValue($data);
        }
    }
}
