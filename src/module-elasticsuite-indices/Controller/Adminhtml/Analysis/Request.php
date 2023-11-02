<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteIndices
 * @author    Vadym HONCHARUK <vahonc@smile.fr>
 * @copyright 2022 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteIndices\Controller\Adminhtml\Analysis;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Smile\ElasticsuiteCore\Api\Client\ClientInterface;

/**
 * ElasticSuite Indices Adminhtml Analysis Request Controller.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteIndices
 * @author   Vadym HONCHARUK <vahonc@smile.fr>
 */
class Request extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @param ClientInterface $client            ES client.
     * @param JsonFactory     $resultJsonFactory Result Json Factory.
     * @param Context         $context           Context.
     *
     */
    public function __construct(
        ClientInterface $client,
        JsonFactory $resultJsonFactory,
        Context $context
    ) {
        $this->client = $client;
        $this->resultJsonFactory = $resultJsonFactory;

        parent::__construct($context);
    }

    /**
     * @inheritDoc
     *
     * @return ResponseInterface|Json|ResultInterface
     */
    public function execute()
    {
        $errors = [];

        if ($this->getRequest()->getPost('index', null) == '0') {
            $errors['index'] = __('Index not selected!');
        } elseif ($this->getRequest()->getPost('analyzer', null) == '0') {
            $errors['analyzer'] = __('Analyzer not selected!');
        }

        if (!empty($errors)) {
            $result['errors'] = $errors;

            $result = $this->resultJsonFactory->create();
            $result->setData(['success' => false, 'output' => $errors]);

            return $result;
        }

        $postData = $this->getRequest()->getPostValue();
        unset($postData['form_key']);
        $responseData = $this->getAnalyzeRequest($postData);

        $result = $this->resultJsonFactory->create();
        $result->setData(['success' => true, 'output' => $responseData]);

        return $result;
    }

    /**
     * Run an Analyze Request using ElasticSearch.
     *
     * @param array $params Analyze params.
     *
     * @return array
     */
    public function getAnalyzeRequest($params)
    {
        return $this->client->analyze(
            [
                'index' => $params['index'],
                'body' => [
                    'text' => $params['text'],
                    'analyzer' => $params['analyzer'],
                ],
            ]
        );
    }
}
