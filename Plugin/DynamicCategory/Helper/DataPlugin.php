<?php
declare(strict_types=1);

namespace Magenmagic\CustomLogicForIndexDynamicCategories\Plugin\DynamicCategory\Helper;

use Exception;
use Magenmagic\CustomLogicForIndexDynamicCategories\Helper\Data as HelperData;
use Magenmagic\CustomLogicForIndexDynamicCategories\Model\Converter;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Profiler;
use Magento\Framework\Serialize\Serializer\Serialize;
use Wyomind\DynamicCategory\Helper\Data;
use Wyomind\DynamicCategory\Model\ResourceModel\ReplaceParent;
use Wyomind\DynamicCategory\Model\Rule\Condition\Product\ReplaceParent as RuleReplaceParent;
use Wyomind\DynamicCategory\Model\Rule;

class DataPlugin
{
    public const CUSTOM_LOGIC = true;

    /**
     * @var Serialize
     */
    private Serialize $serialize;

    /**
     * @var Rule
     */
    private Rule $dynamicCategoryRule;

    /**
     * @var ReplaceParent
     */
    private ReplaceParent $resourceModelReplaceParent;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $productCollection;

    /**
     * @var HelperData
     */
    private HelperData $helperData;

    /**
     * @var Converter
     */
    private Converter $converter;

    /**
     * @param Serialize $serialize
     * @param Rule $dynamicCategoryRule
     * @param ReplaceParent $resourceModelReplaceParent
     * @param CollectionFactory $productCollection
     * @param HelperData $helperData
     * @param Converter $converter
     */
    public function __construct(
        Serialize $serialize,
        Rule $dynamicCategoryRule,
        ReplaceParent $resourceModelReplaceParent,
        CollectionFactory $productCollection,
        HelperData $helperData,
        Converter $converter
    ) {
        $this->serialize = $serialize;
        $this->dynamicCategoryRule = $dynamicCategoryRule;
        $this->resourceModelReplaceParent = $resourceModelReplaceParent;
        $this->productCollection = $productCollection;
        $this->helperData = $helperData;
        $this->converter = $converter;
    }

    /**
     * Add custom logic for get product ids by rules
     *
     * @param Data $subject
     * @param callable $proceed
     * @param Category $category
     * @return array
     * @throws Exception
     */
    public function aroundGetDynamicProductIds(Data $subject, callable $proceed, Category $category)
    {
        if ($this->helperData->isUseQueryInsteadValidation()) {
            if (!$category->getId()) {
                return [];
            }

            $conditions = $category->getDynamicProductsConds();

            if (is_string($conditions)) {
                $conditions = $this->serialize->unserialize($conditions);
            }

            $websites = '1';
            $productIds = [];

            if (!empty($conditions)) {
                try {
                    $this->dynamicCategoryRule->setWebsiteIds($websites);
                    $this->dynamicCategoryRule->loadPost(['conditions' => $conditions]);
                    Profiler::start('DYNAMIC CATEGORY MATCHING PRODUCTS');
                    $productIds = $this->getDynamicCategoryMatchingProductIds();
                    Profiler::stop('DYNAMIC CATEGORY MATCHING PRODUCTS');

                    foreach ($conditions as $condition) {
                        if (isset($condition['type']) && $condition['type'] === RuleReplaceParent::class) {
                            $strategy = $condition['value'];
                            $parentIds = $this->resourceModelReplaceParent->getItems($productIds);
                            if ($strategy == 'keep_orphans') {
                                $productIds = array_unique(
                                    array_merge(
                                        $parentIds,
                                        array_keys(array_diff_key(array_flip($productIds), $parentIds))
                                    )
                                );
                            } else {
                                // Remove orphans
                                $productIds = $parentIds;
                            }
                            break;
                        }
                    }
                } catch (Exception $e) {
                    throw($e);
                }
            }
            return $productIds;
        } else {
            return $proceed($category);
        }
    }

    /**
     * Get all ids by query
     *
     * @return array
     * @throws LocalizedException
     */
    private function getDynamicCategoryMatchingProductIds(): array
    {
        $collection = $this->productCollection->create();
        $this->dynamicCategoryRule->getConditions()->collectValidatedAttributes($collection);
        $conditions = $this->dynamicCategoryRule->getConditions();
        $this->converter->addFiltersFromConditions($collection, $conditions);
        $collection->addWebsiteFilter(1);
        return $collection->getAllIds();
    }
}
