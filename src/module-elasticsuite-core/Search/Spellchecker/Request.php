<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Spellchecker;

use Smile\ElasticsuiteCore\Api\Search\Spellchecker\RequestInterface;

/**
 * DISCLAIMER
 *
 * Spellchecker request implementation
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Request implements RequestInterface
{
    /**
     * @var string
     */
    private $index;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $queryText;

    /**
     * @var float
     */
    private $cufoffFrequency;

    /**
     * Constructor.
     *
     * @param string $index           Spellcheck request index name.
     * @param string $type            Spellcheck request document type.
     * @param string $queryText       Spellcheck fulltext query.
     * @param float  $cutoffFrequency Spellcheck cutoff frequency (used to detect stopwords).
     */
    public function __construct($index, $type, $queryText, $cutoffFrequency)
    {
        $this->index           = $index;
        $this->type            = $type;
        $this->queryText       = $queryText;
        $this->cufoffFrequency = $cutoffFrequency;
    }

    /**
     * {@inheritDoc}
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * {@inheritDoc}
     */
    public function getQueryText()
    {
        return $this->queryText;
    }

    /**
     * {@inheritDoc}
     */
    public function getCutoffFrequency()
    {
        return $this->cufoffFrequency;
    }
}
