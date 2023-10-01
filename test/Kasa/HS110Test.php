<?php

namespace VRciF\PhpSmartdeviceTest\Kasa;

use PHPUnit\Framework\TestCase;
use VRciF\PhpSmartdevice\EchoDebugClosure;
use VRciF\PhpSmartdevice\Kasa\Dto\Energy;

class HS110Test extends TestCase
{
    protected $ip = null;

    public function setup () : void {
        $this->ip = \getenv('IP') ?? null;
    }

    /**
     * @return void
     * @test
     */
    public function debugTest () {
        $debugClosure = new class() extends EchoDebugClosure {
            public $debugCalled = 0;

            public function __invoke() {
                //$args = \func_get_args();
                //\call_user_func_array([$this, 'parent::__invoke'], $args);
                $this->debugCalled++;
            }
        };

        $tpLink = new \VRciF\PhpSmartdevice\Kasa\HS110($this->ip, $debugClosure);
        $tpLink->setDebugEnabled(true)
               ->getEnergy();
        $this->assertGreaterThan(0, $debugClosure->debugCalled);
    }

    /**
     * @return void
     * @test
     */
    public function energyUsageTest () {
        $tpLink = new \VRciF\PhpSmartdevice\Kasa\HS110($this->ip);

        // {"emeter":{"get_realtime":{"voltage_mv":234412,"current_ma":41,"power_mw":3037,"total_wh":926,"err_code":0}}
        $energy = $tpLink->getEnergy();

        $this->assertInstanceOf(Energy::class, $energy);

        $this->assertIsObject($energy->getCommandResult());
        $this->assertGreaterThan(0, $energy->getVoltageInVolt());
        $this->assertEquals(0, $energy->getErrorCode());
    }

    /**
     * @return void
     * @test
     */
    public function voltageTest () {
        $tpLink = new \VRciF\PhpSmartdevice\Kasa\HS110($this->ip);
        $voltage = $tpLink->getEnergy()->getVoltageInVolt();
        $this->assertGreaterThan(0, $voltage);
        $voltage = $tpLink->getEnergy()->getVoltageInMilliVolt();
        $this->assertGreaterThan(0, $voltage);
    }

    /**
     * @return void
     * @test
     */
    public function deviceInfoTest () {
        $tpLink = new \VRciF\PhpSmartdevice\Kasa\HS110($this->ip);
        $deviceInfo = $tpLink->getDeviceInfo();
        var_dump($deviceInfo);
        $this->assertEquals('IOT.SMARTPLUGSWITCH', $deviceInfo->getMicType());
    }

    /**
     * @return void
     * @test
     */
    public function turnOffAndOnTest () {
        $tpLink = new \VRciF\PhpSmartdevice\Kasa\HS110($this->ip);

        $result = $tpLink->turnOff();
        $this->assertTrue($result);

        $relayState = $tpLink->getDeviceInfo()->getRelayState();
        $this->assertEquals(0, $relayState);

        \sleep(2);

        $result = $tpLink->turnOn();
        $this->assertTrue($result);

        $relayState = $tpLink->getDeviceInfo()->getRelayState();
        $this->assertEquals(1, $relayState);
    }

    /**
     * @return void
     * @test
     */
    public function rebootTest () {
        $tpLink = new \VRciF\PhpSmartdevice\Kasa\HS110($this->ip);

        $result = $tpLink->reboot();
        $this->assertTrue($result);
    }

}