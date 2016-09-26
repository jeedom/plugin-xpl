<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* ------------------------------------------------------------ Inclusions */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
include_file('core', 'xpl', 'class', 'xpl');
include_file('core', 'xpl', 'config', 'xpl');

class xPLDevice {
    /*     * ********Attributs******************* */

    private $heartBeatInterval = DEFAULT_HBEAT_INTERVAL;
    private $port = 0;
    private $vendor = '';
    private $device = '';
    private $instance = '';
    private $address = '';
    private $lastHeartBeat = 0;

    /*     * ********Static******************* */

    public function __construct($_vendor, $_device = null, $_instance = null) {
        if (is_null($_device)) {
            $dash = strpos($_vendor, '-');
            $dot = strpos($_vendor, '.');
            $this->vendor = substr($_vendor, 0, $dash);
            $this->device = substr($_vendor, $dash + 1, $dot - $dash - 1);
            $this->instance = substr($_vendor, $dot + 1);
        } else {
            $this->vendor = $_vendor;
            $this->device = $_device;

            if (is_null($_instance)) {
                //Generate an instance
                $randomNumber = mt_rand(1, 9999); //Generate ranom number between 1-9999
                $this->instance = sprintf('default%s', $randomNumber);
            } else {
                $this->instance = $_instance;
            }
        }
    }

    /*     * ********Getteur Setteur******************* */

    public function address() {
        return $this->address;
    }

    public function heartBeatInterval() {
        return $this->heartBeatInterval;
    }

    public function deviceName() {
        return sprintf('%s-%s.%s', $this->vendor, $this->device, $this->instance);
    }

    public function lastHeartBeat() {
        return $this->lastHeartBeat;
    }

    public function port() {
        return $this->port;
    }

    public function setAddress($address) {
        $this->address = $address;
        return $this;
    }

    public function setHeartBeatInterval($minutes) {
        $this->heartBeatInterval = $minutes;
        return $this;
    }

    public function setPort($p) {
        $this->port = $p;
        return $this;
    }

    public function setLastHeartBeat($timestamp) {
        $this->lastHeartBeat = $timestamp;
        return $this;
    }

}

class XPLInstance {
    /*     * ********Attributs******************* */

    protected $thisDevice = null;
    protected $devices = array();
    private $socket;
    private $isAttached = false;
    private $ip;
    private $message;
    private $retryConnectToHub = 0;
    private static $sharedInstance;

    /*     * ********Static******************* */

    private function __construct($_sendOnCmd = false) {
        $this->ip = XPL_IP;
        $this->thisDevice = new XPLDevice(XPL_VENDOR, XPL_DEVICE, XPL_INSTANCE);
        $this->init($_sendOnCmd);
    }

    public static function getXPLInstance($_sendOnCmd = false) {
        if (!isset(self::$sharedInstance)) {
            self::$sharedInstance = new self($_sendOnCmd);
        }
        return self::$sharedInstance;
    }

    public function attached() {
        return $this->isAttached;
    }

    public function detach() {
        $message = new xPLMessage(xPLMessage::xplstat);
        $message->setMessageSchemeIdentifier('hbeat.end');
        $message->setTarget('*');
        $message->setSource($this->thisDevice->deviceName());
        $this->sendMessage($message);
        socket_close($this->socket);
        $this->socket = null;
    }

    public function doEvents() {
        //-1 : error
        //0 : ok (message processed)
        //1 : there is a message
        //2 : no message

        $this->message = '';
        $string = socket_read($this->socket, 1500);
        if ($string != '') {
            $message = XPLMessage::createMessageFromString($string);
            if ($message) {
                if ($this->processMessage($message) == 1) {
                    $this->message = $message;
                    return 1;
                }
            }
            return 0;
        }
        $this->poll();
        return 2;
    }

    public function sendMessage($message, $device = null) {
        $msg = (string) $message;
        socket_sendto($this->socket, $msg, strlen($msg), 0, '255.255.255.255', XPL_PORT);
    }

    public function sendPlainTextMessage($message) {
        socket_sendto($this->socket, $message, strlen($message), 0, '255.255.255.255', XPL_PORT);
    }

    protected function attachedToNetwork() {
        
    }

    protected function handleMessage($message) {
        return false;
    }

    private function bindToPort($_sendOnCmd = false) {
        $port = 49152;
        $error = 98;
        while ($error == 98 && $port < 65535) {
            if (@socket_bind($this->socket, $this->ip, $port) === false) {
                ++$port;
            } else {
                if (!$_sendOnCmd) {
                    log::add('xpl', 'info', 'Bind succeded as client on: ' . $port);
                }
                return $port;
            }
        }
        return 0;
    }

