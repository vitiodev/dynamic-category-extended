<?php
declare(strict_types=1);

namespace Magenmagic\CustomLogicForIndexDynamicCategories\Plugin\Rule\Model\Condition\Product;

use Magenmagic\CustomLogicForIndexDynamicCategories\Helper\Data;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Rule\Model\Condition\Product\AbstractProduct;

class AbstractProductPlugin
{
    public const SKIP_ATTRIBUTES = [
        'attribute_set_id',
        'category_ids',
        'product_parent',
        'stock',
        'total_child_products_qty',
        'product_type',
        'is_new',
        'is_salable',
        'in_promo',
        'created_at',
        'quantity',
        'price_special_applied',
        'has_image'
    ];

    /**
     * @var AttributeRepositoryInterface
     */
    private AttributeRepositoryInterface $attributeRepository;

    /**
     * @var Data
     */
    private Data $dataHelper;

    /**
     * @param AttributeRepositoryInterface $attributeRepository
     * @param Data $dataHelper
     */
    public function __construct(
        AttributeRepositoryInterface $attributeRepository,
        Data $dataHelper
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->dataHelper = $dataHelper;
    }

    /**
     * Check if product attribute exist
     *
     * @param AbstractProduct $subject
     * @param Collection $productCollection
     * @return array
     * @throws NoSuchEntityException
     */
    public function beforeCollectValidatedAttributes(AbstractProduct $subject, Collection $productCollection): array
    {
        if ($this->dataHelper->isCheckAttribute()) {
            try {
                $attribute = $subject->getAttribute();
                if (!in_array($attribute, self::SKIP_ATTRIBUTES)) {
                    $this->attributeRepository->get(Product::ENTITY, $attribute);
                }
                return [$productCollection];
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                throw new NoSuchEntityException(__($e->getMessage()));
            }
        } else {
            return [$productCollection];
        }
    }
}
