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

namespace Smile\ElasticsuiteTracker\Model\Event\Mapping\TypeEnforcer;

use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticsuiteCore\Api\Index\MappingInterface;
use Smile\ElasticsuiteTracker\Model\Event\DotObject;
use Smile\ElasticsuiteTracker\Model\Event\DotObjectFactory;
use Smile\ElasticsuiteTracker\Model\Event\Mapping\TypeEnforcerInterface;

/**
 * Abstract field type enforcer.
 *
 * @category Smile.
 * @package  Smile\ElasticsuiteTracker
 */
abstract class AbstractTypeEnforcer implements TypeEnforcerInterface
{
    /**
     * @var DotObjectFactory
     */
    protected $dotObjectFactory;

    /**
     * @var MappingInterface
     */
    protected $mapping;

    /**
     * @var FieldInterface[]
     */
    protected $fields;

    /**
     * Integer constructor.
     *
     * @param DotObjectFactory $dataObjectFactory DotObject factory.
     * @param MappingInterface $mapping           Index mapping.
     */
    public function __construct(
        DotObjectFactory $dataObjectFactory,
        MappingInterface $mapping
    ) {
        $this->dotObjectFactory = $dataObjectFactory;
        $this->mapping          = $mapping;
        $this->fields           = [];
        $this->collectFields();
    }

    /**
     * Enforces value type for collected fields.
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @param array $event Event data.
     *
     * @return array
     */
    public function enforce($event)
    {
        if (!empty($this->fields)) {
            $eventData = $this->dotObjectFactory->create(['data' => $event]);

            foreach ($this->fields as $field) {
                if ($field->isNested()) {
                    $this->handleNestedField($eventData, $field);
                } else {
                    $this->handleRegularField($eventData, $field);
                }
            }

            $event = $eventData->getData();
        }

        return $event;
    }

    /**
     * Collect the fields to enforce from the mapping.
     *
     * @return void
     */
    abstract protected function collectFields();

    /**
     * Enforce mapping for a given field value.
     *
     * @param mixed $fieldValue Field value.
     *
     * @return void
     */
    abstract protected function enforceField(&$fieldValue);

    /**
     * Handles a nested field.
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @param DotObject      $eventData Event data.
     * @param FieldInterface $field     Mapping nested field.
     *
     * @return void
     */
    protected function handleNestedField($eventData, $field)
    {
        $fieldPath = $field->getNestedPath();
        if ($eventData->hasData($fieldPath)) {
            $fieldName = $field->getNestedFieldName();
            $items = $eventData->getData($fieldPath);
            if (is_array($items)) {
                foreach ($items as &$item) {
                    if (array_key_exists($fieldName, $item)) {
                        $fieldValue = $item[$fieldName];
                        if (is_array($fieldValue)) {
                            array_walk(
                                $fieldValue,
                                array($this, 'enforceField')
                            );
                        } else {
                            $this->enforceField($fieldValue);
                        }
                        $item[$fieldName] = $fieldValue;
                    }
                }
                $eventData->setData($fieldPath, $items);
            }
        }
    }

    /**
     * Handles a regular field.
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @param DotObject      $eventData Event data.
     * @param FieldInterface $field     Mapping regular field.
     *
     * @return void
     */
    protected function handleRegularField($eventData, $field)
    {
        $fieldName = $field->getName();
        if ($eventData->hasData($fieldName)) {
            $fieldValue = $eventData->getData($fieldName);
            if (is_array($fieldValue)) {
                array_walk(
                    $fieldValue,
                    array($this, 'enforceField')
                );
            } else {
                $this->enforceField($fieldValue);
            }
            $eventData->setData($fieldName, $fieldValue);
        }
    }
}
