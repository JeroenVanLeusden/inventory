<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryGroupedProduct\Test\Integration\CatalogInventory\Api\StockRegistry;

use Magento\CatalogInventory\Api\Data\StockStatusInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterface;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryCatalog\Model\GetStockIdForCurrentWebsite;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test StockRegistryInterface::getStockStatusBySku() for grouped product type.
 */
class GetStockStatusBySkuTest extends TestCase
{
    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @var GetStockIdForCurrentWebsite
     */
    private $getStockForCurrentWebsite;

    /**
     * @var StockItemRepositoryInterface
     */
    private $stockItemRepository;

    /**
     * @var StockItemCriteriaInterface
     */
    private $stockItemCriteria;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->stockRegistry = Bootstrap::getObjectManager()->get(StockRegistryInterface::class);
        $this->getProductIdsBySkus = Bootstrap::getObjectManager()->get(GetProductIdsBySkusInterface::class);
        $this->getStockForCurrentWebsite = Bootstrap::getObjectManager()->get(GetStockIdForCurrentWebsite::class);
        $this->stockItemRepository = Bootstrap::getObjectManager()->get(StockItemRepositoryInterface::class);
        $this->stockItemCriteria = Bootstrap::getObjectManager()->create(StockItemCriteriaInterface::class);
    }

    /**
     * Check, grouped has correct stock status configuration on default source.
     *
     * @magentoDataFixture Magento/GroupedProduct/_files/product_grouped.php
     * @magentoAppArea frontend
     * @return void
     */
    public function testGetStatusOnDefaultSource()
    {
        $sku = 'grouped-product';
        $stockId = $this->getStockForCurrentWebsite->execute();
        $productIds = $this->getProductIdsBySkus->execute([$sku]);
        $productId = reset($productIds);

        //Check product with 'In Stock' status.
        $stockStatus = $this->stockRegistry->getStockStatusBySku($sku);
        $this->assertInstanceOf(StockStatusInterface::class, $stockStatus);
        $this->assertEquals(1, $stockStatus->getStockStatus());
        $this->assertEquals(0, $stockStatus->getQty());
        $this->assertEquals($stockId, $stockStatus->getStockId());
        $this->assertEquals($productId, $stockStatus->getProductId());

        $this->setProductsOutOfStock((int)$productId);

        //Check product with 'Out of Stock' status.
        $stockStatus = $this->stockRegistry->getStockStatusBySku($sku);
        $this->assertInstanceOf(StockStatusInterface::class, $stockStatus);
        $this->assertEquals(0, $stockStatus->getStockStatus());
        $this->assertEquals(0, $stockStatus->getQty());
        $this->assertEquals($stockId, $stockStatus->getStockId());
        $this->assertEquals($productId, $stockStatus->getProductId());
    }

    /**
     * Check, grouped product has correct stock status on custom source.
     *
     * @return void
     */
    public function testGetStatusOnCustomSource()
    {
        $this->markTestSkipped('Grouped product type not supported on custom source');
    }

    /**
     * Set grouped to 'Out of Stock'.
     *
     * @param int $productId
     * @return void
     */
    private function setProductsOutOfStock(int $productId)
    {
        $this->stockItemCriteria->setProductsFilter($productId);
        $stockItems = $this->stockItemRepository->getList($this->stockItemCriteria)->getItems();
        $configurableStockItem = reset($stockItems);
        $configurableStockItem->setIsInStock(false);
        $this->stockItemRepository->save($configurableStockItem);
    }
}
