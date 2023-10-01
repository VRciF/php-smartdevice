<?php

namespace VRciF\PhpSmartdevice\Kasa\Dto;

class SysInfo
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

    public function getSoftwareVersion()
    {
        return $this->commandResult->system->get_sysinfo->sw_ver;
    }
    public function getHardwareVersion()
    {
        return $this->commandResult->system->get_sysinfo->hw_ver;
    }

    public function getModel()
    {
        return $this->commandResult->system->get_sysinfo->model;
    }

    public function getDeviceId()
    {
        return $this->commandResult->system->get_sysinfo->deviceId;
    }

    public function getOemId()
    {
        return $this->commandResult->system->get_sysinfo->oemId;
    }

    public function getHwId()
    {
        return $this->commandResult->system->get_sysinfo->hwId;
    }

    public function getRSSI()
    {
        return $this->commandResult->system->get_sysinfo->rssi;
    }

    public function getLongitued()
    {
        return $this->commandResult->system->get_sysinfo->longitude_i;
    }

    public function getLatitude()
    {
        return $this->commandResult->system->get_sysinfo->latitutde_i;
    }

    public function getAlias()
    {
        return $this->commandResult->system->get_sysinfo->alias;
    }

    public function getStatus()
    {
        return $this->commandResult->system->get_sysinfo->status;
    }

    public function getMicType()
    {
        return $this->commandResult->system->get_sysinfo->mic_type;
    }

    public function getFeature()
    {
        return $this->commandResult->system->get_sysinfo->feature;
    }

    public function getMac()
    {
        return $this->commandResult->system->get_sysinfo->mac;
    }

    public function getUpdating()
    {
        return $this->commandResult->system->get_sysinfo->updating;
    }

    public function getLedOff()
    {
        return $this->commandResult->system->get_sysinfo->led_off;
    }

    public function getRelayState()
    {
        return $this->commandResult->system->get_sysinfo->relay_state;
    }

    public function getOnTime()
    {
        return $this->commandResult->system->get_sysinfo->on_time;
    }

    public function getActiveMode()
    {
        return $this->commandResult->system->get_sysinfo->active_mode;
    }

    public function getIconHash()
    {
        return $this->commandResult->system->get_sysinfo->icon_hash;
    }

    public function getDeviceName()
    {
        return $this->commandResult->system->get_sysinfo->dev_name;
    }

    public function getNextAction()
    {
        return $this->commandResult->system->get_sysinfo->next_action;
    }

    public function getErrorCode()
    {
        return $this->commandResult->emeter->get_realtime->err_code;
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