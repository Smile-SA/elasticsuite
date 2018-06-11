<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig;

/**
 * Relevance Configuration Reader Pool.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ReaderPool
{
    /**
     * List of readers
     *
     * @var array
     */
    private $readers = [];

    /**
     * @param \Magento\Framework\App\Config\Scope\ReaderInterface[] $readers The readers
     */
    public function __construct(array $readers)
    {
        $this->readers = $readers;
    }

    /**
     * Retrieve reader by scope type
     *
     * @param string $scopeType The scope
     *
     * @return mixed
     */
    public function getReader($scopeType)
    {
        return $this->readers[$scopeType];
    }
}
