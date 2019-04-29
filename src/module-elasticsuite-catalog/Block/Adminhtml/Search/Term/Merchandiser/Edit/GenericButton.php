<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Block\Adminhtml\Search\Term\Merchandiser\Edit;

/**
 * Generic form button.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class GenericButton
{
    /**
     * Url Builder
     *
     * @var \Magento\Framework\UrlInterface
     */
    private $urlBuilder;

    /**
     * Registry
     *
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Widget\Context $context  Block context.
     * @param \Magento\Framework\Registry           $registry Registry.
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry
    ) {
        $this->urlBuilder = $context->getUrlBuilder();
        $this->registry   = $registry;
    }

    /**
     * Return the current query id.
     *
     * @return int|null
     */
    public function getQueryId()
    {
        $query = $this->registry->registry('current_query');

        return $query ? $query->getId() : null;
    }

    /**
     * Generate url by route and parameters
     *
     * @param string $route  Route.
     * @param array  $params Route params.
     *
     * @return  string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->urlBuilder->getUrl($route, $params);
    }

    /**
     * Check where button can be rendered
     *
     * @param string $name Button name.
     *
     * @return string
     */
    public function canRender($name)
    {
        return $name;
    }
}
