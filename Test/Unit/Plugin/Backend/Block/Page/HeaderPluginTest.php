<?php
/**
 * Copyright © element119. All rights reserved.
 * See LICENCE.txt for licence details.
 */
declare(strict_types=1);

namespace Element119\CustomAdminLogo\Test\Unit\Plugin\Backend\Block\Page;

use Element119\CustomAdminLogo\Plugin\Backend\Block\Page\HeaderPlugin;
use Magento\Backend\Block\Page\Header;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HeaderPluginTest extends TestCase
{
    private ScopeConfigInterface&MockObject $scopeConfig;
    private StoreManagerInterface&MockObject $storeManager;
    private MockObject $header;
    private HeaderPlugin $plugin;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->header = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->addMethods(['setLogoImageSrc'])
            ->onlyMethods(['getData'])
            ->getMock();

        $this->plugin = new HeaderPlugin($this->scopeConfig, $this->storeManager);
    }

    public function testBeforeToHtmlDoesNothingWhenNoConfigPath(): void
    {
        $this->header->method('getData')
            ->willReturnMap([
                ['custom_logo_config_path', null, null],
                ['custom_logo_upload_dir', null, null],
            ]);

        $this->scopeConfig->expects($this->never())->method('getValue');
        $this->header->expects($this->never())->method('setLogoImageSrc');

        $this->plugin->beforeToHtml($this->header);
    }

    public function testBeforeToHtmlDoesNothingWhenNoUploadDir(): void
    {
        $this->header->method('getData')
            ->willReturnMap([
                ['custom_logo_config_path', null, 'admin/e119_admin_logos/menu'],
                ['custom_logo_upload_dir', null, null],
            ]);

        $this->scopeConfig->expects($this->never())->method('getValue');
        $this->header->expects($this->never())->method('setLogoImageSrc');

        $this->plugin->beforeToHtml($this->header);
    }

    public function testBeforeToHtmlDoesNothingWhenNoFilenameInConfig(): void
    {
        $configPath = 'admin/e119_admin_logos/menu';

        $this->header->method('getData')
            ->willReturnMap([
                ['custom_logo_config_path', null, $configPath],
                ['custom_logo_upload_dir', null, 'admin/logo/custom/menu'],
            ]);

        $this->scopeConfig->method('getValue')
            ->with($configPath)
            ->willReturn(null);

        $this->header->expects($this->never())->method('setLogoImageSrc');

        $this->plugin->beforeToHtml($this->header);
    }

    public function testBeforeToHtmlSetsMenuLogoUrl(): void
    {
        $configPath = 'admin/e119_admin_logos/menu';
        $uploadDir = 'admin/logo/custom/menu';
        $filename = 'my-logo.png';
        $mediaUrl = 'https://example.com/media/';

        $this->header->method('getData')
            ->willReturnMap([
                ['custom_logo_config_path', null, $configPath],
                ['custom_logo_upload_dir', null, $uploadDir],
            ]);

        $this->scopeConfig->method('getValue')
            ->with($configPath)
            ->willReturn($filename);

        $store = $this->createMock(Store::class);
        $store->method('getBaseUrl')
            ->with(UrlInterface::URL_TYPE_MEDIA)
            ->willReturn($mediaUrl);
        $this->storeManager->method('getStore')->willReturn($store);

        $this->header->expects($this->once())
            ->method('setLogoImageSrc')
            ->with('https://example.com/media/admin/logo/custom/menu/my-logo.png');

        $this->plugin->beforeToHtml($this->header);
    }

    public function testBeforeToHtmlSetsLoginLogoUrl(): void
    {
        $configPath = 'admin/e119_admin_logos/login';
        $uploadDir = 'admin/logo/custom/login';
        $filename = 'login-logo.jpg';
        $mediaUrl = 'https://example.com/media/';

        $this->header->method('getData')
            ->willReturnMap([
                ['custom_logo_config_path', null, $configPath],
                ['custom_logo_upload_dir', null, $uploadDir],
            ]);

        $this->scopeConfig->method('getValue')
            ->with($configPath)
            ->willReturn($filename);

        $store = $this->createMock(Store::class);
        $store->method('getBaseUrl')
            ->with(UrlInterface::URL_TYPE_MEDIA)
            ->willReturn($mediaUrl);
        $this->storeManager->method('getStore')->willReturn($store);

        $this->header->expects($this->once())
            ->method('setLogoImageSrc')
            ->with('https://example.com/media/admin/logo/custom/login/login-logo.jpg');

        $this->plugin->beforeToHtml($this->header);
    }

    public function testAfterGetViewFileUrlPassesThroughHttpsUrl(): void
    {
        $url = 'https://example.com/media/admin/logo/custom/menu/logo.png';

        $result = $this->plugin->afterGetViewFileUrl($this->header, 'ignored', $url);

        $this->assertSame($url, $result);
    }

    public function testAfterGetViewFileUrlPassesThroughHttpUrl(): void
    {
        $url = 'http://example.com/media/admin/logo/custom/login/logo.png';

        $result = $this->plugin->afterGetViewFileUrl($this->header, 'ignored', $url);

        $this->assertSame($url, $result);
    }

    public function testAfterGetViewFileUrlReturnsOriginalResultForViewFile(): void
    {
        $resolvedUrl = 'https://example.com/static/adminhtml/Magento/backend/en_US/images/mage-os-icon.svg';

        $result = $this->plugin->afterGetViewFileUrl(
            $this->header,
            $resolvedUrl,
            'images/mage-os-icon.svg'
        );

        $this->assertSame($resolvedUrl, $result);
    }
}
