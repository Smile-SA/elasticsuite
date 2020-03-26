<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Model\Event\Mapping;

use Smile\ElasticsuiteCore\Api\Index\IndexInterface;

/**
 * Type enforcers collector.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 */
class TypeEnforcerCollector
{
    /**
     * @var array
     */
    private $factories;

    /**
     * @var array
     */
    private $cache;

    /**
     * Enforcer constructor.
     *
     * @param array $factories Type enforcer factories.
     */
    public function __construct($factories = [])
    {
        $this->factories = $factories;
        $this->cache = [];
    }

    /**
     * Collects type enforcers for a given index mapping.
     *
     * @param IndexInterface $index Index.
     *
     * @return TypeEnforcerInterface[]
     */
    public function getTypeEnforcers(IndexInterface $index)
    {
        if (!array_key_exists($index->getIdentifier(), $this->cache)) {
            $enforcers = [];
            foreach ($this->factories as $enforcerFactory) {
                $enforcers[] = $enforcerFactory->create(['mapping' => $index->getMapping()]);
            }
            $this->cache[$index->getIdentifier()] = $enforcers;
        }

        return $this->cache[$index->getIdentifier()];
    }
}
