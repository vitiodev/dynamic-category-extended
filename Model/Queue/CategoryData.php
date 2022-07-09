<?php

namespace Magenmagic\CustomLogicForIndexDynamicCategories\Model\Queue;

use Magenmagic\CustomLogicForIndexDynamicCategories\Api\Data\CategoryDataInterface;

class CategoryData implements CategoryDataInterface
{
    /**
     * @var string
     */
    private string $categoryId;

    /**
     * @var string[]
     */
    private array $products;

    /**
     * @inheritDoc
     */
    public function getCategoryId(): string
    {
        return $this->categoryId;
    }

    /**
     * @inheritDoc
     */
    public function getProducts(): array
    {
        return $this->products;
    }

    /**
     * @inheritDoc
     */
    public function setCategoryId(string $categoryId): CategoryDataInterface
    {
        $this->categoryId = $categoryId;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setProducts(array $products): CategoryDataInterface
    {
        $this->products = $products;
        return $this;
    }
}
