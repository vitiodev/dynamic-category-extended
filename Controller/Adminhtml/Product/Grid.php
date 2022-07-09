<?php
declare(strict_types=1);

namespace Magenmagic\CustomLogicForIndexDynamicCategories\Controller\Adminhtml\Product;

use Magento\Backend\Model\View\Result\Redirect;
use Magento\Catalog\Block\Adminhtml\Category\Tab\Product;
use Magento\Framework\Controller\Result\Raw;

class Grid extends \Wyomind\DynamicCategory\Controller\Adminhtml\Product\Grid
{
    /**
     * Rewrite execute
     *
     * @return Redirect|Raw|\Magento\Framework\Controller\Result\Redirect|void
     */
    public function execute()
    {
        $category = $this->_initCategory(true);
        if (!$category) {
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('catalog/*/', ['_current' => true, 'id' => null]);
        }
        $resultRaw = $this->resultRawFactory->create();
        // Allow another (AJAX) requests to be made if this one is too long
        $this->_session->writeClose();
        try {
            $productIds = $this->_indexer->process($category);
            // Used to generate grid later
            $this->getRequest()->setPostValue('selected_products', $productIds);
            $category->unsetData('products_position');
            $storeId = $this->getRequest()->getParam('store', 0);
            $this->_session->setLastViewedStore($storeId);
            $this->getRequest()->setControllerName('catalog_category');
            return $resultRaw->setContents(
                $this->layoutFactory->create()->createBlock(
                    Product::class,
                    'category.product.grid'
                )->toHtml()
            );
        } catch (\Exception $e) {
            $this->getResponse()->setHttpResponseCode(403)->setBody($e->getMessage());
        }
    }
}
