<?php

namespace VRciF\PhpSmartdevice\Kasa\Dto;

class Energy
{
    protected $timestamp = 0;
    protected $commandResult = null;

    public function __construct($commandResult, $timestamp = 0) {
        $this->commandResult = $commandResult;

        if ($timestamp) {
            $this->timestamp = $timestamp;
        } else {
            $this->timestamp = \microtime(true);
        }
    }

    public function getVoltageInMilliVolt () {
        return $this->commandResult->emeter->get_realtime->voltage_mv;
    }
    public function getVoltageInVolt () {
        return $this->formatValue($this->getVoltageInMilliVolt());
    }

    public function getCurrentInMilliAmpere () {
        return $this->commandResult->emeter->get_realtime->current_ma;
    }
    public function getCurrentInAmpere () {
        return $this->formatValue($this->getCurrentInMilliAmpere());
    }

    public function getPowerInMilliWatt () {
        return $this->commandResult->emeter->get_realtime->power_mw;
    }
    public function getPowerInKiloWatt () {
        return $this->formatValue($this->getPowerInMilliWatt());
    }

    public function getTotalPowerInWattHour () {
        return $this->commandResult->emeter->get_realtime->total_wh;
    }
    public function getTotalPowerInKiloWattHour () {
        return $this->formatValue($this->getTotalPowerInWattHour());
    }

    protected function formatValue($value) {
        return \number_format($value / 1000, 3);
    }

    public function getErrorCode () {
        return $this->commandResult->emeter->get_realtime->err_code;
    }

    public function getTimestmap () {
        return $this->timestamp;
    }

    /**
     * Returns the plain result the device returned using the queryEnergy command
     * Example: {"emeter":{"get_realtime":{"voltage_mv":234412,"current_ma":41,"power_mw":3037,"total_wh":926,"err_code":0}}
     *
     * @return mixed|null
     */
    public function getCommandResult () {
        return $this->commandResult;
    }

    public static function fromCommand($commandResult, $timestamp = 0) {
        return new self($commandResult, $timestamp);
    }
}