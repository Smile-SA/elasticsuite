<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Plugin\Index;

use Smile\ElasticsuiteCore\Block\Adminhtml\Form\Field\Renderer\DynamicColumn;
use Smile\ElasticsuiteThesaurus\Model\Index as ThesaurusIndex;

/**
 * Indices List plugin. Add thesaurus index to the indices list.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class IndicesListPlugin
{
    /**
     * Add thesaurus index type to the indices list.
     *
     * @param DynamicColumn $subject Plugin subject.
     * @param array         $result  Result list.
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetList($subject, $result)
    {
        $thesaurusIndexIdentifier = ThesaurusIndex::INDEX_IDENTIER;
        $result[] = $thesaurusIndexIdentifier;
        sort($result);

        return $result;
    }
}
