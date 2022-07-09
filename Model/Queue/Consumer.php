<?php
declare(strict_types=1);

namespace Magenmagic\CustomLogicForIndexDynamicCategories\Model\Queue;

use Magenmagic\CustomLogicForIndexDynamicCategories\Api\Data\CategoryDataInterface;
use Magenmagic\CustomLogicForIndexDynamicCategories\Helper\Data;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResource;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator;
use Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator;
use Magento\Indexer\Model\IndexerFactory;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Psr\Log\LoggerInterface;

class Consumer
{
    /**
     * @var CategoryResource
     */
    private CategoryResource $categoryResource;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var CategoryFactory
     */
    private CategoryFactory $categoryFactory;

    /**
     * @var Data
     */
    private Data $dataHelper;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @var ProductUrlPathGenerator
     */
    private ProductUrlPathGenerator $productUrlPathGenerator;

    /**
     * @var UrlPersistInterface
     */
    private UrlPersistInterface $urlPersist;

    /**
     * @var ProductUrlRewriteGenerator
     */
    private ProductUrlRewriteGenerator $productUrlRewriteGenerator;

    /**
     * @var IndexerFactory
     */
    private IndexerFactory $indexerFactory;

    /**
     * @param CategoryResource $categoryResource
     * @param CategoryFactory $categoryFactory
     * @param LoggerInterface $logger
     * @param Data $dataHelper
     * @param CollectionFactory $collectionFactory
     * @param ProductUrlPathGenerator $productUrlPathGenerator
     * @param UrlPersistInterface $urlPersist
     * @param ProductUrlRewriteGenerator $productUrlRewriteGenerator
     * @param IndexerFactory $indexerFactory
     */
    public function __construct(
        CategoryResource $categoryResource,
        CategoryFactory $categoryFactory,
        LoggerInterface $logger,
        Data $dataHelper,
        CollectionFactory $collectionFactory,
        ProductUrlPathGenerator $productUrlPathGenerator,
        UrlPersistInterface $urlPersist,
        ProductUrlRewriteGenerator $productUrlRewriteGenerator,
        IndexerFactory $indexerFactory
    ) {
        $this->categoryResource = $categoryResource;
        $this->logger = $logger;
        $this->categoryFactory = $categoryFactory;
        $this->dataHelper = $dataHelper;
        $this->collectionFactory = $collectionFactory;
        $this->productUrlPathGenerator = $productUrlPathGenerator;
        $this->urlPersist = $urlPersist;
        $this->productUrlRewriteGenerator = $productUrlRewriteGenerator;
        $this->indexerFactory = $indexerFactory;
    }

    /**
     * Save category with products
     *
     * @param CategoryDataInterface $data
     * @return void
     */
    public function process(CategoryDataInterface $data)
    {
        try {
            $category = $this->categoryFactory->create();
            $this->categoryResource->load($category, $data->getCategoryId());
            $productIds = $data->getProducts();
            $oldProducts = $category->getProductsPosition();
            $products = array_fill_keys($productIds, '');
            $common = array_intersect_key($oldProducts, $products);
            $products = $common + $products;

            $category->setPostedProducts($products)->setDynamicProductsRefresh(0);
            $this->categoryResource->save($category);

            if ($this->dataHelper->isUseUrlRewrite()) {
                $collection = $this->collectionFactory->create();

                if (!empty($productIds)) {
                    $collection->addIdFilter($productIds);
                } else {
                    $collection->addIdFilter([0]); // Workaround for empty collection
                }

                if ($collection->getSize()) {
                    foreach ($collection as $product) {
                        try {
                            $product->unsUrlPath();
                            $product->setUrlPath($this->productUrlPathGenerator->getUrlPath($product));
                            $this->urlPersist->replace($this->productUrlRewriteGenerator->generate($product));
                        } catch (\Exception $e) {
                            $this->logger->critical($e->getMessage());
                        }
                    }
                }

                if ($this->dataHelper->isLoggingEnabled()) {
                    $this->logger->notice(sprintf(
                        '[Dynamic Category] Category %d successfully reindexed (%d): %s',
                        $category->getId(),
                        count($productIds),
                        implode(', ', $productIds)
                    ));
                }

                $this->indexerFactory->create()
                    ->load(\Magento\Catalog\Model\Indexer\Category\Product::INDEXER_ID)
                    ->invalidate();
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }
}
