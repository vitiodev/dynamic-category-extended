<?php
declare(strict_types=1);

namespace Magenmagic\CustomLogicForIndexDynamicCategories\Model;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Wyomind\DynamicCategory\Model\Rule\Condition\Combine;
use Wyomind\DynamicCategory\Model\Rule\Condition\Product;

class Converter
{
    /**
     * Add filters to collection
     *
     * @param Collection $collection
     * @param Combine $conditions
     * @return void
     */
    public function addFiltersFromConditions(Collection $collection, Combine $conditions)
    {
        $filters = [];
        $categoryFilter = [];
        $all = $conditions->getAggregator() === 'all';
        $true = (bool) $conditions->getValue();
        foreach ($conditions->getConditions() as $condition) {
            if ($condition instanceof Combine && is_array($condition->getConditions())) {
                $this->addFiltersFromConditions($collection, $condition);
            }
            if ($condition instanceof Product && $condition->getType() === Product::class) {
                if ($all) {
                    if ($condition->getAttribute() === 'category_ids') {
                        $collection->addAttributeToSelect('*');
                        $collection->addCategoriesFilter($this->convertOperatorToCondition(
                            $condition->getOperator(),
                            $condition->getValue(),
                            $true
                        ));
                    } else {
                        $collection->addAttributeToFilter(
                            $condition->getAttribute(),
                            $this->convertOperatorToCondition(
                                $condition->getOperator(),
                                $condition->getValue(),
                                $true
                            )
                        );
                    }
                } else {
                    if ($condition->getAttribute() === 'category_ids') {
                        $categoryFilter = $this->convertOperatorToCondition(
                            $condition->getOperator(),
                            $condition->getValue(),
                            $true
                        );
                    } else {
                        $filters[] = array_merge(
                            ['attribute' => $condition->getAttribute()],
                            $this->convertOperatorToCondition(
                                $condition->getOperator(),
                                $condition->getValue(),
                                $true
                            )
                        );
                    }
                }
            }
        }
        if (count($filters)) {
            $collection->addAttributeToFilter($filters);
        }
        if (count($categoryFilter)) {
            $collection->addAttributeToSelect('*');
            $this->addCategoriesFilterWithOr($collection, $categoryFilter);
        }
    }

    /**
     * Convert data from rules to filters
     *
     * @param string $operator
     * @param string|float $value
     * @param bool $true
     * @return array
     */
    private function convertOperatorToCondition(string $operator, $value, bool $true): array
    {
        $result = [];

        switch ($operator) {
            case $operator === '!!':
            case $operator === '==':
                $result = [$true ? 'eq' : 'neq' => $value];
                break;
            case $operator === '!=':
                $result = [$true ? 'neq' : 'eq' => $value];
                break;
            case $operator === '<=>':
                $result = [$true ? 'null' : 'notnull' => true];
                break;
            case $operator === '{}':
                $result = [$true ? 'like' : 'nlike' => '%' . $value . '%'];
                break;
            case $operator === '!{}':
                $result = [$true ? 'nlike' : 'like' => '%' . $value . '%'];
                break;
            case $operator === '()':
                $result = [$true ? 'in' : 'nin' => [$value]];
                break;
            case $operator === '!()':
                $result = [$true ? 'nin' : 'in' => [$value]];
                break;
            case $operator === '<':
                $result = [$true ? 'lt' : 'gt' => $value];
                break;
            case $operator === '>':
                $result = [$true ? 'gt' : 'lt' => $value];
                break;
            case $operator === '<=':
                $result = [$true ? 'lteq' : 'gteq' => $value];
                break;
            case $operator === '>=':
                $result = [$true ? 'gteq' : 'lteq' => $value];
                break;
            case $operator === '^$':
                $result = [$true ? 'regexp' : 'nregexp' => $value];
                break;
            case $operator === '!^$':
                $result = [$true ? 'nregexp' : 'regexp' => $value];
                break;
        }

        return $result;
    }

    /**
     * Add categories filter with OR condition
     *
     * @param Collection $collection
     * @param array $categoriesFilter
     * @return void
     */
    private function addCategoriesFilterWithOr(Collection $collection, array $categoriesFilter)
    {
        foreach ($categoriesFilter as $conditionType => $values) {
            $categorySelect = $collection->getConnection()->select()->from(
                ['cat' => $collection->getTable('catalog_category_product')],
                'cat.product_id'
            )->where($collection->getConnection()->prepareSqlCondition('cat.category_id', ['in' => $values]));
            $selectCondition = [
                $this->mapConditionType($conditionType) => $categorySelect
            ];
            $collection->getSelect()->orWhere(
                $collection->getConnection()->prepareSqlCondition('e.entity_id', $selectCondition)
            );
        }
    }

    /**
     * Map condition type
     *
     * @param string $conditionType
     * @return string
     */
    private function mapConditionType(string $conditionType): string
    {
        $conditionsMap = [
            'eq' => 'in',
            'neq' => 'nin'
        ];
        return $conditionsMap[$conditionType] ?? $conditionType;
    }
}
