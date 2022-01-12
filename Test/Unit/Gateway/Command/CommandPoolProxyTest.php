<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Test\Unit\Gateway\Command;

class CommandPoolProxyTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider paymentFlowCommandDataProvider
     */
    public function testCaptureCommand(string $paymentFlow, string $expectedCommand): void
    {
        $clearpayConfigMock = $this->getMockBuilder(\Clearpay\Clearpay\Model\Config::class)
            ->onlyMethods(['getPaymentFlow'])
            ->disableOriginalConstructor()
            ->getMock();

        $commandPoolFactory = $this->getMockBuilder('Magento\Payment\Gateway\Command\CommandPoolFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $commandPoolProxy = new \Clearpay\Clearpay\Gateway\Command\CommandPoolProxy(
            $commandPoolFactory,
            $clearpayConfigMock,
            [
                'capture_immediate' => 'Clearpay\Clearpay\Gateway\Command\CaptureCommand',
                'auth_deferred' => 'Clearpay\Clearpay\Gateway\Command\AuthCommand',
            ]
        );

        $clearpayConfigMock->expects($this->atLeastOnce())
            ->method('getPaymentFlow')
            ->willReturn($paymentFlow);

        $commandPoolInterfaceStub = $this->getMockForAbstractClass(
            \Magento\Payment\Gateway\Command\CommandPoolInterface::class
        );
        $commandPoolInterfaceStub->method('get')
            ->willReturn($this->createMock(\Magento\Payment\Gateway\CommandInterface::class));

        $commandPoolFactory->expects($this->atLeastOnce())
            ->method('create')
            ->with([
                'commands' => [
                    'capture' => $expectedCommand
                ]
            ])->willReturn($commandPoolInterfaceStub);

        $commandPoolProxy->get('capture');
    }

    public function paymentFlowCommandDataProvider(): array
    {
        return [
            [
                \Clearpay\Clearpay\Model\Config\Source\PaymentFlow::IMMEDIATE,
                'Clearpay\Clearpay\Gateway\Command\CaptureCommand'
            ],
            [
                \Clearpay\Clearpay\Model\Config\Source\PaymentFlow::DEFERRED,
                'Clearpay\Clearpay\Gateway\Command\AuthCommand'
            ],
            [
                'UNDEFINED PAYMENT FLOW',
                'Clearpay\Clearpay\Gateway\Command\CaptureCommand'
            ]
        ];
    }
}
