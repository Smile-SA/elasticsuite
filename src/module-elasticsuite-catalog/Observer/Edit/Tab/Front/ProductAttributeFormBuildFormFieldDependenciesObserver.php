<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Observer\Edit\Tab\Front;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Overriding the product attribute edit form prepare field observer introduced in 2.4.7
 * to address the weird logic of ACP2E-1854.
 * (see https://github.com/magento/magento2/commit/40b649f1c392fa6e075e76302b4077db0eb48f32)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @SuppressWarnings(PHPMD.LongClassName)
 */
class ProductAttributeFormBuildFormFieldDependenciesObserver implements ObserverInterface
{
    /**
     * Do nothing. (That observer is supposed to be disabled in config anyway.)
     * This is to replace the observer introduced for 2.4.7 for fixing ACP2E-1854 (which we don't need to fix).
     *
     * @param Observer $observer Observer.
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(Observer $observer)
    {
        ;
    }
}
