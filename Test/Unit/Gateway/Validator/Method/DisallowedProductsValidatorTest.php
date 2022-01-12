<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Test\Unit\Gateway\Validator\Method;

class DisallowedProductsValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider validateDataProvider
     */
    public function testValidate(array $quoteProductIds, array $notAllowedProductIds, bool $isValidExpected)
    {
        $resultInterfaceFactoryStub =
            $this->getMockBuilder(\Magento\Payment\Gateway\Validator\ResultInterfaceFactory::class)
                ->onlyMethods(['create'])
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();
        $quoteStub = $this->createMock(\Magento\Quote\Api\Data\CartInterface::class);
        $notAllowedProductsProviderStub = $this->createMock(
            \Clearpay\Clearpay\Model\ResourceModel\NotAllowedProductsProvider::class
        );

        $disallowedProductsValidator = new \Clearpay\Clearpay\Gateway\Validator\Method\NotAllowedProductsValidator(
            $resultInterfaceFactoryStub,
            $notAllowedProductsProviderStub
        );

        $resultInterfaceFactoryStub->expects($this->once())
            ->method('create')
            ->willReturn($this->createMock(\Magento\Payment\Gateway\Validator\ResultInterface::class));

        $notAllowedProductsProviderStub->expects($this->once())
            ->method('provideIds')
            ->willReturn($notAllowedProductIds);

        $quoteItemsStubs = [];
        foreach ($quoteProductIds as $quoteProductId) {
            $itemStub = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
                ->addMethods(['getProductId'])
                ->disableOriginalConstructor()
                ->getMock();
            $itemStub->method('getProductId')
                ->willReturn($quoteProductId);
            $quoteItemsStubs[] = $itemStub;
        }

        $quoteStub->expects($this->once())
            ->method('getItems')
            ->willReturn($quoteItemsStubs);

        $resultInterfaceFactoryStub->expects($this->once())
            ->method('create')
            ->with([
                'isValid' => $isValidExpected,
                'failsDescription' => [],
                'errorCodes' => []
            ]);

        $disallowedProductsValidator->validate(['quote' => $quoteStub]);
    }

    public function validateDataProvider(): array
    {
        return [
            [[1,2,3], [4,5,6], true],
            [[1,2,3], [2,3,4], false],
            [[2], [2], false],
            [[2,4,6], [4], false],
            [[4], [2,4,8], false],
            [[5,6,7], [1,2,3], true],
            [[5], [], true],
            [[], [2], true],
            [[], [], true],
        ];
    }
}
