<?php
/**
 * Copyright © element119. All rights reserved.
 * See LICENCE.txt for licence details.
 */
declare(strict_types=1);

namespace Element119\CustomAdminLogo\ViewModel;

use Element119\CustomAdminLogo\Model\AdminLogo as AdminLogoModel;
use Magento\Framework\App\Area;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class AdminLogo implements ArgumentInterface
{
    /** @var AdminLogoModel */
    private AdminLogoModel $adminLogo;

    /** @var RequestInterface */
    private RequestInterface $request;

    /**
     * @param AdminLogoModel $adminLogo
     * @param RequestInterface $request
     */
    public function __construct(
        AdminLogoModel $adminLogo,
        RequestInterface $request
    ) {
        $this->adminLogo = $adminLogo;
        $this->request = $request;
    }

    /**
     * @return AdminLogoModel
     */
    public function getAdminLogoModel(): AdminLogoModel
    {
        return $this->adminLogo;
    }

    /**
     * @return bool
     */
    public function isAdminLoginPage(): bool
    {
        return $this->request->getRouteName() === Area::AREA_ADMINHTML
            && $this->request->getControllerName() === 'auth'
            && $this->request->getActionName() === 'login';
    }
}
