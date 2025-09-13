<?php

declare(strict_types=1);

namespace Tests\Unit\Radio\Simulcasting;

use App\Entity\Enums\SimulcastingStatus;
use App\Entity\Simulcasting;
use App\Entity\Station;
use App\Radio\Backend\Liquidsoap;
use App\Radio\Enums\BackendAdapters;
use App\Radio\Simulcasting\LiquidSoapSimulcastingService;
use App\Radio\Simulcasting\SimulcastingManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SimulcastingManagerTest extends TestCase
{
    private SimulcastingManager $manager;
    private LoggerInterface $logger;
    private LiquidSoapSimulcastingService $liquidsoapService;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->liquidsoapService = $this->createMock(LiquidSoapSimulcastingService::class);

        $this->manager = new SimulcastingManager(
            $this->logger,
            $this->liquidsoapService
        );
    }

    public function testGetAvailableAdapters(): void
    {
        $adapters = $this->manager->getAvailableAdapters();

        $this->assertArrayHasKey('facebook', $adapters);
        $this->assertArrayHasKey('youtube', $adapters);

        $this->assertEquals('Facebook Live', $adapters['facebook']['description']);
        $this->assertEquals('YouTube Live', $adapters['youtube']['description']);
    }

    public function testValidateAdapter(): void
    {
        $this->assertTrue($this->manager->validateAdapter('facebook'));
        $this->assertTrue($this->manager->validateAdapter('youtube'));
        $this->assertFalse($this->manager->validateAdapter('invalid'));
    }

    public function testStartSimulcastingWithNonLiquidsoapBackend(): void
    {
        $station = $this->createMock(Station::class);
        $station->method('getBackendType')->willReturn(BackendAdapters::None);

        $simulcasting = $this->createMock(Simulcasting::class);
        $simulcasting->method('getStation')->willReturn($station);
        $simulcasting->method('getId')->willReturn(1);
        $simulcasting->method('getAdapter')->willReturn('facebook');

        $liquidsoap = $this->createMock(Liquidsoap::class);

        $this->logger->expects($this->once())
            ->method('error');

        $result = $this->manager->startSimulcasting($simulcasting, $liquidsoap);

        $this->assertFalse($result);
    }

    public function testStartSimulcastingSuccess(): void
    {
        $station = $this->createMock(Station::class);
        $station->method('getBackendType')->willReturn(BackendAdapters::Liquidsoap);
        $station->method('getId')->willReturn(1);

        $simulcasting = $this->createMock(Simulcasting::class);
        $simulcasting->method('getStation')->willReturn($station);
        $simulcasting->method('getId')->willReturn(1);
        $simulcasting->method('getAdapter')->willReturn('facebook');

        $liquidsoap = $this->createMock(Liquidsoap::class);

        $this->liquidsoapService->expects($this->once())
            ->method('validateVideoFiles')
            ->willReturn([]);

        $this->liquidsoapService->expects($this->once())
            ->method('startStream')
            ->willReturn(true);

        $this->logger->expects($this->once())
            ->method('info');

        $result = $this->manager->startSimulcasting($simulcasting, $liquidsoap);

        $this->assertTrue($result);
    }

    public function testStopSimulcastingSuccess(): void
    {
        $station = $this->createMock(Station::class);
        $station->method('getId')->willReturn(1);

        $simulcasting = $this->createMock(Simulcasting::class);
        $simulcasting->method('getStation')->willReturn($station);
        $simulcasting->method('getId')->willReturn(1);
        $simulcasting->method('getAdapter')->willReturn('facebook');

        $liquidsoap = $this->createMock(Liquidsoap::class);

        $this->liquidsoapService->expects($this->once())
            ->method('stopStream')
            ->willReturn(true);

        $this->logger->expects($this->once())
            ->method('info');

        $result = $this->manager->stopSimulcasting($simulcasting, $liquidsoap);

        $this->assertTrue($result);
    }

    public function testGetStreamsStatus(): void
    {
        $station = $this->createMock(Station::class);

        $expectedStatus = [
            [
                'id' => 1,
                'name' => 'Test Stream',
                'adapter' => 'facebook',
                'status' => SimulcastingStatus::Running->value,
                'is_running' => true,
                'error_message' => null,
            ],
        ];

        $this->liquidsoapService->expects($this->once())
            ->method('getStreamsStatus')
            ->with($station)
            ->willReturn($expectedStatus);

        $result = $this->manager->getStreamsStatus($station);

        $this->assertEquals($expectedStatus, $result);
    }
}
