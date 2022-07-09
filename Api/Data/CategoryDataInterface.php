<?php

namespace Magenmagic\CustomLogicForIndexDynamicCategories\Api\Data;

interface CategoryDataInterface
{
    /**
     * Category instance
     *
     * @return string
     */
    public function getCategoryId(): string;

    /**
     * Array of products
     *
     * @return string[]
     */
    public function getProducts(): array;

    /**
     * Set category id
     *
     * @param string $categoryId
     * @return CategoryDataInterface
     */
    public function setCategoryId(string $categoryId): CategoryDataInterface;

    /**
     * Set products
     *
     * @param string[] $product
     * @return CategoryDataInterface
     */
    public function setProducts(array $product): CategoryDataInterface;
}
