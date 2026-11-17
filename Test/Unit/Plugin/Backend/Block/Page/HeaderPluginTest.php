<?php
/**
 * Copyright © element119. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Element119\CustomAdminLogo\Test\Unit\Plugin\Backend\Block\Page;

use Element119\CustomAdminLogo\Plugin\Backend\Block\Page\HeaderPlugin;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class HeaderPluginTest extends TestCase
{
    /** @var ScopeConfigInterface&MockObject */
    private ScopeConfigInterface $scopeConfig;

    /** @var StoreManagerInterface&Stub */
    private StoreManagerInterface $storeManager;

    /** @var LoggerInterface&Stub */
    private LoggerInterface $logger;

    /** @var HeaderStub&MockObject */
    private MockObject $header;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->storeManager = $this->createStub(StoreManagerInterface::class);
        $this->logger = $this->createStub(LoggerInterface::class);
        $this->header = $this->getMockBuilder(HeaderStub::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData', 'setLogoImageSrc'])
            ->getMock();
    }

    /**
     * Create the plugin with the configured collaborators.
     */
    private function createPlugin(): HeaderPlugin
    {
        return new HeaderPlugin(
            $this->scopeConfig,
            $this->storeManager,
            $this->logger
        );
    }

    /**
     * Helper: configure getData to return the given map plus show_part=logo.
     */
    private function setHeaderData(array $extra = []): void
    {
        $map = [['show_part', null, 'logo']];
        foreach ($extra as $key => $value) {
            $map[] = [$key, null, $value];
        }
        $this->header->expects($this->atLeastOnce())->method('getData')->willReturnMap($map);
    }

    // ── show_part guard ──────────────────────────────────────────────

    public function testBeforeToHtmlSkipsNonLogoBlock(): void
    {
        $this->header->expects($this->atLeastOnce())->method('getData')->willReturnMap([
            ['show_part', null, 'user'],
        ]);

        $this->scopeConfig->expects($this->never())->method('getValue');
        $this->header->expects($this->never())->method('setLogoImageSrc');

        $this->createPlugin()->beforeToHtml($this->header);
    }

    public function testAfterGetViewFileUrlSkipsNonLogoBlock(): void
    {
        $this->scopeConfig->expects($this->never())->method('getValue');

        $this->header->expects($this->atLeastOnce())->method('getData')->willReturnMap([
            ['show_part', null, 'user'],
        ]);

        $url = 'https://example.com/media/admin/logo/custom/menu/logo.png';
        $result = $this->createPlugin()->afterGetViewFileUrl($this->header, 'original-result', $url);

        $this->assertSame('original-result', $result);
    }

    // ── beforeToHtml guard clauses ───────────────────────────────────

    public function testBeforeToHtmlDoesNothingWhenNoConfigPath(): void
    {
        $this->setHeaderData([
            'custom_logo_config_path' => null,
            'custom_logo_upload_dir' => null,
        ]);

        $this->scopeConfig->expects($this->never())->method('getValue');
        $this->header->expects($this->never())->method('setLogoImageSrc');

        $this->createPlugin()->beforeToHtml($this->header);
    }

    public function testBeforeToHtmlDoesNothingWhenNoUploadDir(): void
    {
        $this->setHeaderData([
            'custom_logo_config_path' => 'admin/e119_admin_logos/menu',
            'custom_logo_upload_dir' => null,
        ]);

        $this->scopeConfig->expects($this->never())->method('getValue');
        $this->header->expects($this->never())->method('setLogoImageSrc');

        $this->createPlugin()->beforeToHtml($this->header);
    }

    public function testBeforeToHtmlDoesNothingWhenNoFilenameInConfig(): void
    {
        $configPath = 'admin/e119_admin_logos/menu';

        $this->setHeaderData([
            'custom_logo_config_path' => $configPath,
            'custom_logo_upload_dir' => 'admin/logo/custom/menu',
        ]);

        $this->scopeConfig->expects($this->once())->method('getValue')
            ->with($configPath)
            ->willReturn(null);

        $this->header->expects($this->never())->method('setLogoImageSrc');

        $this->createPlugin()->beforeToHtml($this->header);
    }

    public function testBeforeToHtmlDoesNothingWhenFilenameIsEmptyString(): void
    {
        $configPath = 'admin/e119_admin_logos/menu';

        $this->setHeaderData([
            'custom_logo_config_path' => $configPath,
            'custom_logo_upload_dir' => 'admin/logo/custom/menu',
        ]);

        $this->scopeConfig->expects($this->once())->method('getValue')
            ->with($configPath)
            ->willReturn('');

        $this->header->expects($this->never())->method('setLogoImageSrc');

        $this->createPlugin()->beforeToHtml($this->header);
    }

    public function testBeforeToHtmlDoesNothingWhenConfigReturnsNonString(): void
    {
        $configPath = 'admin/e119_admin_logos/menu';

        $this->setHeaderData([
            'custom_logo_config_path' => $configPath,
            'custom_logo_upload_dir' => 'admin/logo/custom/menu',
        ]);

        $this->scopeConfig->expects($this->once())->method('getValue')
            ->with($configPath)
            ->willReturn(['unexpected' => 'array']);

        $this->header->expects($this->never())->method('setLogoImageSrc');

        $this->createPlugin()->beforeToHtml($this->header);
    }

    // ── beforeToHtml security ────────────────────────────────────────

    public function testBeforeToHtmlStripsPathTraversalFromFilename(): void
    {
        $configPath = 'admin/e119_admin_logos/menu';
        $uploadDir = 'admin/logo/custom/menu';
        $mediaUrl = 'https://example.com/media/';

        $this->setHeaderData([
            'custom_logo_config_path' => $configPath,
            'custom_logo_upload_dir' => $uploadDir,
        ]);

        $this->scopeConfig->expects($this->once())->method('getValue')
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

        $this->createPlugin()->beforeToHtml($this->header);
    }

    // ── beforeToHtml happy path ──────────────────────────────────────

    public function testBeforeToHtmlSetsMenuLogoUrl(): void
    {
        $configPath = 'admin/e119_admin_logos/menu';
        $uploadDir = 'admin/logo/custom/menu';
        $filename = 'my-logo.png';
        $mediaUrl = 'https://example.com/media/';

        $this->setHeaderData([
            'custom_logo_config_path' => $configPath,
            'custom_logo_upload_dir' => $uploadDir,
        ]);

        $this->scopeConfig->expects($this->once())->method('getValue')
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

        $this->createPlugin()->beforeToHtml($this->header);
    }

    public function testBeforeToHtmlSetsLoginLogoUrl(): void
    {
        $configPath = 'admin/e119_admin_logos/login';
        $uploadDir = 'admin/logo/custom/login';
        $filename = 'login-logo.jpg';
        $mediaUrl = 'https://example.com/media/';

        $this->setHeaderData([
            'custom_logo_config_path' => $configPath,
            'custom_logo_upload_dir' => $uploadDir,
        ]);

        $this->scopeConfig->expects($this->once())->method('getValue')
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

        $this->createPlugin()->beforeToHtml($this->header);
    }

    // ── beforeToHtml error handling ──────────────────────────────────

    public function testBeforeToHtmlHandlesNoSuchEntityException(): void
    {
        $configPath = 'admin/e119_admin_logos/menu';

        $this->setHeaderData([
            'custom_logo_config_path' => $configPath,
            'custom_logo_upload_dir' => 'admin/logo/custom/menu',
        ]);

        $this->scopeConfig->expects($this->once())->method('getValue')
            ->with($configPath)
            ->willReturn('logo.png');

        $this->storeManager->method('getStore')
            ->willThrowException(new NoSuchEntityException(__('Store not found')));

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Element119_CustomAdminLogo: Unable to resolve store for media URL.',
                $this->arrayHasKey('exception')
            );

        $this->header->expects($this->never())->method('setLogoImageSrc');

        $this->createPlugin()->beforeToHtml($this->header);
    }

    public function testBeforeToHtmlHandlesUnexpectedThrowable(): void
    {
        $configPath = 'admin/e119_admin_logos/menu';

        $this->setHeaderData([
            'custom_logo_config_path' => $configPath,
            'custom_logo_upload_dir' => 'admin/logo/custom/menu',
        ]);

        $this->scopeConfig->expects($this->once())->method('getValue')
            ->with($configPath)
            ->willReturn('logo.png');

        $this->storeManager->method('getStore')
            ->willThrowException(new \RuntimeException('Unexpected failure'));

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Element119_CustomAdminLogo: Unexpected error resolving media URL.',
                $this->arrayHasKey('exception')
            );

        $this->header->expects($this->never())->method('setLogoImageSrc');

        $this->createPlugin()->beforeToHtml($this->header);
    }

    // ── afterGetViewFileUrl ──────────────────────────────────────────

    public function testAfterGetViewFileUrlPassesThroughHttpsUrl(): void
    {
        $this->scopeConfig->expects($this->never())->method('getValue');

        $this->setHeaderData();
        $url = 'https://example.com/media/admin/logo/custom/menu/logo.png';

        $result = $this->createPlugin()->afterGetViewFileUrl($this->header, 'ignored', $url);

        $this->assertSame($url, $result);
    }

    public function testAfterGetViewFileUrlPassesThroughHttpUrl(): void
    {
        $this->scopeConfig->expects($this->never())->method('getValue');

        $this->setHeaderData();
        $url = 'http://example.com/media/admin/logo/custom/login/logo.png';

        $result = $this->createPlugin()->afterGetViewFileUrl($this->header, 'ignored', $url);

        $this->assertSame($url, $result);
    }

    public function testAfterGetViewFileUrlReturnsOriginalResultForViewFile(): void
    {
        $this->scopeConfig->expects($this->never())->method('getValue');

        $this->setHeaderData();
        $resolvedUrl = 'https://example.com/static/adminhtml/Magento/backend/en_US/images/mage-os-icon.svg';

        $result = $this->createPlugin()->afterGetViewFileUrl(
            $this->header,
            $resolvedUrl,
            'images/mage-os-icon.svg'
        );

        $this->assertSame($resolvedUrl, $result);
    }
}
