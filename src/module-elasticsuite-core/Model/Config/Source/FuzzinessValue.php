<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Model\Config\Source;

/**
 * Fuzziness value config source model.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class FuzzinessValue implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritDoc}
     */
    public function toOptionArray()
    {
        return [
            'AUTO' => __('Automatic'),
            '1'    => 1,
            '2'    => 2,
        ];
    }
}
