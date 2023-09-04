<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Test\Unit\Model\Url\UrlBuilder;

class UrlFactoryTest extends \PHPUnit\Framework\TestCase
{
    private \Clearpay\Clearpay\Model\Url\UrlBuilder\UrlFactory $urlFactory;

    /** @var \Clearpay\Clearpay\Model\Config|\PHPUnit\Framework\MockObject\MockObject */
    private $configMock;

    /** @var \Magento\Store\Model\Store|\PHPUnit\Framework\MockObject\MockObject */
    private $storeMock;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(\Clearpay\Clearpay\Model\Config::class);
        $storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $environmentsConfigurationFromDiXml = [
            'sandbox' => [
                'api_url' => [
                    'USD' => 'https://api.us-sandbox.clearpay.com/',
                    'CAD' => 'https://api.us-sandbox.clearpay.com/',
                    'default' => 'https://api-sandbox.clearpay.com/',
                ],
                'js_lib_url' => 'https://js-sandbox.squarecdn.com/'
            ],
            'production' => [
                'api_url' => [
                    'USD' => 'https://api.us.clearpay.com/',
                    'CAD' => 'https://api.us.clearpay.com/',
                    'default' => 'https://api.clearpay.com/',
                ],
                'js_lib_url' => 'https://js.squarecdn.com/'
            ]
        ];

        $this->storeMock = $this->createMock(\Magento\Store\Model\Store::class);

        $storeManagerMock->expects($this->atMost(1))
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->urlFactory = new \Clearpay\Clearpay\Model\Url\UrlBuilder\UrlFactory(
            $this->configMock,
            $storeManagerMock,
            $environmentsConfigurationFromDiXml
        );
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(string $apiMode, string $urlType, string $expectedCreatedUrl, ?string $currency = null)
    {
        $this->configMock->expects($this->once())
            ->method('getApiMode')
            ->willReturn($apiMode);

        $this->storeMock->expects($this->atMost(1))
            ->method('getCurrentCurrencyCode')
            ->willReturn($currency);

        $url = $this->urlFactory->create($urlType);
        static::assertSame($expectedCreatedUrl, $url);
    }

    public function createDataProvider(): array
    {
        return [
            ['production', 'api_url', 'https://api.us.clearpay.com/', 'USD'],
            ['sandbox', 'js_lib_url', 'https://js.sandbox.squarecdn.com/'],
            ['production', 'js_lib_url', 'https://js.squarecdn.com/'],
            ['sandbox', 'api_url', 'https://api-sandbox.clearpay.com/', 'UAH'],
            ['production', 'api_url', 'https://api.clearpay.com/', 'AUD']
        ];
    }
}
