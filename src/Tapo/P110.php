<?php

namespace VRciF\PhpSmartdevice\Tapo;

// https://k4czp3r.xyz/reverse-engineering/tp-link/tapo/2020/10/15/reverse-engineering-tp-link-tapo.html
// info of further endpoints: https://pypi.org/project/tapo-plug/

use VRciF\PhpSmartdevice\Tapo\Dto\DeviceInfo;
use VRciF\PhpSmartdevice\Tapo\Dto\Energy;

class P110 {
    protected array $errorCodes = [
        0     =>  'Success',
        -1010 => 'Invalid Public Key Length',
        -1501 => 'Invalid Request or Credentials',
        1002  => 'Incorrect Request',
        -1003 => 'JSON formatting error'
    ];
    protected string $ip = '';
    /**
     * @var string|null Just the base64 encoded key without newlines
     */
    protected string $base64PublicKey  = '';
    /**
     * @var string|null
     */
    protected string $base64PrivateKey = '';
    /**
     * @var string Cookie returned by handshake from tapo device
     */
    protected string $tapoCookie = '';
    /**
     * @var string The public key returned by the tapo device
     */
    protected string $encryptedDeviceKey = '';
    /**
     * @var string The public key returned by the tapo device
     */
    protected string $decryptedDeviceKey = '';
    /**
     * @var string The IV used by the tapo device's public key
     */
    protected string $decryptedDeviceKeyIV = '';
    /**
     * @var string Token used for further requests
     */
    protected string $requestToken = '';

    /**
     * @var string Token used for further requests
     */
    protected string $macAddress = '';

    public function __construct(string $ip, string $base64PublicKey=null, string $base64PrivateKey=null){
        $this->ip = $ip;

        if (!empty($base64PublicKey) && !empty($base64PrivateKey)) {
            $this->base64PublicKey = $base64PublicKey;
            $this->base64PrivateKey = $base64PrivateKey;
        }
        else{
            $this->generateKeyPair();
        }
    }

    public function handshake(){
        $handshakeParamKey = $this->getPublicKeyInPEMFormat();
        $json = \json_encode(['method' => 'handshake', 'params' => ['key' => $handshakeParamKey], 'requestTimeMils' => 0]);
        //var_dump($json);
        $url = 'http://' . $this->ip . '/app';

        $result = $this->sendPost($url, $json);
        if ($result->info['http_code'] !== 200 || !isset($result->body->error_code) || $result->body->error_code !== 0) {
            echo "ERROR: ".\json_encode($result, JSON_PRETTY_PRINT)."\n";
            throw new \Exception('Error response received during handshake: ('.$result->body->error_code.')'.\json_encode($result->body), 1696174595241);
        }

        //var_dump($result);

        //var_dump($this->tapoCookie, $this->encryptedDeviceKey);
    }

    public function login(string $username, string $password){
        $encodedUsername = \base64_encode(\sha1($username));
        $encodedPassword = \base64_encode($password);

        $plainRequest = [
            'method' => 'login_device',
            'params' => [
                'username' => $encodedUsername,
                'password' => $encodedPassword,
            ],
            'requestTimeMils' => 0
        ];

        $result = $this->sendSecureRequest($plainRequest);
        $this->requestToken = $result->result->token;
    }

    public function getDeviceTime() {
        $plainRequest = [
            'method' => 'get_device_time',
            'requestTimeMils' => 0,
        ];

        return $this->sendSecureRequest($plainRequest);
    }

    public function setDeviceTime() {
        $plainRequest = [
            'method' => 'set_device_time',
            'params' => [
                'time_diff' => 60,
                'timestamp' => time(),
                'region' => 'Europe/Vienna',
            ],
            'requestTimeMils' => 0,
        ];

        return $this->sendSecureRequest($plainRequest);
    }

    public function getDeviceInfo() {
        $plainRequest = [
            'method' => 'get_device_info',
            //'params' => [],
            //'terminalUUID' => '1C-61-B4-77-75-FC', // mac address
            //'requestTimeMils' => $this->getUnixTimestampInMs(),
            'requestTimeMils' => 0,
        ];

        $result = $this->sendSecureRequest($plainRequest);

        $this->macAddress = $result->result->mac;

        return new DeviceInfo($result);
    }

    public function turnOff() {
        return $this->toggleSwitch(false);
    }
    public function turnOn() {
        return $this->toggleSwitch(true);
    }

    public function toggleSwitch(bool $onoff=true) {
        if (empty($this->macAddress)) {
            $this->getDeviceInfo();
        }

        $plainRequest = [
            'method' => 'set_device_info',
            'params' => [
                'device_on' => $onoff,
            ],
            'terminalUUID' => $this->macAddress, // mac address
            'requestTimeMils' => $this->getUnixTimestampInMs(),
        ];

        return $this->sendSecureRequest($plainRequest);
    }

    public function getEnergyUsage() {
//        if (empty($this->macAddress)) {
//            $this->getDeviceInfo();
//        }

        $plainRequest = [
            'method' => 'get_energy_usage',
            //'params' => [],
            //'terminalUUID' => $this->macAddress, // mac address
            'requestTimeMils' => $this->getUnixTimestampInMs(),
        ];

        try {
            $result = $this->sendSecureRequest($plainRequest);
            return new Energy($result);
        } catch(\Exception $e) {
            if ($e->getCode() === -1003) {
                $this->setDeviceTime();
                return $this->sendSecureRequest($plainRequest);
            }

            throw $e;
        }
    }

    protected function sendSecureRequest($plainRequest) {
        $plainJson = \json_encode($plainRequest);
        $encrypted = $this->encrypt($this->decryptedDeviceKey, $plainJson);

        $result = $this->securePassthrough($encrypted);
        if($result->error_code !== 0){
            throw new \Exception('Error returned from '.$plainJson.' : '.\json_encode($result), $result->error_code);
        }
        return $result;
    }

