<?php
/**
 * Copyright © element119. All rights reserved.
 * See LICENCE.txt for licence details.
 */
declare(strict_types=1);

namespace Element119\CustomAdminLogo\Model;

use Element119\CustomAdminLogo\Model\Config\Backend\AdminLoginLogo;
use Element119\CustomAdminLogo\Model\Config\Backend\AdminMenuLogo;
use Element119\CustomAdminLogo\Scope\Config;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\UrlInterface;

class AdminLogo
{
    /** @var Config */
    private Config $moduleConfig;

    /** @var FileDriver */
    private FileDriver $fileDriver;

    /** @var UrlInterface */
    private UrlInterface $urlBuilder;

    /**
     * @param Config $moduleConfig
     * @param FileDriver $fileDriver
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        Config $moduleConfig,
        FileDriver $fileDriver,
        UrlInterface $urlBuilder
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->fileDriver = $fileDriver;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @return string|null
     */
    public function getCustomAdminLoginLogoSrc(): ?string
    {
        if (!$logoFileName = $this->moduleConfig->getAdminLoginLogoFileName()) {
            return null;
        }

        return $this->fileDriver->getAbsolutePath(
            $this->urlBuilder->getBaseUrl() . DirectoryList::MEDIA . DIRECTORY_SEPARATOR,
            AdminLoginLogo::UPLOAD_DIR . DIRECTORY_SEPARATOR . $logoFileName
        );
    }

    /**
     * @return string|null
     */
    public function getCustomAdminMenuLogoSrc(): ?string
    {
        if (!$logoFileName = $this->moduleConfig->getAdminMenuLogoFileName()) {
            return null;
        }

        return $this->fileDriver->getAbsolutePath(
            $this->urlBuilder->getBaseUrl() . DirectoryList::MEDIA . DIRECTORY_SEPARATOR,
            AdminMenuLogo::UPLOAD_DIR . DIRECTORY_SEPARATOR . $logoFileName
        );
    }
}
