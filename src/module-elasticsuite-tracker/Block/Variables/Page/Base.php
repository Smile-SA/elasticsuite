<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteTracker\Block\Variables\Page;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Layout\PageType\Config as PageTypeConfig;
use Smile\ElasticsuiteTracker\Helper\Data as TrackerHelper;

/**
 * Base variables block for page tracking, exposes all base tracking variables
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Base extends AbstractBlock
{
    /**
     * @var PageTypeConfig The page type configuration
     */
    private $pageTypeConfig;

    /**
     * Magento Locale Resolver
     *
     * @var ResolverInterface
     */
    protected $localeResolver;

    /**
     * @var RequestInterface
     */
    protected $requestInterface;

    /**
     * Set the default template for page variable blocks
     *
     * @param Template\Context  $context          The template context
     * @param Data              $jsonHelper       The Magento's JSON Helper
     * @param TrackerHelper     $trackerHelper    The Smile Tracker helper
     * @param Registry          $registry         Magento Core Registry
     * @param PageTypeConfig    $pageTypeConfig   The page type configuration
     * @param ResolverInterface $localeResolver   Locale Resolver
     * @param RequestInterface  $requestInterface RequestInterface
     * @param array             $data             The block data
     */
    public function __construct(
        Template\Context $context,
        Data $jsonHelper,
        TrackerHelper $trackerHelper,
        Registry $registry,
        PageTypeConfig $pageTypeConfig,
        ResolverInterface $localeResolver,
        RequestInterface $requestInterface,
        array $data = []
    ) {
        $this->pageTypeConfig = $pageTypeConfig;
        $this->localeResolver = $localeResolver;
        $this->requestInterface = $requestInterface;

        parent::__construct($context, $jsonHelper, $trackerHelper, $registry, $data);
    }

    /**
     * Append the page type data to the tracked variables list
     *
     * @return array
     */
    public function getVariables()
    {
        return array_merge(
            $this->getPageTypeInformations(),
            $this->getPageInformation()
        );
    }

    /**
     * List of the page type data
     *
     * @return array
     */
    public function getPageTypeInformations()
    {
        return [
            'type.identifier' => $this->getPageTypeIdentifier(),
            'type.label'      => stripslashes($this->getPageTypeLabel()),
        ];
    }

    /**
     * Page type identifier built from route (ex: catalog/product/view => catalog_product_view)
     *
     * @return string
     */
    public function getPageTypeIdentifier()
    {
        $request = $this->getRequest();

        return $request->getModuleName() . '_' . $request->getControllerName() . '_' . $request->getActionName();
    }

    /**
     * Human readable version of the page
     *
     * @return string
     */
    public function getPageTypeLabel()
    {
        if (!$this->getData('page_type_label')) {
            $label             = '';
            $identifier        = $this->getPageTypeIdentifier();
            $labelByIdentifier = $this->getPageTypeLabelMap();

            if (isset($labelByIdentifier[$identifier])) {
                $label = $labelByIdentifier[$identifier];
            }

            $this->setData('page_type_label', $label);
        }

        return $this->getData('page_type_label');
    }

    /**
     * Get telemetry variables.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     *
     * @return array
     */
    private function getPageInformation()
    {
        return [
            'locale' => $this->localeResolver->getLocale(),
        ];
    }

    /**
     * Return the array of page labels from layout indexed by handle names.
     *
     * @return array
     */
    private function getPageTypeLabelMap()
    {
        $labelByIdentifier = [];

        $pageTypes = $this->pageTypeConfig->getPageTypes();
        foreach ($pageTypes as $identifier => $pageType) {
            $labelByIdentifier[$identifier] = $pageType['label'];
        }

        return $labelByIdentifier;
    }
}
