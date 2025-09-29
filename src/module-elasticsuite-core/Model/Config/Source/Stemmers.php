<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Model\Config\Source;

use \Smile\ElasticsuiteCore\Index\Analysis\Config as AnalysisConfig;

/**
 * Language stemmers options source model.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 */
class Stemmers implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var AnalysisConfig
     */
    protected $analysisConfig;

    /**
     * Constructor.
     *
     * @param AnalysisConfig $analysisConfig Analysis configuration.
     */
    public function __construct(AnalysisConfig $analysisConfig)
    {
        $this->analysisConfig = $analysisConfig;
    }

    /**
     * {@inheritDoc}
     */
    public function toOptionArray()
    {
        $options = [
            [
                'label' => __('Please select a stemmer for the store'),
                'value' => '',
            ],
        ];

        $stemmerConfig = $this->analysisConfig->get('default/stemmers') ?? [];
        foreach ($stemmerConfig as $languageCode => $languageConfig) {
            $languageLabel = $languageConfig['title'] ?? $languageCode;
            $stemmerOptions = [];
            foreach ($languageConfig['stemmers'] ?? [] as $stemmer) {
                $identifier = $stemmer['identifier'] ?? false;
                if (!empty($identifier)) {
                    $stemmerOptions[] = [
                        'value' => $identifier,
                        'label' => $this->getStemmerOptionLabel($stemmer) ?? $identifier,
                    ];
                }
            }
            if (!empty($stemmerOptions)) {
                $options[] = [
                    'label' => $languageLabel,
                    'value' => $stemmerOptions,
                ];
            }
        }

        return $options;
    }

    /**
     * Get stemmer option label.
     *
     * @param array $stemmer Stemmer definition.
     *
     * @return string
     */
    protected function getStemmerOptionLabel($stemmer)
    {
        $labelElements = [];

        $labelElements[] = $stemmer['identifier'] ?? '';
        if (!empty($stemmer['label'])) {
            $labelElements[] = sprintf("(%s)", $stemmer['label']);
        }
        if ($stemmer['default'] ?? false) {
            $labelElements[] = '[default]';
        }
        if ($stemmer['recommended'] ?? false) {
            $labelElements[] = '[recommended]';
        }

        $labelElements = array_filter($labelElements);

        return implode(' ', $labelElements);
    }
}
