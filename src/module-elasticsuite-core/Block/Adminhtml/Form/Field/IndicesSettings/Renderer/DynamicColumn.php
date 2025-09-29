<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Block\Adminhtml\Form\Field\IndicesSettings\Renderer;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface;

/**
 * HTML select element block with Elasticsuite index types as options.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class DynamicColumn extends Select
{
    /**
     * @var \Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface
     */
    private $indexSettings;

    /**
     * @var array
     */
    private $specialIndices;

    /**
     * IndicesList constructor
     *
     * @param Context                $context        Context.
     * @param IndexSettingsInterface $indexSettings  Indices Settings.
     * @param array                  $specialIndices Special Indices names, if any.
     * @param array                  $data           Data.
     */
    public function __construct(
        Context $context,
        IndexSettingsInterface $indexSettings,
        $specialIndices = [],
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->indexSettings  = $indexSettings;
        $this->specialIndices = $specialIndices;
    }

    /**
     * Set "name" for <select> element.
     *
     * @param string $value Name of element.
     *
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Set "id" for <select> element.
     *
     * @param string $value Id of element.
     *
     * @return $this
     */
    public function setInputId($value)
    {
        return $this->setId($value);
    }

    /**
     * Render block HTML.
     *
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }

        return parent::_toHtml();
    }

    /**
     * Get list of index types managed by Smile Elasticsuite.
     *
     * @return array
     */
    public function getList()
    {
        $list = array_keys($this->indexSettings->getIndicesConfig());
        foreach ($this->specialIndices as $indexType) {
            $list[] = $indexType;
        }

        return $list;
    }

    /**
     * Retrieve source options.
     *
     * @return array
     */
    private function getSourceOptions(): array
    {
        $indexTypes = $this->getList();

        // Add an empty option as the first element.
        $options[] = [
            'value' => '?',
            'label' => '',
        ];

        foreach ($indexTypes as $indexType) {
            $options[] = [
                'value' => $indexType,
                'label' => $indexType,
            ];
        }

        return $options;
    }
}
