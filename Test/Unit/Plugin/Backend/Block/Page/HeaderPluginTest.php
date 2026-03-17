<?php
/**
 * Copyright © element119. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Element119\CustomAdminLogo\Test\Unit\Plugin\Backend\Block\Page;

use Element119\CustomAdminLogo\Plugin\Backend\Block\Page\HeaderPlugin;
use Magento\Backend\Block\Page\Header;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class HeaderPluginTest extends TestCase
{
    /** @var ScopeConfigInterface&MockObject */
    private ScopeConfigInterface&MockObject $scopeConfig;

    /** @var StoreManagerInterface&MockObject */
    private StoreManagerInterface&MockObject $storeManager;

    /** @var LoggerInterface&MockObject */
    private LoggerInterface&MockObject $logger;

    /** @var MockObject */
    private MockObject $header;

    /** @var HeaderPlugin */
    private HeaderPlugin $plugin;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->header = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->addMethods(['setLogoImageSrc'])
            ->onlyMethods(['getData'])
            ->getMock();

        $this->plugin = new HeaderPlugin(
            $this->scopeConfig,
            $this->storeManager,
            $this->logger
        );
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

    public function testBeforeToHtmlDoesNothingWhenFilenameIsEmptyString(): void
    {
        $configPath = 'admin/e119_admin_logos/menu';

        $this->header->method('getData')
            ->willReturnMap([
                ['custom_logo_config_path', null, $configPath],
                ['custom_logo_upload_dir', null, 'admin/logo/custom/menu'],
            ]);

        $this->scopeConfig->method('getValue')
            ->with($configPath)
            ->willReturn('');

        $this->header->expects($this->never())->method('setLogoImageSrc');

        $this->plugin->beforeToHtml($this->header);
    }

    public function testBeforeToHtmlDoesNothingWhenConfigReturnsNonString(): void
    {
        $configPath = 'admin/e119_admin_logos/menu';

        $this->header->method('getData')
            ->willReturnMap([
                ['custom_logo_config_path', null, $configPath],
                ['custom_logo_upload_dir', null, 'admin/logo/custom/menu'],
            ]);

        $this->scopeConfig->method('getValue')
            ->with($configPath)
            ->willReturn(['unexpected' => 'array']);

        $this->header->expects($this->never())->method('setLogoImageSrc');

        $this->plugin->beforeToHtml($this->header);
    }

    public function testBeforeToHtmlStripsPathTraversalFromFilename(): void
    {
        $configPath = 'admin/e119_admin_logos/menu';
        $uploadDir = 'admin/logo/custom/menu';
        $mediaUrl = 'https://example.com/media/';

        $this->header->method('getData')
            ->willReturnMap([
                ['custom_logo_config_path', null, $configPath],
                ['custom_logo_upload_dir', null, $uploadDir],
            ]);

        $this->scopeConfig->method('getValue')
            ->with($configPath)
            ->willReturn('../../etc/passwd');

        $store = $this->createMock(Store::class);
        $store->method('getBaseUrl')
            ->with(UrlInterface::URL_TYPE_MEDIA)
            ->willReturn($mediaUrl);
        $this->storeManager->method('getStore')->willReturn($store);

        $this->header->expects($this->once())
            ->method('setLogoImageSrc')
            ->with('https://example.com/media/admin/logo/custom/menu/passwd');

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

    public function testBeforeToHtmlHandlesNoSuchEntityException(): void
    {
        $configPath = 'admin/e119_admin_logos/menu';

        $this->header->method('getData')
            ->willReturnMap([
                ['custom_logo_config_path', null, $configPath],
                ['custom_logo_upload_dir', null, 'admin/logo/custom/menu'],
            ]);

        $this->scopeConfig->method('getValue')
            ->with($configPath)
            ->willReturn('logo.png');

        $this->storeManager->method('getStore')
            ->willThrowException(new NoSuchEntityException(__('Store not found')));

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Element119_CustomAdminLogo: Unable to resolve store for media URL.',
                $this->arrayHasKey('exception')
            );

        $this->header->expects($this->never())->method('setLogoImageSrc');

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
