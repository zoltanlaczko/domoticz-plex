# domoticz-plex

Simple Plex Media Server plugin for Domoticz

## Features

* Lightweight, runs on RPi too
* Support multiple Plex clients
* Easy to install/setup

## Installation

#### Script setup

* Install PHP runtine

```bash
apt-get install php5-cli php5-curl
```

* Copy plex.php into scripts directory under domoticz home directory 
* Modify domoticz and plex URLs in plex.php 

```php
$domoticz_url="http://127.0.0.1:8080/";
$plex_url="http://192.168.1.100:32400/";
```

* Setup unix cron 

```bash
* * * * *  pi	/usr/bin/php5 {$domoticz_home}/scripts/plex.php
```

#### Plex machineIdentifier ID

* Open your Plex Media Server session API: http://192.168.1.100:32400/status/sessions
* Find **machineIdentifier** value

#### Domoticz setup

##### Hardware

* Name: Enter your Plex machineIdentifier ID with "**plex__**" prefix (e.g.: "plex__jhck925fp4mei") - Don't miss the double underscore!
* Type: **Dummy (Does nothing, use for virtual switches only)**
* **Add**
* Click on "**Create virtual sensor**" button for the newly added hardware
* Sensor type: **Text**
* **OK**

##### Device

* Search the new text device
* Click on the green arrow (**Add device**)
* Name: what ever you want

#### Wait 1 minute and check the device status


## Similar projects:

[Galadril's python script](https://www.domoticz.com/forum/viewtopic.php?f=23&t=7953)