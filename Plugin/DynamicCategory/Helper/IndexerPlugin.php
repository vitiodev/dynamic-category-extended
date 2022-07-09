<?php
declare(strict_types=1);

namespace Magenmagic\CustomLogicForIndexDynamicCategories\Plugin\DynamicCategory\Helper;

use Magenmagic\CustomLogicForIndexDynamicCategories\Api\Data\CategoryDataInterface;
use Magenmagic\CustomLogicForIndexDynamicCategories\Helper\Data;
use Magenmagic\CustomLogicForIndexDynamicCategories\Model\Queue\Publisher;
use Magento\Catalog\Model\Category;
use Magento\Framework\Exception\LocalizedException;
use Wyomind\DynamicCategory\Helper\Indexer;

class IndexerPlugin
{
    /**
     * @var Data
     */
    private Data $dataHelper;

    /**
     * @var Publisher
     */
    private Publisher $publisher;

    /**
     * @var CategoryDataInterface
     */
    private CategoryDataInterface $categoryData;

    /**
     * @param Data $dataHelper
     * @param Publisher $publisher
     * @param CategoryDataInterface $categoryData
     */
    public function __construct(
        Data $dataHelper,
        Publisher $publisher,
        CategoryDataInterface $categoryData
    ) {
        $this->dataHelper = $dataHelper;
        $this->publisher = $publisher;
        $this->categoryData = $categoryData;
    }

    /**
     * Use custom logic for save category via async
     *
     * @param Indexer $subject
     * @param callable $proceed
     * @param Category $category
     * @return string[]
     * @throws LocalizedException
     */
    public function aroundProcess(Indexer $subject, callable $proceed, Category $category): array
    {
        if ($this->dataHelper->isUseCustomIndexer()) {
            $collection = $subject->getDynamicProductCollection($category);
            $productIds = $collection->getAllIds();

            $this->categoryData->setCategoryId($category->getId())->setProducts($productIds);
            $this->publisher->checkMessageAndStatus($this->categoryData);
            $this->publisher->execute($this->categoryData);

            return $productIds;
        } else {
            return $proceed($category);
        }
    }
}
