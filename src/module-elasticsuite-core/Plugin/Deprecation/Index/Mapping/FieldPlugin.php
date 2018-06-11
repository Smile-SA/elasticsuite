<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Plugin\Deprecation\Index\Mapping;

use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;

/**
 * Implements backward compatibility of mapping fields with ES 2.x.
 *
  *@category Smile_Elasticsuite
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class FieldPlugin
{
    /**
     * @var string
     */
    private $serverVersion;

    /**
     * Constructor.
     *
     * @param \Smile\ElasticsuiteCore\Api\Cluster\ClusterInfoInterface $clusterInfo Cluster information API.
     */
    public function __construct(\Smile\ElasticsuiteCore\Api\Cluster\ClusterInfoInterface $clusterInfo)
    {
        $this->serverVersion = $clusterInfo->getServerVersion();
    }

    /**
     * Fix the ES property into the mapping.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param FieldInterface $field  Field object.
     * @param array          $result ES 5.x property mapping.
     *
     * @return array
     */
    public function afterGetMappingPropertyConfig(FieldInterface $field, $result)
    {
        $result = $this->fixPropertyNorm($result);
        $result = $this->fixFielddata($result);

        if (isset($result['fields'])) {
            $result['fields'] = array_map([$this, 'fixPropertyNorm'], $result['fields']);
            $result['fields'] = array_map([$this, 'fixFielddata'], $result['fields']);
        }

        return $result;
    }

    /**
     * Norms configuration have changed between ES 2.x and ES 5.x.
     * Restore the previous behavior if using ES 2.x.
     *
     * @param array $property Property.
     *
     * @return array
     */
    private function fixPropertyNorm($property)
    {
        if (strcmp($this->serverVersion, "5") < 0 && isset($property['norms']) && is_bool($property['norms'])) {
            $property['norms'] = ['enabled' => $property['norms']];
        }

        return $property;
    }

    /**
     * Fielddata configuration have changed between ES 2.x and ES 5.x.
     * Restore the previous behavior if using ES 2.x.
     *
     * @param array $property Property.
     *
     * @return array
     */
    private function fixFielddata($property)
    {
        if (strcmp($this->serverVersion, "5") < 0 && $property['type'] === FieldInterface::FIELD_TYPE_STRING) {
            $isAnalyzed   = isset($property['index']) && $property['index'] !== 'not_analyzed';
            $useFielddata = isset($property['fielddata']) ? $property['fielddata'] : $isAnalyzed === false;

            if (isset($property['fielddata']) && $useFielddata) {
                unset($property['fielddata']);
            } elseif (!$useFielddata) {
                $property['fielddata'] = ['format' => 'disabled'];
            }
        }

        return $property;
    }
}
