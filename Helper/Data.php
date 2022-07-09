<?php
declare(strict_types=1);

namespace Magenmagic\CustomLogicForIndexDynamicCategories\Helper;

use Magento\Store\Model\ScopeInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    public const XML_PATH_USE_QUERY_INSTEAD_VALIDATION = 'dynamic_category/settings/custom_query';
    public const XML_PATH_USE_CUSTOM_INDEXER = 'dynamic_category/settings/custom_indexer';
    public const XML_PATH_USE_URL_REWRITE = 'dynamic_category/settings/url_rewrite';
    public const XML_PATH_USE_NOTICE = 'dynamiccategory/general/enable_reindex_log';
    public const XML_PATH_CHECK_ATTRIBUTE = 'dynamic_category/settings/check_attr';

    /**
     * Get setting "Use query instead validation"
     *
     * @return bool
     */
    public function isUseQueryInsteadValidation(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_USE_QUERY_INSTEAD_VALIDATION,
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Get setting "Use custom logic for indexer"
     *
     * @return bool
     */
    public function isUseCustomIndexer(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_USE_CUSTOM_INDEXER,
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Get settings "Rewrite urls during indexation"
     *
     * @return bool
     */
    public function isUseUrlRewrite(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_USE_URL_REWRITE,
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Get settings "Enable Category Reindexation Logging"
     *
     * @return bool
     */
    public function isLoggingEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_USE_NOTICE,
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Get settings "Check, if exist product attribute"
     *
     * @return bool
     */
    public function isCheckAttribute(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CHECK_ATTRIBUTE,
            ScopeInterface::SCOPE_WEBSITE
        );
    }
}
