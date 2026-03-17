<?php
/**
 * Copyright © element119. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Element119\CustomAdminLogo\Model\Config\Backend;

use Magento\Config\Model\Config\Backend\Image;

class AdminMenuLogo extends Image
{
    /**
     * @inheritDoc
     */
    protected function _addWhetherScopeInfo(): bool
    {
        return false;
    }
}
