<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteModuleName
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types = 1);

namespace Smile\ElasticsuiteCatalog\Block\Adminhtml\Product\Attribute\Edit\Renderer;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Psr\Log\LoggerInterface;

/**
 * Generic decorator that wraps any existing form element renderer and appends an extra CSS
 * class to the rendered field wrapper <div>, without altering the decorated renderer's own
 * behavior in any other way.
 *
 * Design notes (why this class looks the way it does):
 *
 * 1. Composition, not inheritance. We implement RendererInterface and accept the renderer to
 *    decorate as a constructor dependency (configured per use case via di.xml virtual types -
 *    see etc/adminhtml/di.xml), instead of extending a concrete renderer class such as
 *    Magento\Backend\Block\Widget\Form\Renderer\Fieldset\Element. This keeps this class
 *    reusable for any field/CSS-class combination, fully unit-testable against a mocked
 *    RendererInterface, and free of any direct reference (and therefore any IDE/static-analysis
 *    deprecation warning) to a specific concrete renderer implementation.
 *
 * 2. ensureNativeRendererHasName() exists to work around a real, confirmed Magento core
 *    interaction bug: Block-based renderers (such as Fieldset\Element) are designed to be
 *    created through the Layout, which assigns them a "name in layout". Constructed via plain
 *    dependency injection instead (as di.xml does here), they never receive one. On PHP 8.1+,
 *    that null name reaches Magento\PricePermissions\Observer\AdminhtmlBlockHtmlBeforeObserver
 *    ::execute(), which passes it to stripos() - now a deprecation that Magento's error handler
 *    escalates into a fatal exception, blanking out the entire tab being rendered.
 *    See:
 *    https://github.com/magento/magento2/issues/35542
 *    https://github.com/magento/magento2/pull/34820 (fixes the sibling Fieldset class, not
 *    Fieldset\Element, so this guard remains necessary here).
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class WrapperCssClassDecorator implements RendererInterface
{
    /**
     * Fallback name assigned to the decorated renderer's layout identity
     * when it doesn't already have one (see ensureNativeRendererHasName()).
     *
     * @var string
     */
    private const FALLBACK_LAYOUT_NAME = 'smile_elasticsuite_wrapper_css_class_decorator';

    /**
     * Renderer being decorated.
     *
     * @var RendererInterface
     */
    private $nativeRenderer;

    /**
     * Used to report when the expected wrapper markup pattern is not found
     * in the decorated renderer's output (see appendWrapperCssClass()).
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ElasticSuite feature logo CSS class to append to the field wrapper.
     * Injected via di.xml so the same decorator class can be reused for
     * different fields/classes without writing a new PHP class each time.
     *
     * @var string
     */
    private $cssClass;

    /**
     * Constructor.
     *
     * @param RendererInterface $nativeRenderer Renderer to decorate.
     * @param LoggerInterface   $logger         Logger used to report unmatched wrapper markup.
     * @param string            $cssClass       CSS class to append to the field wrapper.
     */
    public function __construct(
        RendererInterface $nativeRenderer,
        LoggerInterface $logger,
        string $cssClass
    ) {
        $this->nativeRenderer = $nativeRenderer;
        $this->logger = $logger;
        $this->cssClass = $cssClass;
    }

    /**
     * Render the given form element through the decorated (native) renderer,
     * then append the configured CSS class to its wrapper <div>.
     *
     * @param AbstractElement $element Form element to render.
     *
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        // Guard against the layout-name bug described in the class docblock,
        // before the decorated renderer's own toHtml()/_toHtml() lifecycle
        // (and therefore the adminhtml_block_html_before event) runs.
        $this->ensureNativeRendererHasName();

        $html = $this->nativeRenderer->render($element);

        return $this->appendWrapperCssClass($html, (string) $element->getId());
    }

    /**
     * Defensively assign a "name in layout" to the decorated renderer
     * if it doesn't already have one.
     *
     * @return void
     */
    private function ensureNativeRendererHasName(): void
    {
        if ($this->nativeRenderer instanceof AbstractBlock && !$this->nativeRenderer->getNameInLayout()) {
            $this->nativeRenderer->setNameInLayout(self::FALLBACK_LAYOUT_NAME);
        }
    }

    /**
     * Inject the configured CSS class into the opening wrapper <div>
     * of an already-rendered field, identified by its "field-{elementId}"
     * marker class, without disturbing any other attribute.
     *
     * @param string $html      HTML produced by the decorated (native) renderer.
     * @param string $elementId Target element's id (used to locate the right wrapper tag).
     *
     * @return string
     */
    private function appendWrapperCssClass(string $html, string $elementId): string
    {
        $pattern = '/(class="admin__field field field-' . preg_quote($elementId, '/') . '[^"]*)"/';
        $replacement = '${1} ' . $this->cssClass . '"';

        $replacedHtml = preg_replace($pattern, $replacement, $html, 1, $replacementCount);

        if ($replacementCount === 0) {
            // Expected wrapper markup not found - most likely a future Magento core change to
            // the native field-wrapper markup. Log it instead of failing silently, so a missing
            // CSS class gets noticed and fixed rather than quietly disappearing after an upgrade.
            $this->logger->warning(
                sprintf(
                    'Smile\ElasticsuiteCatalog: unable to inject CSS class "%s" into the wrapper'
                    . ' of form element "%s" - expected markup pattern not found.',
                    $this->cssClass,
                    $elementId
                )
            );

            return $html;
        }

        return $replacedHtml;
    }
}
