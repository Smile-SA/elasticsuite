<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\Elasticsuite
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Model\Event\Mapping;

use Smile\ElasticsuiteCore\Api\Index\IndexInterface;

/**
 * Mapping enforcer.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 */
class Enforcer
{
    /**
     * @var TypeEnforcerCollector
     */
    private $typeEnforcerCollector;

    /**
     * Enforcer constructor.
     *
     * @param TypeEnforcerCollector $typeEnforcerCollector Type enforcers collector.
     */
    public function __construct(TypeEnforcerCollector $typeEnforcerCollector)
    {
        $this->typeEnforcerCollector = $typeEnforcerCollector;
    }

    /**
     * Enforces an index mapping on given data.
     *
     * @param IndexInterface $index Index whose mapping to enforce on data.
     * @param array          $data  Data to enforce mapping on.
     *
     * @return array
     */
    public function enforce($index, $data)
    {
        $enforcers = $this->typeEnforcerCollector->getTypeEnforcers($index);
        foreach ($enforcers as $enforcer) {
            $data = $enforcer->enforce($data);
        }

        return $data;
    }
}
