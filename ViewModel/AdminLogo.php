<?php
/**
 * Copyright © element119. All rights reserved.
 * See LICENCE.txt for licence details.
 */
declare(strict_types=1);

namespace Element119\CustomAdminLogo\ViewModel;

use Element119\CustomAdminLogo\Model\AdminLogo as AdminLogoModel;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class AdminLogo implements ArgumentInterface
{
    /** @var AdminLogoModel */
    private AdminLogoModel $adminLogo;

    /**
     * @param AdminLogoModel $adminLogo
     */
    public function __construct(
        AdminLogoModel $adminLogo
    ) {
        $this->adminLogo = $adminLogo;
    }

    /**
     * @return AdminLogoModel
     */
    public function getAdminLogoModel(): AdminLogoModel
    {
        return $this->adminLogo;
    }
}
