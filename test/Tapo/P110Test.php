<?php

namespace VRciF\PhpSmartdeviceTest\Tapo;

use PHPUnit\Framework\TestCase;

class P110Test extends TestCase
{
    protected $ip    = null;
    protected $email = null;
    protected $pwd   = null;

    public function setup () : void {
        $this->ip = \getenv('IP') ?? null;
        $this->email = \getenv('EMAIL') ?? null;
        $this->pwd = \getenv('PWD') ?? null;
    }

    /**
     * @return void
     * @test
     */
    public function deviceInfoTest () {
        $p110 = new \VRciF\PhpSmartdevice\Tapo\P110($this->ip);
        $p110->handshake();
        $p110->login($this->email, $this->pwd);

        $deviceInfo = $p110->getDeviceInfo();
        $this->assertEquals('P110', $deviceInfo->getModel());
    }

    /**
     * @return void
     * @test
     */
    public function energyUsageTest () {
        $p110 = new \VRciF\PhpSmartdevice\Tapo\P110($this->ip);
        $p110->handshake();
        $p110->login($this->email, $this->pwd);

        $energy = $p110->getEnergyUsage();

        $this->assertGreaterThan(0, $energy->getCurrentPowerInMilliWatt());
        $this->assertGreaterThan(0, $energy->getCurrentPowerInWatt());
    }

    /**
     * @return void
     * @test
     */
    public function turnOffAndOnTest () {
        $p110 = new \VRciF\PhpSmartdevice\Tapo\P110($this->ip);
        $p110->handshake();
        $p110->login($this->email, $this->pwd);

        $p110->turnOff();

        $deviceInfo = $p110->getDeviceInfo();
        $this->assertFalse($deviceInfo->isDeviceOn());

        \sleep(3);

        $p110->turnOn();

        $deviceInfo = $p110->getDeviceInfo();
        $this->assertTrue($deviceInfo->isDeviceOn());
    }
}