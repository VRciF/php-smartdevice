<?php

namespace VRciF\PhpSmartdevice\Tapo\Dto;

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

    public function getTodayRuntimeInMinutes () {
        return $this->commandResult->result->today_runtime;
    }
    public function getMonthRuntimeInMinutes () {
        return $this->commandResult->result->month_runtime;
    }

    public function getTodayCurrentInMilliWatt () {
        return $this->commandResult->result->today_energy;
    }
    public function getMonthCurrentInMilliWatt () {
        return $this->commandResult->result->month_energy;
    }

    public function getLocalTime () {
        return new \DateTime($this->commandResult->result->local_time);
    }

    public function getPast24HoursInMilliWatt () {
        return $this->commandResult->result->past24h;
    }
    public function getPast24HoursInWatt () {
        return \array_map(function($value){
            return $this->formatValue($value);
        },$this->commandResult->result->past24h);
    }

    public function get24HoursEachPast7DaysInMilliWatt () {
        return $this->commandResult->result->past7d;
    }
    public function get24HoursEachPast7DaysInWatt () {
        $result = $this->commandResult->result->past7d;

        foreach ($result as $dayno => $hours) {
            $result[$dayno] = \array_map(function($value){
                return $this->formatValue($value);
            },$hours);
        }

        return $result;
    }

    public function getPast30DaysInMilliWatt () {
        return $this->commandResult->result->past30d;
    }
    public function getPast30DaysInWatt () {
        return \array_map(function($value){
            return $this->formatValue($value);
        },$this->commandResult->result->past30d);
    }

    public function getPast12MonthsInMilliWatt () {
        return $this->commandResult->result->past1y;
    }
    public function getPast12MonthsInWatt () {
        return \array_map(function($value){
            return $this->formatValue($value);
        },$this->commandResult->result->past1y);
    }


    public function getCurrentPowerInMilliWatt () {
        return $this->commandResult->result->current_power;
    }

    public function getCurrentPowerInWatt () {
        return $this->formatValue($this->commandResult->result->current_power);
    }

    protected function formatValue($value) {
        return \number_format($value / 1000, 3);
    }

    public function getErrorCode () {
        return $this->commandResult->error_code;
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