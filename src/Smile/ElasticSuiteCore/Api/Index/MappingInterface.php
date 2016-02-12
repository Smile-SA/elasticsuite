<?php

namespace Smile\ElasticSuiteCore\Api\Index;

interface MappingInterface
{
    const DEFAULT_SEARCH_FIELD       = 'search';
    const DEFAULT_SPELLING_FIELD     = 'spelling';
    const DEFAULT_AUTOCOMPLETE_FIELD = 'autocomplete';

    const ANALYZER_STANDARD   = 'standard';
    const ANALYZER_WHITESPACE = 'whitespace';
    const ANALYZER_SHINGLE    = 'shingle';
    const ANALYZER_SORTABLE   = 'sortable';
    const ANALYZER_EDGE_NGRAM = 'edge_ngram_front';
    const ANALYZER_UNTOUCHED  = 'untouched';

    /**
     * @return array
     */
    public function getProperties();


    /**
     * @return \Smile\ElasticSuiteCore\Api\Index\Mapping\FieldInterface[]
     */
    public function getFields();

    /**
     * @return array
     */
    public function asArray();
}