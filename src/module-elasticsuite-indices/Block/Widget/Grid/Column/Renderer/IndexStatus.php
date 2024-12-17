<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteIndices
 * @author    Dmytro ANDROSHCHUK <dmand@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteIndices\Block\Widget\Grid\Column\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;

/**
 * Widget Grid Column Renderer: IndexStatus
 *
 * @category Smile
 * @package  Smile\ElasticsuiteIndices
 * @author   Dmytro ANDROSHCHUK <dmand@smile.fr>
 */
class IndexStatus extends AbstractRenderer
{
    private const SEVERITY_NOTICE = 'notice';
    private const SEVERITY_EXTERNAL = 'external';
    private const SEVERITY_UNDEFINED = 'undefined';
    private const SEVERITY_MINOR = 'minor';
    private const SEVERITY_CRITICAL = 'critical';

    public const LIVE_STATUS = 'live';
    public const REBUILDING_STATUS = 'rebuilding';
    public const GHOST_STATUS = 'ghost';
    public const EXTERNAL_STATUS = 'external';
    public const CLOSED_STATUS = 'closed';
    public const UNDEFINED_STATUS = 'undefined';

    /**
     * @var array
     */
    private $severityMapping = [
        self::LIVE_STATUS => self::SEVERITY_NOTICE,
        self::REBUILDING_STATUS => self::SEVERITY_MINOR,
        self::GHOST_STATUS => self::SEVERITY_CRITICAL,
        self::EXTERNAL_STATUS => self::SEVERITY_EXTERNAL,
        self::CLOSED_STATUS => self::SEVERITY_MINOR,
        self::UNDEFINED_STATUS => self::SEVERITY_UNDEFINED,
    ];

    /**
     * @var string
     */
    protected $fieldName = 'index_status';

    /**
     * @inheritdoc
     * @param DataObject $row DataObject.
     * @return string
     */
    public function render(DataObject $row): string
    {
        $value = $this->getValue($row, $this->fieldName);
        $severity = $this->getSeverityFromValue($value);

        if ($severity === null) {
            return $value;
        }

        return '<span class="grid-severity-' . $severity . '"><span>' . $value . '</span></span>';
    }

    /**
     * Get a value.
     *
     * @param DataObject $row Data object.
     * @param string     $key Key.
     * @return mixed
     */
    protected function getValue(DataObject $row, $key)
    {
        $value = $row->getData($key);
        $value = strtolower($value);

        return $value;
    }

    /**
     * Get a severity from value.
     *
     * @param string $value Value.
     * @return string
     */
    protected function getSeverityFromValue($value): string
    {
        return $this->severityMapping[$value] ?? '';
    }
}
