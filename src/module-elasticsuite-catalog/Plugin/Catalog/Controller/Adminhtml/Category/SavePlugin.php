<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Plugin\Catalog\Controller\Adminhtml\Category;

use Magento\Catalog\Controller\Adminhtml\Category\Save;

/**
 * Plugin on Category Save controller that allows to handle additional "use_config" elements.
 * The legacy ones are hardcoded in the controller class...
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class SavePlugin
{
    /**
     * @var array
     */
    private $stringToBoolInputs = [
        'use_config' => ['sort_direction'],
    ];

    /**
     * Constructor
     *
     * @param array $stringToBoolInputs The input names to cast
     */
    public function __construct(array $stringToBoolInputs = [])
    {
        $this->stringToBoolInputs = array_merge_recursive($this->stringToBoolInputs, $stringToBoolInputs);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param \Magento\Catalog\Controller\Adminhtml\Category\Save $subject            Category controller
     * @param array                                               $result             Result
     * @param array                                               $data               Legacy Data
     * @param array                                               $stringToBoolInputs Inputs to be converted
     *
     * @return array
     */
    public function afterStringToBoolConverting(Save $subject, array $result, array $data, ?array $stringToBoolInputs = null)
    {
        return $this->stringToBoolConverting($result);
    }

    /**
     * Copy paste of the legacy method of the category save controller.
     * Copy pasted because the legacy method cannot be called again in a plugin since it's recursive.
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @param array $data               The data
     * @param array $stringToBoolInputs The inputs
     *
     * @return mixed
     */
    private function stringToBoolConverting($data, ?array $stringToBoolInputs = null)
    {
        if (null === $stringToBoolInputs) {
            $stringToBoolInputs = $this->stringToBoolInputs;
        }

        foreach ($stringToBoolInputs as $key => $value) {
            if (is_array($value)) {
                if (isset($data[$key])) {
                    $data[$key] = $this->stringToBoolConverting($data[$key], $value);
                }
            } else {
                if (isset($data[$value])) {
                    if ($data[$value] === 'true') {
                        $data[$value] = true;
                    }
                    if ($data[$value] === 'false') {
                        $data[$value] = false;
                    }
                }
            }
        }

        return $data;
    }
}
