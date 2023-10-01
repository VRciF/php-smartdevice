#

## Installation

`composer require vrcif/php-stmartdevice`

## Usage

### TPLink Tapo P110

```
$ip = '10.0.x.x';  // your device IP
$email = 'some@email.com';  // the email you used to register your account
$pwd = 'stecsyronaotem';  // the pwd you used for your account during setup

$p110 = new \VRciF\PhpSmartdevice\Tapo\P110($ip);
$p110->handshake();
$p110->login($email, $pwd);

$deviceInfo = $p110->getDeviceInfo();
if ($deviceInfo->isDeviceOn()) {
  $p110->turnOff();
}
```

### TPLink Kasa HS110

Info about the protocol from
* https://www.softscheck.com/en/blog/tp-link-reverse-engineering/
* https://github.com/softScheck/tplink-smartplug/blob/master/tplink-smarthome-commands.txt

```
$ip = '192.168.x.x';  // your device IP
$tpLink = new \VRciF\PhpSmartdevice\Kasa\HS110($ip);
$energy = $tpLink->getEnergy();
$currentInAmpere = $energy->getCurrentInAmpere();
```