    private function init($_sendOnCmd = false) {
        if (($this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP)) === false) {
            if (!$_sendOnCmd) {
                log::add('xpl', 'error', 'socket_create() failed: reason: ' . socket_strerror(socket_last_error()));
            }
            return;
        }
        socket_set_option($this->socket, SOL_SOCKET, SO_BROADCAST, 1);
        socket_set_nonblock($this->socket);

        $this->thisDevice->setPort($this->bindToPort($_sendOnCmd));
        if ($this->thisDevice->port() == 0) {
            log::add('xpl', 'error', "socket_bind() failed: reason: " . socket_strerror(socket_last_error($this->socket)));
            return;
        }

        if (!$_sendOnCmd) {
            log::add('xpl', 'info', 'xPL server started (bound to port : ' . $this->thisDevice->port() . ')');
            $this->sendHeartbeat();
        }
    }

    private function poll() {
        if (!$this->attached()) {
            $this->sendHeartbeat();
            $this->retryConnectToHub++;
            if ($this->retryConnectToHub > XPL_MAX_RETRY_CONNEXION_TO_HUB) {
                log::add('xpl', 'error', 'xPL Hub non trouvé, veuillez le redemarrer','xplHubNotFound');
                $cron = cron::byClassAndFunction('xpl', 'deamon');
                if (is_object($cron)) {
                    $cron->setEnable(0);
                    $cron->save();
                    $cron->stop();
                }
            }
            return;
        }
        $this->retryConnectToHub = 0;

        if (time() - $this->thisDevice->lastHeartBeat() >= $this->thisDevice->heartBeatInterval() * 60) {
            $this->sendHeartbeat();
        }
        //Loop all devices to see if they have timed out
        foreach ($this->devices as $key => $device) {
            if (time() - $device->lastHeartBeat() >= $device->heartBeatInterval() * 60 * 2) {
                log::add('xpl', 'info', 'Device removed (timeout) : ' . $device->deviceName());
                //This is not a prefeered way of removing the item from an array
                //Since we are iterating the loop will miss to check the new item.
                //Here, it will be checked next time poll is called
                unset($this->devices[$key]);
                xPL::removedDeviceFromxPLNetwork($device->deviceName());
                continue;
            }
        }
    }

    private function processHeartBeat($message) {
        if ($message->messageSchemeIdentifier() == 'hbeat.request') {
            $this->sendHeartbeat();
            return true;
        }
        foreach ($this->devices as $key => $device) {
            if ($device->deviceName() == $message->source()) {
                if (substr($message->messageSchemeIdentifier(), -3) == 'end') {
                    log::add('xpl', 'info', 'Device removed: ' . $message->source());
                    unset($this->devices[$key]);
                    xPL::removedDeviceFromxPLNetwork($device->deviceName());
                    return true;
                } else {
                    $device->setLastHeartBeat(time());
                    $device->setHeartBeatInterval($message->bodyItem('interval'));
                }
                return true;
            }
        }
        if ((substr($message->messageSchemeIdentifier(), -3) != 'app') && (substr($message->messageSchemeIdentifier(), -5) != 'basic')) { //Not a heartbeat
            return false;
        }
        $device = new xPLDevice($message->source());
        $device->setHeartBeatInterval($message->bodyItem('interval'));
        $device->setLastHeartBeat(time());
        if ($message->messageSchemeIdentifier() == 'hbeat.app') {
            $device->setAddress($message->bodyItem('remote-ip'));
            $device->setPort($message->bodyItem('port'));
        }
        $this->devices[] = $device;
        xPL::newDeviceFromxPLNetwork($message->source());
        return true;
    }

    private function processMessage($message) {
        //-1 : error
        //0 : ok (message processed)
        //1 : there is a message

        if (!$this->attached() && $message->messageSchemeIdentifier() == 'hbeat.app' && $message->source() == $this->thisDevice->deviceName()) {
            //Our own echo
            $this->setAttached(true);
            return 0;
        }
        if ($message->source() == $this->thisDevice->deviceName()) {
            //Ignore messages from ourselves
            return 0;
        }
        if ((substr($message->messageSchemeIdentifier(), 0, 5) == 'hbeat') || (substr($message->messageSchemeIdentifier(), 0, 6) == 'config')) {
            if ($this->processHeartBeat($message)) {
                return 0;
            }
        }
        if ($message->target() != $this->thisDevice->deviceName() && $message->target() != '*') {
            //Message not for us
            return 0;
        }
        if (!$this->handleMessage($message)) {
            return 1;
        }
    }

    private function sendHeartbeat() {
        //echo "Sending heartbeat\n";
        $message = new xPLMessage(xPLMessage::xplstat);
        $message->setMessageSchemeIdentifier('hbeat.app');
        $message->setTarget('*');
        $message->setSource($this->thisDevice->deviceName());
        $message->addBodyItem('interval', $this->thisDevice->heartBeatInterval());
        $message->addBodyItem('port', $this->thisDevice->port());
        $message->addBodyItem('remote-ip', $this->ip);
        $message->addBodyItem('appname', 'jeedom-xpl');
        $message->addBodyItem('version', '1.0');
        $this->sendMessage($message);
        $this->thisDevice->setLastHeartBeat(time());
    }

    private function setAttached($attached) {
        $this->isAttached = $attached;
        if ($this->isAttached) {
            log::add('xpl', 'info', 'Attached to xPL-network');
            $message = new xPLMessage(xPLMessage::xplcmnd);
            $message->setTarget("*");
            $message->setSource($this->thisDevice->deviceName());
            $message->setMessageSchemeIdentifier('hbeat.request');
            $message->addBodyItem('command', 'request');
            $this->sendMessage($message);
            $this->attachedToNetwork();
        }
    }

    public function getMessage() {
        return $this->message;
    }

    public function getThisDevice() {
        return $this->thisDevice;
    }

    public function getIp() {
        return $this->ip;
    }

}

