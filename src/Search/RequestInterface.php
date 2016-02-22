<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Search;

use Smile\ElasticSuiteCore\Search\Request\QueryInterface;
use Smile\ElasticSuiteCore\Search\Request\SortOrderInterface;

interface RequestInterface extends \Magento\Framework\Search\RequestInterface
{
    /**
     * @return string
     */
    public function getType();

    /**
     * @return QueryInterface
     */
    public function getFilter();

    /**
     * @return SortOrderInterface
     */
    public function getSortOrder();
}