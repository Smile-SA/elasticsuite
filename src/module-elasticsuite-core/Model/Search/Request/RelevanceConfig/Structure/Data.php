<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Thomas Klein <thomasklein876@gmail.com>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig\Structure;

use Magento\Framework\Config\Data\Scoped;

class Data extends Scoped
{
    public function merge(array $config): void
    {
        parent::merge($config['config']['system'] ?? $config);
    }
}
