<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteSwatches
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteSwatches\Model\Serialize\Serializer;

use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class for processing of serialized form data.
 * Copy of \Magento\Framework\Serialize\Serializer\FormData which became available in 2.2.7
 *
 * @category Smile
 * @package  Smile\ElasticsuiteSwatches
 */
class FormData
{
    /**
     * @var Json
     */
    private $serializer;

    /**
     * FormData constructor
     *
     * @param Json $serializer Json serializer
     */
    public function __construct(Json $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Provides form data from the serialized data.
     *
     * @param string $serializedData Serialized Data
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    public function unserialize(string $serializedData): array
    {
        $encodedFields = $this->serializer->unserialize($serializedData);

        if (!is_array($encodedFields)) {
            throw new \InvalidArgumentException('Unable to unserialize value.');
        }

        $formData = [];
        foreach ($encodedFields as $item) {
            $decodedFieldData = [];
            parse_str($item, $decodedFieldData);
            $formData = array_replace_recursive($formData, $decodedFieldData);
        }

        return $formData;
    }
}
