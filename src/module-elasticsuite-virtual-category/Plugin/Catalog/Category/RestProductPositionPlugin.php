<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2022 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Plugin\Catalog\Category;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Save the category product sorting on REST API call.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class RestProductPositionPlugin
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var string
     */
    const TABLE_NAME = 'smile_virtualcategory_catalog_category_product_position';

    /**
     * @param ProductRepositoryInterface $productRepository  Product repository.
     * @param ResourceConnection         $resourceConnection Resource Connection.
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        ResourceConnection $resourceConnection
    ) {
        $this->productRepository = $productRepository;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param \Magento\Catalog\Api\CategoryLinkRepositoryInterface   $subject     The plugin subject.
     * @param \Closure                                               $proceed     The execute method.
     * @param \Magento\Catalog\Api\Data\CategoryProductLinkInterface $productLink The product data.
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function aroundSave(
        \Magento\Catalog\Api\CategoryLinkRepositoryInterface $subject,
        \Closure $proceed,
        \Magento\Catalog\Api\Data\CategoryProductLinkInterface $productLink
    ) {
        $result = $proceed($productLink);
        $productPosition = (int) $productLink->getPosition();

        if ($productPosition > 0) {
            $product = $this->productRepository->get($productLink->getSku());
            $storeId = \Magento\Store\Model\Store::DEFAULT_STORE_ID;

            $insertData[] = [
                'category_id'    => $productLink->getCategoryId(),
                'product_id'     => $product->getId(),
                'store_id'       => $storeId,
                'position'       => $productLink->getPosition(),
            ];

            try {
                $this->resourceConnection->getConnection()->insertOnDuplicate(
                    $this->resourceConnection->getTableName(self::TABLE_NAME),
                    $insertData,
                    array_keys(current($insertData))
                );
            } catch (\Exception $e) {
                throw new CouldNotSaveException(
                    __(
                        'Could not save product "%1" with position %2 to category %3',
                        $product->getId(),
                        $productLink->getPosition(),
                        $productLink->getCategoryId()
                    ),
                    $e
                );
            }
        }

        return $result;
    }
}
