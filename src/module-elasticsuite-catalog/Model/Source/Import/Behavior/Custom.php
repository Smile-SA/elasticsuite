<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace  Smile\ElasticsuiteCatalog\Model\Source\Import\Behavior;

use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Source\Import\AbstractBehavior;

/**
 * Custom import behavior source model used for defining the behavior during the product attributes import.
 *
 * @api
 * @since 100.0.2
 */
class Custom extends AbstractBehavior
{
    /**
     * @inheritdoc
     */
    public function toArray()
    {
        return [
            Import::BEHAVIOR_APPEND => __('Update')
        ];
    }

    /**
     * @inheritdoc
     */
    public function getCode()
    {
        return 'custom';
    }
}
