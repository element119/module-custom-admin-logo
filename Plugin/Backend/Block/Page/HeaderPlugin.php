<?php
/**
 * Copyright © element119. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Element119\CustomAdminLogo\Plugin\Backend\Block\Page;

use Magento\Backend\Block\Page\Header;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Plugin that injects custom admin logo URLs into the Header block.
 *
 * Replaces a template override approach: layout XML arguments drive which
 * config path and upload directory apply per layout handle (login vs. menu),
 * and the core template renders the custom logo without modification.
 */
class HeaderPlugin
{
    /** @var ScopeConfigInterface */
    private ScopeConfigInterface $scopeConfig;

    /** @var StoreManagerInterface */
    private StoreManagerInterface $storeManager;

    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * Set custom logo URL on the block before rendering, if configured.
     *
     * Reads the config path and upload directory from layout XML arguments
     * to determine which custom logo (login or menu) applies to this context.
     *
     * @param Header $subject
     * @return void
     */
    public function beforeToHtml(Header $subject): void
    {
        if ($subject->getData('show_part') !== 'logo') {
            return;
        }

        $configPath = $subject->getData('custom_logo_config_path');
        $uploadDir = $subject->getData('custom_logo_upload_dir');

        if (!$configPath || !$uploadDir) {
            return;
        }

        $filename = $this->scopeConfig->getValue($configPath);

        if (!is_string($filename) || $filename === '') {
            return;
        }

        $filename = basename($filename);

        if ($filename === '') {
            return;
        }

        try {
            $mediaUrl = $this->storeManager->getStore()
                ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        } catch (NoSuchEntityException $e) {
            $this->logger->warning(
                'Element119_CustomAdminLogo: Unable to resolve store for media URL.',
                ['exception' => $e]
            );
            return;
        } catch (\Throwable $e) {
            $this->logger->error(
                'Element119_CustomAdminLogo: Unexpected error resolving media URL.',
                ['exception' => $e]
            );
            return;
        }

        $subject->setLogoImageSrc($mediaUrl . $uploadDir . '/' . $filename);
    }

    /**
     * Pass through full URLs without asset repository resolution.
     *
     * When a custom logo is configured, logo_image_src is set to a full media
     * URL. The core template passes this to getViewFileUrl(), which would
     * attempt static file resolution. This plugin short-circuits that for
     * absolute URLs, returning the fileId as-is.
     *
     * @param Header $subject
     * @param string $result
     * @param string $fileId
     * @return string
     */
    public function afterGetViewFileUrl(
        Header $subject,
        string $result,
        string $fileId = ''
    ): string {
        if ($subject->getData('show_part') !== 'logo') {
            return $result;
        }

        if (str_starts_with($fileId, 'http://') || str_starts_with($fileId, 'https://')) {
            return $fileId;
        }

        return $result;
    }
}