class xPLMessage {
    /*     * ********Attributs******************* */

    private $hop = 1;
    private $identifier = null;
    private $body = array();
    private $msi = '';
    private $source = '';
    private $target = '';

    const xplcmnd = 1;
    const xplstat = 2;
    const xpltrig = 3;

    /*     * ********Static******************* */

    public function __construct($identifier) {
        $this->identifier = $identifier;
    }

    public function addBodyItem($key, $value) {
        $this->body[$key] = $value;
    }

    public function addHeadItem($key, $value) {
        if ($key == 'hop') {
            $hop = (int) $value;
        } else if ($key == 'source') {
            $this->setSource($value);
        } else if ($key == 'target') {
            $this->setTarget($value);
        }
    }

    public function bodyItem($key) {
        if (array_key_exists($key, $this->body)) {
            return $this->body[$key];
        }
        return '';
    }

    public function __tostring() {
        $message = '';

        switch ($this->identifier) {
            case self::xplcmnd:
                $message .= "xpl-cmnd\n";
                break;
            case self::xplstat:
                $message .= "xpl-stat\n";
                break;
            case self::xpltrig:
                $message .= "xpl-trig\n";
                break;
            default:
                return "";
        }

        $message .= "{\n";
        $message .= sprintf("hop=%s\n", $this->hop);
        $message .= sprintf("source=%s\n", $this->source);
        $message .= sprintf("target=%s\n", $this->target);
        $message .= "}\n";
        $message .= $this->msi . "\n";
        $message .= "{\n";
        foreach ($this->body as $key => $value) {
            $message .= sprintf("%s=%s\n", $key, $value);
        }
        $message .= "}\n";

        return $message;
    }

    public static function createMessageFromString($message) {
        $lines = explode("\n", $message);
        $row = 0;
        $i = 0;
        if ($lines[$row] == 'xpl-cmnd') {
            $i = self::xplcmnd;
        } else if ($lines[$row] == 'xpl-stat') {
            $i = self::xplstat;
        } else if ($lines[$row] == 'xpl-trig') {
            $i = self::xpltrig;
        } else {
            return 0;
        }
        ++$row;

        if ($lines[$row] != '{') {
            return 0;
        }
        ++$row;

        $msg = new xPLMessage($i);
        for (; $row < count($lines) && $lines[$row] != '}'; ++$row) {
            list($name, $value) = explode('=', $lines[$row]);
            $msg->addHeadItem($name, $value);
        }
        if ($row >= count($lines)) {
            return 0;
        }
        ++$row;

        $msg->setMessageSchemeIdentifier($lines[$row]);
        ++$row;

        if ($lines[$row] != '{') {
            return 0;
        }
        ++$row;

        for (; $row < count($lines) && $lines[$row] != '}'; ++$row) {
            list($name, $value) = explode('=', $lines[$row]);
            $msg->addBodyItem($name, $value);
        }
        return $msg;
    }

    /*     * ********Getteur Setteur******************* */

    public function messageSchemeIdentifier() {
        return $this->msi;
    }

    public function source() {
        return $this->source;
    }

    public function target() {
        return $this->target;
    }

    public function setMessageSchemeIdentifier($messageSchemeIdentifier) {
        $this->msi = $messageSchemeIdentifier;
        return $this;
    }

    public function setSource($value) {
        $this->source = $value;
        return $this;
    }

    public function setTarget($value) {
        $this->target = $value;
        return $this;
    }

    public function getIdentifier() {
        return $this->identifier;
    }

    public function getBody() {
        return $this->body;
    }

}
