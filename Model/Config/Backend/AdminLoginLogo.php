<?php
/**
 * Copyright © element119. All rights reserved.
 * See LICENCE.txt for licence details.
 */
declare(strict_types=1);

namespace Element119\CustomAdminLogo\Model\Config\Backend;

use Magento\Config\Model\Config\Backend\Image;

class AdminLoginLogo extends Image
{
    public const UPLOAD_DIR = 'admin/logo/custom/login';

    /**
     * @inheritDoc
     */
    protected function _getUploadDir(): string
    {
        return $this->_mediaDirectory->getAbsolutePath(self::UPLOAD_DIR);
    }

    /**
     * @inheritDoc
     */
    protected function _addWhetherScopeInfo(): bool
    {
        return false;
    }
}
