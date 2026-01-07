<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteThesaurus
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2023 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types = 1);

namespace Smile\ElasticsuiteThesaurus\Test\Unit;

/**
 * Manually created Interceptor class for @see \Smile\ElasticsuiteCore\Search\Request\Query\Fulltext\QueryBuilder
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Richard Bayet <richard.bayet@smile.fr>
 *
 * phpcs:ignoreFile
 */
class FulltextQueryBuilderInterceptor extends \Smile\ElasticsuiteCore\Search\Request\Query\Fulltext\QueryBuilder implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    /**
     * Constructor
     *
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory Query factory
     * @param \Smile\ElasticsuiteCore\Helper\Text                       $textHelper   Text helper
     * @param array                                                     $fieldFilters Field filters
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory,
        \Smile\ElasticsuiteCore\Helper\Text $textHelper,
        array $fieldFilters = []
    ) {
        $this->___init();

        parent::__construct($queryFactory, $textHelper, $fieldFilters);
    }

    /**
     * {@inheritdoc}
     */
    public function create(\Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface $containerConfig, $queryText, $spellingType, $boost = 1, $depth = 0)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'create');

        return $pluginInfo ? $this->___callPlugins('create', func_get_args(), $pluginInfo) : parent::create($containerConfig, $queryText, $spellingType, $boost, $depth);
    }
}
