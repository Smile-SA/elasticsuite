<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Plugin\Controller\Version;

use Magento\Framework\App\ResponseInterface;
use Smile\ElasticsuiteCore\Model\ProductMetadata;

/**
 * Composer Information model.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class IndexPlugin
{
    /**
     * @var \Smile\ElasticsuiteCore\Model\ProductMetadata
     */
    private $productMetadata;

    /**
     * @var \Magento\Framework\App\Response\HttpFactory
     */
    private $httpFactory;

    /**
     * @param \Smile\ElasticsuiteCore\Model\ProductMetadata $productMetadata Product Metadata
     * @param \Magento\Framework\App\Response\HttpFactory   $httpFactory     HTTP Factory
     */
    public function __construct(
        ProductMetadata $productMetadata,
        \Magento\Framework\App\Response\HttpFactory $httpFactory
    ) {
        $this->productMetadata = $productMetadata;
        $this->httpFactory     = $httpFactory;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param \Magento\Version\Controller\Index\Index                         $subject The legacy controller
     * @param \Magento\Framework\Controller\ResultInterface|ResponseInterface $result  The legacy result
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     */
    public function afterExecute(\Magento\Version\Controller\Index\Index $subject, $result)
    {
        if ((method_exists($result, 'setContents')) && (method_exists($result, 'renderResult'))) {
            try {
                $dummyResponse = $this->httpFactory->create();
                $result->renderResult($dummyResponse);

                $content = $dummyResponse->getBody() .
                    " with " .
                    $this->productMetadata->getName() . '/' .
                    $this->productMetadata->getVersion() .
                    ' (' . $this->productMetadata->getEdition() . ')';

                $result->setContents($content);
            } catch (\Exception $exception) {
                ; // Do nothing, we don't want to break legacy Magento here.
            }
        }

        return $result;
    }
}
