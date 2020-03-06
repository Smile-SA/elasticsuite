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

namespace Smile\ElasticsuiteTracker\Model\Event;

use Magento\Framework\DataObject;

/**
 * Variation of the DataObject to support dot notation for
 * - testing existence
 * - getting data
 * - setting data
 *
 * @SuppressWarnings(PHPMD.CountInLoopExpression)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 */
class DotObject extends DataObject
{
    /**
     * Overwrite data in the object.
     *
     * The $key parameter can be string or array.
     * If $key is string, the attribute value will be overwritten by $value
     * If $key is an array, it will overwrite all the data in the object.
     *
     * Added support for "dot" notation, using inspiration from Laravel array_set helper.
     *
     * @SuppressWarnings(PHPMD.CountInLoopExpression)
     *
     * @param string|array $key   Key where to overwrite data.
     * @param mixed        $value Value to set for the given key.
     *
     * @return $this
     */
    public function setData($key, $value = null)
    {
        if ($key === (array) $key) {
            $this->_data = $key;

            return $this;
        }

        $array = &$this->_data;

        $keys = explode('.', $key);
        // @codingStandardsIgnoreStart
        while (count($keys) > 1) {
            $key = array_shift($keys);

            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }
        // @codingStandardsIgnoreEnd

        $array[array_shift($keys)] = $value;

        return $this;
    }

    /**
     * Object data getter
     *
     * If $key is not defined will return all the data as an array.
     * Otherwise it will return value of the element specified by $key.
     * It is possible to use keys like a/b/c for access nested array data
     *
     * If $index is specified it will assume that attribute data is an array
     * and retrieve corresponding member. If data is the string - it will be explode
     * by new line character and converted to array.
     *
     * Added support for "dot" notation.
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @param string     $key   Data key.
     * @param string|int $index Data index.
     * @return mixed
     */
    public function getData($key = '', $index = null)
    {
        if ('' === $key) {
            return $this->_data;
        }

        /* process a/b/c key as ['a']['b']['c'] */
        if (strpos($key, '/') !== false) {
            $data = $this->getDataByPath($key);
        } elseif (strpos($key, '.') !== false) {
            /* process a.b.c key as ['a']['b']['c'] */
            $data = $this->getDataByPath($key, '.');
        } else {
            $data = $this->_getData($key);
        }

        if ($index !== null) {
            if ($data === (array) $data) {
                $data = isset($data[$index]) ? $data[$index] : null;
            } elseif (is_string($data)) {
                $data = explode(PHP_EOL, $data);
                $data = isset($data[$index]) ? $data[$index] : null;
            } elseif ($data instanceof \Magento\Framework\DataObject) {
                $data = $data->getData($index);
            } else {
                $data = null;
            }
        }

        return $data;
    }

    /**
     * Get object data by path
     *
     * Method consider the path as chain of keys: a/b/c => ['a']['b']['c']
     * or a path made using "do" notation: a.b.c => ['a']['b']['c']
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @param string $path      Path to data.
     * @param string $delimiter Path delimiter.
     *
     * @return mixed
     */
    public function getDataByPath($path, $delimiter = '/')
    {
        $keys = explode($delimiter, $path);

        $data = $this->_data;
        foreach ($keys as $key) {
            if ((array) $data === $data && isset($data[$key])) {
                $data = $data[$key];
            } elseif ($data instanceof \Magento\Framework\DataObject) {
                $data = $data->getDataByKey($key);
            } else {
                return null;
            }
        }

        return $data;
    }

    /**
     * If $key is empty, checks whether there's any data in the object
     *
     * Otherwise checks if the specified attribute is set.
     *
     * Added support for "dot" notation.
     *
     * @SuppressWarnings(PHPCS.Squiz.PHP.DisallowSizeFunctionsInLoops)
     * @SuppressWarnings(PHPMD.CountInLoopExpression)
     *
     * @param string $key Data key.
     *
     * @return bool
     */
    public function hasData($key = '')
    {
        if (empty($key) || !is_string($key)) {
            return !empty($this->_data);
        }

        $array = &$this->_data;

        $keys = explode('.', $key);
        // @codingStandardsIgnoreStart
        while (count($keys) > 1) {
            $key = array_shift($keys);

            // If the key doesn't exist at this depth, then the whole structure does not exists.
            if (!isset($array[$key]) || !is_array($array[$key])) {
                return false;
            }

            $array = &$array[$key];
        }
        // @codingStandardsIgnoreEnd

        return array_key_exists(array_shift($keys), $array);
    }
}
