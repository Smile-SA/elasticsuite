<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Block\CatalogSearch\Result;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\AbstractBlock;

/**
 * Block to handle search results cache tags.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Cache extends AbstractBlock implements IdentityInterface
{
    /**
     * Cache tag that will be applied to popular search results (they are cached by Magento).
     */
    const POPULAR_SEARCH_CACHE_TAG = 'es_pop'; // Short name style like Magento does (cat_c, cat_p, etc...).

    /**
     * @var \Magento\Framework\App\ResponseInterface
     */
    private $response;

    /**
     * Cache constructor.
     *
     * @param \Magento\Framework\View\Element\Context  $context  Block Context
     * @param \Magento\Framework\App\ResponseInterface $response HTTP Response
     * @param array                                    $data     Data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Magento\Framework\App\ResponseInterface $response,
        array $data = []
    ) {
        $this->response = $response;
        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentities()
    {
        $identities = [];

        if ($this->isPageCacheable()) {
            $identities[] = self::POPULAR_SEARCH_CACHE_TAG;
        }

        return $identities;
    }

    /**
     * Check if current page is cacheable
     *
     * @return bool
     */
    public function isPageCacheable()
    {
        $result = false;
        $pragma = $this->response->getHeader('pragma');

        if ($pragma) {
            $result = $pragma->getFieldValue() === 'cache';
        }

        return $result;
    }
}
