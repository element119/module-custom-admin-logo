<?php
/**
 * Copyright © element119. All rights reserved.
 * See LICENCE.txt for licence details.
 */
declare(strict_types=1);

namespace Element119\CustomAdminLogo\Plugin\Backend\Block\Page;

use Magento\Backend\Block\Page\Header;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class HeaderPlugin
{
    private ScopeConfigInterface $scopeConfig;
    private StoreManagerInterface $storeManager;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Set custom logo URL on the block before rendering, if configured.
     *
     * Reads the config path and upload directory from layout XML arguments
     * to determine which custom logo (login or menu) applies to this context.
     */
    public function beforeToHtml(Header $subject): void
    {
        $configPath = $subject->getData('custom_logo_config_path');
        $uploadDir = $subject->getData('custom_logo_upload_dir');

        if (!$configPath || !$uploadDir) {
            return;
        }

        $filename = $this->scopeConfig->getValue($configPath);

        if (!$filename) {
            return;
        }

        $mediaUrl = $this->storeManager->getStore()
            ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

        $subject->setLogoImageSrc($mediaUrl . $uploadDir . '/' . $filename);
    }

    /**
     * Pass through full URLs without asset repository resolution.
     *
     * When a custom logo is configured, logo_image_src is set to a full media
     * URL. The core template passes this to getViewFileUrl(), which would
     * attempt static file resolution. This plugin short-circuits that for URLs.
     */
    public function afterGetViewFileUrl(
        Header $subject,
        string $result,
        string $fileId
    ): string {
        if (str_starts_with($fileId, 'http://') || str_starts_with($fileId, 'https://')) {
            return $fileId;
        }

        return $result;
    }
}