    public function securePassthrough (string $encryptedRequest) {
        $json = \json_encode(['method' => 'securePassthrough', 'params' => ['request' => $encryptedRequest]]);
        //var_dump($json);
        $url = 'http://' . $this->ip . '/app';
        if (!empty($this->requestToken)) {
            $url .= '?token='.$this->requestToken;
        }

        $result = $this->sendPost($url, $json);
        if ($result->info['http_code'] !== 200 || !isset($result->body->error_code) || $result->body->error_code !== 0) {
            echo "ERROR: ".\json_encode($result, JSON_PRETTY_PRINT)."\n";
            throw new \Exception('Error response received: ('.$result->body->error_code.')'.\json_encode($result->body), 1696174595241);
        }

        $encryptedResponse = $result->body->result->response;
        $result = $this->decrypt($this->decryptedDeviceKey, $encryptedResponse);
        return \json_decode($result);
    }

    protected function getUnixTimestampInMs(){
        return round(microtime(1) * 1000);
    }

    protected function generateKeyPair(){
        $keys = \openssl_pkey_new(array("private_key_bits" => 1024,"private_key_type" => OPENSSL_KEYTYPE_RSA));
        $public_key_pem = \openssl_pkey_get_details($keys)['key'];
        \openssl_pkey_export($keys, $private_key_pem);

        $public_key_pem_raw= str_replace (array("-----BEGIN PUBLIC KEY-----","-----END PUBLIC KEY-----","\r\n", "\n", "\r"), '', $public_key_pem);
        $private_key_pem_raw= str_replace (array("-----BEGIN PRIVATE KEY-----","-----END PRIVATE KEY-----","\r\n", "\n", "\r"), '', $private_key_pem);

        $this->base64PublicKey = $public_key_pem_raw;
        $this->base64PrivateKey = $private_key_pem_raw;
    }

    protected function getPublicKeyInPEMFormat(){
        return "-----BEGIN PUBLIC KEY-----\n".$this->base64PublicKey."\n-----END PUBLIC KEY-----\n";
    }
    protected function getPrivateKeyInPEMFormat(){
        return "-----BEGIN PRIVATE KEY-----\n".$this->base64PrivateKey."\n-----END PRIVATE KEY-----\n";
    }

    protected function sendPost($url, $json){
        //var_dump("send request", $url, $json, $this->tapoCookie);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2); //timeout in seconds

        \curl_setopt($ch, CURLOPT_URL,$url);
        \curl_setopt($ch, CURLOPT_POST, true);
        \curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

        if (!empty($this->tapoCookie)) {
            \curl_setopt($ch, CURLOPT_HTTPHEADER, ['Cookie: ' . $this->tapoCookie]);
        }

        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($ch, CURLOPT_HEADER, true);

        $response = \curl_exec($ch);
        $info = \curl_getinfo($ch);

        \curl_close($ch);

        // split response in header and body
        $header_size = $info['header_size'];
        $headerStr = \trim(\substr($response, 0, $header_size));
        $body = \substr($response, $header_size);

        //var_dump("response", $headerStr, $body);

        $headers = [];
        $headerLines = \explode("\r\n", $headerStr);
        foreach ($headerLines as $no => $line){
            // ignore HTTP/1.1 200 OK header line
            if ($no === 0) {
                continue;
            }

            list($headername, $headervalue) = \explode(":", $line, 2);
            $headers[$headername] = \trim($headervalue);
        }

        if (isset($headers['Set-Cookie'])) {
            $this->tapoCookie = \explode(';', $headers['Set-Cookie'])[0];
        }
        if (
            isset($headers['Content-Type']) &&
            $this->strContains($headers['Content-Type'], 'application/json')
        ) {
            $body = \json_decode($body);
            if (isset($body->error_code) && $body->error_code === 0 && isset($body->result->key)) {
                $this->encryptedDeviceKey = $body->result->key;
                $this->decodeTapoKey($this->encryptedDeviceKey);
            }
        }

        return (object)['header' => $headers, 'body' => $body, 'info' => $info];
    }

    protected function strContains ($haystack, $needle) {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }

    protected function decodeTapoKey(string $encryptedDeviceKey) {
        $decryptedKey = '';
        if (
            !\openssl_private_decrypt(
                \base64_decode($encryptedDeviceKey),
                $decryptedKey,
                $this->getPrivateKeyInPEMFormat(),
                OPENSSL_PKCS1_PADDING
            )
        ){
            throw new \Exception('Could not decrypt tapo device key. '.\openssl_error_string());
        }

        //var_dump(\strlen($decryptedKey), $decryptedKey);
        $this->decryptedDeviceKey = \substr($decryptedKey, 0, 16);
        $this->decryptedDeviceKeyIV = \substr($decryptedKey, 16);
    }

    protected function encrypt($aesKey, $dataToEncrypt) {
        $output = \openssl_encrypt($dataToEncrypt, 'AES-128-CBC', $this->decryptedDeviceKey,
            OPENSSL_RAW_DATA, $this->decryptedDeviceKeyIV);
        $output = \base64_encode($output);
        $output = \str_replace("\r\n","", $output);
        return $output;
    }

    protected function decrypt($aesKey, $dataToDecrypt) {
        $dataToDecrypt = \base64_decode ($dataToDecrypt);
        $output = \openssl_decrypt($dataToDecrypt, 'AES-128-CBC',
            $this->decryptedDeviceKey, OPENSSL_RAW_DATA, $this->decryptedDeviceKeyIV);
        return $output;
    }
}

