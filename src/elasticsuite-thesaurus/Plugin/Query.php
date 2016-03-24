<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile_ElasticSuite
 * @package   Smile_ElasticSuiteThesaurus
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteThesaurus\Plugin;

use Magento\Search\Model\Query as QueryModel;

/**
 * Thesaurus search query model plugin.
 *
 * @category Smile_ElasticSuite
 * @package  Smile_ElasticSuiteThesaurus
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Query
{
    /**
     * Avoid original query rewrite using synonym_for to be applied into the query factory.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param QueryModel $subject Query Model.
     * @param \Closure   $proceed Original function.
     * @param string     $text    Query text.
     *
     * @return \Magento\Search\Model\Query
     */
    public function aroundLoadByQuery(QueryModel $subject, \Closure $proceed, $text)
    {
        return $subject->loadByQueryText($text);
    }
}
