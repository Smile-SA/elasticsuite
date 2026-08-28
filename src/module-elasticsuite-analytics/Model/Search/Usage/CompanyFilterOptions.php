<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteAnalytics
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteAnalytics\Model\Search\Usage;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Smile\ElasticsuiteAnalytics\Model\Report\Context as ReportContext;

/**
 * Options source for the search usage dashboard's company filter field.
 *
 * Only ever returns "All Companies" plus, when one is currently selected, that single company - the
 * field searches for anything else through Controller\Adminhtml\Search\Usage\CompanySearch.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 */
class CompanyFilterOptions implements OptionSourceInterface
{
    /**
     * @var ReportContext
     */
    private $reportContext;

    /**
     * @var \Magento\Company\Api\CompanyRepositoryInterface|null
     */
    private $companyRepository = null;

    /**
     * Constructor.
     *
     * @param ReportContext $reportContext Report context.
     * @param ModuleManager $moduleManager Module manager.
     */
    public function __construct(ReportContext $reportContext, ModuleManager $moduleManager)
    {
        $this->reportContext = $reportContext;

        if ($moduleManager->isEnabled('Magento_Company')
            && interface_exists('\Magento\Company\Api\CompanyRepositoryInterface')
        ) {
            $this->companyRepository = ObjectManager::getInstance()->get(
                \Magento\Company\Api\CompanyRepositoryInterface::class
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $options = [['value' => '', 'label' => __('All Companies')]];

        $companyId = $this->reportContext->getCustomerCompanyId();
        if ($companyId && $companyId !== 'all' && $this->companyRepository !== null) {
            try {
                $company = $this->companyRepository->get($companyId);
                $options[] = ['value' => $company->getId(), 'label' => $company->getCompanyName()];
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                ;
            }
        }

        return $options;
    }
}
