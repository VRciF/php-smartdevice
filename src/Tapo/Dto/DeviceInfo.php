<?php

namespace VRciF\PhpSmartdevice\Tapo\Dto;

class DeviceInfo
{
    protected $timestamp = 0;
    protected $commandResult = null;

    public function __construct($commandResult, $timestamp = 0)
    {
        $this->commandResult = $commandResult;

        if ($timestamp) {
            $this->timestamp = $timestamp;
        } else {
            $this->timestamp = \microtime(true);
        }
    }

    public function getDeviceId()
    {
        return $this->commandResult->result->device_id;
    }

    public function getFirmwareVersion()
    {
        return $this->commandResult->result->fw_ver;
    }

    public function getHardwareVersion()
    {
        return $this->commandResult->result->hw_ver;
    }

    public function getType()
    {
        return $this->commandResult->result->type;
    }

    public function getModel()
    {
        return $this->commandResult->result->model;
    }

    public function getMac()
    {
        return $this->commandResult->result->mac;
    }

    public function getHardwareId()
    {
        return $this->commandResult->result->hw_id;
    }

    public function getFirmwareId()
    {
        return $this->commandResult->result->fw_id;
    }

    public function getOemId()
    {
        return $this->commandResult->result->oem_id;
    }

    public function getOverheated()
    {
        return $this->commandResult->result->overheated;
    }

    public function getIp()
    {
        return $this->commandResult->result->ip;
    }

    public function getTimeDiff()
    {
        return $this->commandResult->result->time_diff;
    }

    public function getSSID()
    {
        return $this->commandResult->result->ssid;
    }

    public function getRSSI()
    {
        return $this->commandResult->result->rssi;
    }

    public function getSignalLevel()
    {
        return $this->commandResult->result->signal_level;
    }

    public function getLatitude()
    {
        return $this->commandResult->result->latitude;
    }

    public function getLongitude()
    {
        return $this->commandResult->result->longitude;
    }

    public function getLang()
    {
        return $this->commandResult->result->lang;
    }

    public function getAvatar()
    {
        return $this->commandResult->result->avatar;
    }

    public function getRegion()
    {
        return $this->commandResult->result->region;
    }

    public function getSpecs()
    {
        return $this->commandResult->result->specs;
    }

    public function getNickname()
    {
        return $this->commandResult->result->nickname;
    }

    public function hasSetLocationInfo()
    {
        return $this->commandResult->result->has_set_location_info;
    }

    public function isDeviceOn()
    {
        return $this->commandResult->result->device_on;
    }

    public function getOnTime()
    {
        return $this->commandResult->result->on_time;
    }

    public function getDefaultStates()
    {
        return $this->commandResult->result->default_states;
    }

    public function getErrorCode()
    {
        return $this->commandResult->error_code;
    }

    public function getTimestmap()
    {
        return $this->timestamp;
    }

    /**
     * Returns the plain result the device returned using the queryEnergy command
     * Example: {"emeter":{"get_realtime":{"voltage_mv":234412,"current_ma":41,"power_mw":3037,"total_wh":926,"err_code":0}}
     *
     * @return mixed|null
     */
    public function getCommandResult()
    {
        return $this->commandResult;
    }

    public static function fromCommand($commandResult, $timestamp = 0)
    {
        return new self($commandResult, $timestamp);
    }
}