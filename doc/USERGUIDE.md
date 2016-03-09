
# User guide

## Search engine & navigation

TODO : intro.

### **Fulltext search relevance**

Search relevance configuration is editable via a dedicated screen in the back-office.

It can be accessed under the **Stores** menu, via the **Search Relevance** entry.

You can manage several parameters here :

<br/><br/>
##### **Fulltext base settings**

This panel contains base fulltext configuration settings.

![Fulltext base settings](static/fulltext-base-settings.png)

Parameter                    | Default value  | Description
-----------------------------|----------------|------------
Minimum should match         |           100% | The minimum number of terms that should match a fulltext query (except stopwords managed by Cutoff Frequency, see the *CutoffFrequency* part below).<br/> You can look on the [official documentation](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-minimum-should-match.html) for minimum_should_match available values.
Tie breaker                  |              1 | The way to calculate documents scores. <br/> When set to 1, a document score will be the sum of all its fields score.<br/> If set to an arbitrary value of 0.3, document score will be its higher field score **+** the sum of each other fields score * 0.3. <br/><br/> You can refer to [the documentation about tie breaker](https://www.elastic.co/guide/en/elasticsearch/reference/2.2/query-dsl-multi-match-query.html#_literal_tie_breaker_literal).

<br/><br/>
##### **Phrase match configuration**

Phrase matching enables you to apply a boost on documents that contains *some* of your search terms, in the *same position* relative to each others.

E.g : For the query "the little white horse", we will look for documents matchin "little white", "white horse" or "little white horse".

This feature is based on ElasticSearch Shingle Token Filters, for which you can find more documentation here : [ElasticSearch Shingle Token Filter](https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-shingle-tokenfilter.html)

![Phrase match configuration](static/phrasematch-config.png)

Parameter                    | Default value  | Description
-----------------------------|----------------|------------
Enable boost on phrase match |            Yes | Set to "Yes" to enable phrase match.
Phrase match boost value     |             10 | The boost that will be applied on documents considered as matches.

<br/><br/>
##### **Cutoff Frequency**

Cutoff Frequency allows specifying an arbitrary frequency where high frequency terms (above the cutoff) are not scored for each query.
This is used as an **automatic stopwords detection** based on their frequency in index.

You can go further with the official documentation here : [ElasticSearch Cutoff Frequency](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query.html#query-dsl-match-query-cutoff)

![Cutoff Frequency configuration](static/cutoff-frequency-config.png)

Parameter                    | Default value  | Description
-----------------------------|----------------|------------
Cutoff Frequency             |           0.15 | The cutoff frequency value, as a float number between 0 and 1.

<br/><br/>
##### **Fuzziness Configuration**

Fuzzy queries uses a distance algorithm to calculate matching terms within a specified edit distance related to the current search terms.
This is used to **fix misspelled terms in queries**.

See also the official documentation here : [ElasticSearch Fuzzy Query](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-fuzzy-query.html#query-dsl-fuzzy-query)

![Fuzziness Configuration](static/fuzziness-config.png)

Parameter                    | Default value  | Description
-----------------------------|----------------|------------
Enable fuzziness             |           Yes  | Set it to "Yes" to enable fuzzy queries to the engine.
Fuzziness value              |           0.75 | The maximum edit distance for a fuzzy query. More informations in [the fuzzy query documentation](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-fuzzy-query.html#_parameters_7).
Fuzziness Prefix Length      |              1 | The number of initial characters that must not be "fuzzified". More informations in [the fuzzy query documentation](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-fuzzy-query.html#_parameters_7).
Fuzziness Max. expansion     |             10 | Maximum number of terms the fuzzy query will expand to. More informations in [the fuzzy query documentation](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-fuzzy-query.html#_parameters_7).

<br/><br/>
##### **Phonetic Search Configuration**

**This requires the Phonetic Analysis Plugin : [Phonetic Analysis Plugin](https://github.com/elastic/elasticsearch/tree/master/plugins/analysis-phonetic)**

Phonetic search provides a variety of filters that convert tokens to their phonetic representation.

Phonetic search can be also improved with fuzziness. The parameters are used the same way as described above.

Official documentation related to phonetic search : [ElasticSearch Phonetic Search](https://www.elastic.co/guide/en/elasticsearch/plugins/master/analysis-phonetic.html)

![Phonetic Search Configuration](static/phoneticsearch-config.png)

Parameter                             | Default value  | Description
--------------------------------------|----------------|------------
Enable phonetic search                |           Yes  | Set it to "Yes" to enable phonetic search.
Enable fuzziness                      |           Yes  | Set it to "Yes" to enable phonetic fuzziness.
Phonetic fuzziness value              |            0.5 | The maximum edit distance for a phonetic fuzzy query. More informations in [the fuzzy query documentation](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-fuzzy-query.html#_parameters_7).
Phonetic fuzziness Prefix Length      |              1 | The number of initial characters that must not be "fuzzified". More informations in [the fuzzy query documentation](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-fuzzy-query.html#_parameters_7).
Phonetic fuzziness Max. expansion     |             10 | Maximum number of terms the phonetic fuzzy query will expand to. More informations in [the fuzzy query documentation](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-fuzzy-query.html#_parameters_7).

<br/><br/>
##### **Per Attribute Relevance**

For each product attribute, on the attribute edition page on the Magento back-office, several parameters can be fine tuned to fit your needs :

![Per attribute relevance](static/attribute-relevance-config.png)

Parameter                    | Default value  | Description
-----------------------------|----------------|------------
Used in autocomplete         |            Yes | If the attribute values are used to build the autocomplete results.
Used in spellcheck           |            Yes | If the values of this attributes will be candidate for spellchecking when processing a search query.

<br/><br/>
#### **Faceting configuration**

Each product attribute can also be configured when it is used as a facet in the search results and/or category view page.

On the attribute edition page in Magento back-office, following parameters can be modified :

![Facet configuration](static/facet-config.png)

Parameter                    | Default value  | Description
-----------------------------|----------------|------------
Facet coverage rate          |             90 | The minimum coverage rates of results by this attribute. <br/> Example, if set to 90% on the "Brand" attribute, the facet will be displayed only if 90% of the products in the search results or category have a brand.
Facet max. size              |             10 | The maximum number of values that will be displayed for this facet.
Facet sort order             |   Result count | This is how the facet values will be ordered. <br/><br/> <ul><li>**Result count** : Will order values according to their number of results (descending).</li><li> **Admin sort** : Values will be displayed as ordered in attribute's options values in Magento back-office.</li><li> **Name** : Values will be displayed alphabetically (ascending).</li><li> **Relevance** : Values are displayed by relevance.</li></ul>
