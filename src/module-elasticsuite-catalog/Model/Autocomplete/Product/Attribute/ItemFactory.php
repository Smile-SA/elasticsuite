<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\Autocomplete\Product\Attribute;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\UrlInterface;

/**
 * Create an autocomplete item from a product.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class ItemFactory extends \Magento\Search\Model\Autocomplete\ItemFactory
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * Constructor.
     *
     * @param ObjectManagerInterface $objectManager Object manager used to instantiate new item.
     * @param UrlInterface           $urlBuilder    URL Builder
     */
    public function __construct(ObjectManagerInterface $objectManager, UrlInterface $urlBuilder)
    {
        parent::__construct($objectManager);
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data)
    {
        $data['title'] = $data['value'];
        $data['url']   = $this->getUrl($data);
        unset($data['value']);

        return parent::create($data);
    }

    /**
     * Returns autocompelete result URL.
     *
     * @param array $data Autocomplete data.
     *
     * @return string
     */
    private function getUrl(array $data)
    {
        $urlParams = ['q' => $data['value'], $data['attribute_code'] => $data['value']];

        return $this->urlBuilder->getUrl('catalogsearch/result', ['_query' => $urlParams]);
    }
}
