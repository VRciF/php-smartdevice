<?php

namespace VRciF\PhpSmartdevice\Kasa;

use VRciF\PhpSmartdevice\DebugTrait;
use VRciF\PhpSmartdevice\Kasa\Dto\Energy;
use VRciF\PhpSmartdevice\Kasa\Dto\SysInfo;

class HS110
{
    use DebugTrait;

    protected $host = null;

    public function __construct(string $host, $debugClosure = null){
        $this->host = $host;

        $this->setDebugClosure($debugClosure);
    }

    public function getEnergy () {
        $cmd = '{"emeter":{"get_realtime":{}}}';

        $result = $this->sendCommand($cmd);

        if ($result->emeter->get_realtime->err_code !== 0) {
            throw new \Exception('Failed to get energy info: '.\json_encode($result), 1695557889417);
        }

        return new Energy($result);
    }

    public function getDeviceInfo () {
        $cmd = '{"system":{"get_sysinfo":null}}';

        $result = $this->sendCommand($cmd);

        if ($result->system->get_sysinfo->err_code !== 0) {
            throw new \Exception('Failed to get device sysinfo: '.\json_encode($result), 1695557895764);
        }

        return new SysInfo($result);
    }

    public function turnOff () {
        $cmd = '{"system":{"set_relay_state":{"state":0}}}';
        $result = $this->sendCommand($cmd);

        if ($result->system->set_relay_state->err_code !== 0) {
            throw new \Exception('Failed to turn off device: '.\json_encode($result), 1695557899537);
        }

        return true;
    }
    public function turnOn () {
        $cmd = '{"system":{"set_relay_state":{"state":1}}}';

        $result = $this->sendCommand($cmd);

        if ($result->system->set_relay_state->err_code !== 0) {
            throw new \Exception('Failed to turn on device: '.\json_encode($result), 1695557134332);
        }

        return true;
    }

    public function reboot () {
        $cmd = '{"system":{"reboot":{"delay":1}}}';
        $result = $this->sendCommand($cmd);

        if ($result->system->reboot->err_code !== 0) {
            throw new \Exception('Failed to turn on device: '.\json_encode($result), 1695557134332);
        }

        return true;
    }

    /**
     * FactoryReset is not enabled to avoid mistakes - use sendCommand('{"system":{"reset":{"delay":1}}}') if you really need thi
     */
//    public function factoryReset () {
//        $cmd = '{"system":{"reset":{"delay":1}}}';
//        $result = $this->sendCommand($cmd);
//
//        if ($result->system->reboot->err_code !== 0) {
//            throw new \Exception('Failed to turn on device: '.\json_encode($result), 1695557134332);
//        }
//
//        return true;
//    }

    /**
     * Send a plain command to the device
     *
     * @param $jsonCmd Json string to send to the device
     * @return mixed
     * @throws \Exception
     */
    public function sendCommand ($jsonCmd) {
        $sock = $this->connectToSocket();

        $this->sendToSocket($jsonCmd, $sock);
        $buf    = $this->getResultFromSocket($sock);
        $result = \json_decode($this->decrypt($buf));
        \socket_close($sock);

        return $result;
    }

    protected function decrypt($cypher_text, $first_key = 0xAB)
    {
        $header = \substr($cypher_text, 0, 4);
        $header_length = \unpack('N*', $header)[1];
        $cypher_text = \substr($cypher_text, 4);
        $buf = \unpack('c*', $cypher_text);
        $key = $first_key;

        for ($i = 1; $i < count($buf) + 1; $i++) {
            $nextKey = $buf[$i];
            $buf[$i] = $buf[$i] ^ $key;
            $key = $nextKey;
        }
        $array_map = \array_map('chr', $buf);
        $clear_text = \implode('', $array_map);
        $cypher_length = \strlen($clear_text);

        if ($header_length !== $cypher_length) {
            throw new \Exception("Length in header ({$header_length}) doesn't match actual message length ({$cypher_length}).");
        }

        return $clear_text;
    }

    protected function encrypt($clear_text, $first_key = 0xAB)
    {
        $buf =  \unpack('c*', $clear_text);
        $key = $first_key;

        for ($i = 1; $i < count($buf) + 1; $i++) {
            $buf[$i] = $buf[$i] ^ $key;
            $key = $buf[$i];
        }

        $array_map = \array_map('chr', $buf);
        $clear_text = \implode('', $array_map);
        $length = \strlen($clear_text);
        $header = \pack('N*', $length);

        return $header . $clear_text;
    }

    protected function connectToSocket($timeoutSec = 1)
    {
        if (!($sock1 = \socket_create(AF_INET, SOCK_STREAM, SOL_TCP))) {
            $errorcode = \socket_last_error();
            $errormsg = \socket_strerror($errorcode);
            throw new \Exception('Could not create socket: ['.$errorcode.'] '.$errormsg);
        }

        $this->debug('Socket created');

        //Connect socket to remote server
        if (!\socket_connect($sock1, $this->host, 9999)) {
            $errorcode = \socket_last_error();
            $errormsg = \socket_strerror($errorcode);
            throw new \Exception('Could not connect ['.$errorcode.'] '.$errormsg);
        }

        $this->debug('Connection established', $this->host . ':' . 9999);

        \socket_set_option($sock1, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeoutSec, 'usec' => 0));
        \socket_set_option($sock1, SOL_SOCKET, SO_SNDTIMEO, array('sec' => $timeoutSec, 'usec' => 0));

        return $sock1;
    }

    protected function sendToSocket($messageToSend, $sock)
    {
        $message = $this->encrypt($messageToSend);

        //Send the message to the server
        if (!\socket_send($sock, $message, strlen($message), 0)) {
            $errorcode = \socket_last_error();
            $errormsg = \socket_strerror($errorcode);
            throw new \Exception('Could not send data: ['.$errorcode.'] '.$errormsg);
        }

        $this->debug('Message send successfully');
    }

    protected function getResultFromSocket($sock)
    {
        //Now receive reply from server
        $buf = "";
        $timeoutSec = 1;
        $start = \microtime(true);

        while (\microtime(true) < $start + $timeoutSec) {
            $data = null;
            $noBytes = \socket_recv($sock, $data, 2048, 0);
            if ($noBytes === false) {
                $errorcode = \socket_last_error();
                $errormsg = \socket_strerror($errorcode);

                $this->debug("Could not receive data: [$errorcode] $errormsg");
                break;
            }
            if (0 < $noBytes) {
                $buf .= $data;
                break;
            }
        }

        return $buf;
    }
}