<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types = 1);

namespace Smile\ElasticsuiteCore\Model\Config\Source;

use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;

/**
 * Shingle analyzer tokenizer config source model.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 */
class ShingleTokenizer implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritDoc}
     */
    public function toOptionArray()
    {
        return [
            ['value' => FieldInterface::TOKENIZER_WHITESPACE, 'label' => __('whitespace (legacy)')],
            ['value' => FieldInterface::TOKENIZER_STANDARD, 'label' => __('standard')],
        ];
    }
}
