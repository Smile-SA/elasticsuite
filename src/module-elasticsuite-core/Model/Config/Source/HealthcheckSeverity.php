<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types = 1);

namespace Smile\ElasticsuiteCore\Model\Config\Source;

use Smile\ElasticsuiteCore\Api\Healthcheck\CheckInterface;

/**
 * Healthcheck severity value config source model.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 */
class HealthcheckSeverity implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritDoc}
     */
    public function toOptionArray()
    {
        $options = [];
        foreach (CheckInterface::SEVERITY_LABELS as $value => $label) {
            $options[] = ['value' => $value, 'label' => __($label)];
        }

        return $options;
    }
}
