<?php
/**
 * Copyright © element119. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Element119\CustomAdminLogo\Test\Unit\Plugin\Backend\Block\Page;

use Magento\Backend\Block\Page\Header;

class HeaderStub extends Header
{
    /**
     * Set the logo image source.
     *
     * @param string $value
     * @return $this
     */
    public function setLogoImageSrc($value)
    {
        return $this->setData('logo_image_src', $value);
    }
}
