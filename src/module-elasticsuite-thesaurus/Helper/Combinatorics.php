<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteThesaurus
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2022 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteThesaurus\Helper;

/**
 * Combinatorics helper.
 *
 * The major part of this class is inspired by the package Math_Combinatorics (class Math_Combinatorics).
 * Git url of andreekeberg/abby package : https://github.com/andreekeberg/abby
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteThesaurus
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @SuppressWarnings(PHPMD)
 */
class Combinatorics
{
    /**
     * Math_Combinatorics
     *
     * Math_Combinatorics provides the ability to find all combinations and
     * permutations given an set and a subset size.  Associative arrays are
     * preserved.
     *
     * @category   Math
     * @package    Combinatorics
     * @author     David Sanders <shangxiao@php.net>
     * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
     * @version    Release: @package_version@
     * @link       http://pyrus.sourceforge.net/Math_Combinatorics.html
     */

    /**
     * List of pointers that record the current combination.
     *
     * @var array
     */
    private $_pointers = [];

    /**
     * Find all combinations given a set and a subset size.
     *
     * @access public
     *
     * @param array $set         Parent set
     * @param int   $subset_size Subset size
     *
     * @return array An array of combinations
     */
    public function combinations(array $set, $subset_size = null)
    {
        $set_size = count($set);

        if (is_null($subset_size)) {
            $subset_size = $set_size;
        }

        if ($subset_size >= $set_size) {
            return [$set];
        } else {
            if ($subset_size == 1) {
                return array_chunk($set, 1);
            } else {
                if ($subset_size == 0) {
                    return [];
                }
            }
        }

        $combinations    = [];
        $set_keys        = array_keys($set);
        $this->_pointers = array_slice(array_keys($set_keys), 0, $subset_size);

        $combinations[] = $this->_getCombination($set);
        while ($this->_advancePointers($subset_size - 1, $set_size - 1)) {
            $combinations[] = $this->_getCombination($set);
        }

        return $combinations;
    }


    /**
     * Find all permutations given a set and a subset size.
     *
     * @access public
     *
     * @param array $set         Parent set
     * @param int   $subset_size Subset size
     *
     * @return array An array of permutations
     */
    public function permutations(array $set, $subset_size = null)
    {
        $combinations = $this->combinations($set, $subset_size);
        $permutations = [];

        foreach ($combinations as $combination) {
            $permutations = array_merge($permutations, $this->_findPermutations($combination));
        }

        return $permutations;
    }

    /**
     * Recursive function used to advance the list of 'pointers' that record the
     * current combination.
     *
     * @access private
     *
     * @param int $pointer_number The ID of the pointer that is being advanced
     * @param int $limit          Pointer limit
     *
     * @return bool True if a pointer was advanced, false otherwise
     */
    private function _advancePointers($pointer_number, $limit)
    {
        if ($pointer_number < 0) {
            return false;
        }

        if ($this->_pointers[$pointer_number] < $limit) {
            $this->_pointers[$pointer_number]++;

            return true;
        } else {
            if ($this->_advancePointers($pointer_number - 1, $limit - 1)) {
                $this->_pointers[$pointer_number] = $this->_pointers[$pointer_number - 1] + 1;

                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Get the current combination.
     *
     * @access private
     *
     * @param array $set The parent set
     *
     * @return array The current combination
     */
    private function _getCombination($set)
    {
        $set_keys = array_keys($set);

        $combination = [];

        foreach ($this->_pointers as $pointer) {
            $combination[$set_keys[$pointer]] = $set[$set_keys[$pointer]];
        }

        return $combination;
    }

    /**
     * Recursive function to find the permutations of the current combination.
     *
     * @access private
     *
     * @param array $set Current combination set
     *
     * @return array Permutations of the current combination
     */
    private function _findPermutations($set)
    {
        if (count($set) <= 1) {
            return [$set];
        }

        $permutations = [];

        list($key, $val) = $this->arrayShiftAssoc($set);
        $sub_permutations = $this->_findPermutations($set);

        foreach ($sub_permutations as $permutation) {
            $permutations[] = array_merge([$key => $val], $permutation);
        }

        $set[$key] = $val;

        $start_key = $key;

        $key = $this->_firstKey($set);
        while ($key != $start_key) {
            list($key, $val) = $this->arrayShiftAssoc($set);
            $sub_permutations = $this->_findPermutations($set);

            foreach ($sub_permutations as $permutation) {
                $permutations[] = array_merge([$key => $val], $permutation);
            }

            $set[$key] = $val;
            $key       = $this->_firstKey($set);
        }

        return $permutations;
    }

    /**
     * Associative version of array_shift()
     *
     * @access public
     *
     * @param array $array Reference to the array to shift
     *
     * @return array Array with 1st element as the shifted key and the 2nd
     *               element as the shifted value
     */
    private function arrayShiftAssoc(array &$array)
    {
        foreach ($array as $key => $val) {
            unset($array[$key]);
            break;
        }

        return [$key => $val];
    }

    /**
     * Get the first key of an associative array
     *
     * @param array $array Array to find the first key
     *
     * @access private
     * @return mixed The first key of the given array
     */
    private function _firstKey($array)
    {
        foreach ($array as $key => $val) {
            break;
        }

        return $key;
    }
}
