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

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
include_file('core', 'xpl.core', 'class', 'xpl');
include_file('core', 'xpl', 'config', 'xpl');

class xpl extends eqLogic {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */

    public static function deamon() {
        $xplinstance = XPLInstance::getXPLInstance();
        $eventReturn = $xplinstance->doEvents();
        if ($eventReturn == 1) {
            xPL::proccessMessageEvent($xplinstance->getMessage());
        }
    }

    public static function newDeviceFromxPLNetwork($_logicalId) {
        $xPl = self::byLogicalId($_logicalId, 'xpl');
        if (is_object($xPl)) {
            $xPl->setIsEnable(1);
            $xPl->save();
        } else {
            $xPl = new xpl();
            $xPl->setName($_logicalId);
            $xPl->setLogicalId($_logicalId);
            $xPl->setObject_id(null);
            $xPl->setEqType_name('xpl');
            $xPl->setIsEnable(1);
            $xPl->setIsVisible(1);
            $xPl->save();
        }
    }

    public static function removedDeviceFromxPLNetwork($_logicalId) {
        $xPl = self::byLogicalId($_logicalId, 'xpl');
        $xPl->setEnable(0);
    }

    public static function proccessMessageEvent($_message = null) {
        switch ($_message->messageSchemeIdentifier()) {
            case 'sensor.basic':
                require_once dirname(__FILE__) . '/../schema/sensor.basic.class.php';
                $list_event = sensorBasic::parserMessage($_message);
                break;
            case 'homeeasy.basic':
                require_once dirname(__FILE__) . '/../schema/homeeasy.basic.class.php';
                $list_event = homeeasyBasic::parserMessage($_message);
                break;
            case 'x10.basic':
                require_once dirname(__FILE__) . '/../schema/x10.basic.class.php';
                $list_event = x10Basic::parserMessage($_message);
                break;
            case 'x10.security':
                require_once dirname(__FILE__) . '/../schema/x10.security.class.php';
                $list_event = x10Security::parserMessage($_message);
                break;
            case 'ac.basic':
                require_once dirname(__FILE__) . '/../schema/ac.basic.class.php';
                $list_event = acBasic::parserMessage($_message);
                break;
            case 'remote.basic':
                require_once dirname(__FILE__) . '/../schema/remote.basic.class.php';
                $list_event = remoteBasic::parserMessage($_message);
                break;
            case 'osd.basic':
                require_once dirname(__FILE__) . '/../schema/osd.basic.class.php';
                $list_event = osdBasic::parserMessage($_message);
                break;
            case 'lighting.device':
                require_once dirname(__FILE__) . '/../schema/lighting.device.class.php';
                $list_event = lightingDevice::parserMessage($_message);
                break;
            default:
                break;
        }
        $replace = array(
            'on' => 1,
            'off' => 0,
            'alert' => 1,
            'normal' => 0,
            'motion' => 1,
            'light' => 1,
            'dark' => 0,
            'arm-home' => 1,
            'arm-away' => 1,
            'disarm' => 0,
            'panic' => 1,
            'lights-on' => 1,
            'lights-off' => 0,
        );


        if (is_array($list_event)) {
            foreach ($list_event as $event) {
                $cmd = xPLCmd::byId($event['cmd_id']);
                $event['value'] = str_replace(array_keys($replace), array_values($replace), $event['value']);
                if ($cmd->getType() == 'info') {
                    cache::set('xpl' . $cmd->getId(), $event['value']);
                }
                $cmd->event($event['value']);
            }
        }
        return;
    }

    /*     * *********************Methode d'instance************************* */


    /*     * **********************Getteur Setteur*************************** */
}

class xPLCmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    public function preSave() {
        if ($this->getConfiguration('xPLbody') == '') {
            throw new Exception(__('xPL Body ne peut etre vide', __FILE__));
        }
        if ($this->getConfiguration('xPLtypeCmd') == '') {
            throw new Exception(__('xPL type commande ne peut etre vide', __FILE__));
        }
        if ($this->getConfiguration('xPLschema') == '') {
            throw new Exception(__('Le schema xPL ne peut etre vide', __FILE__));
        }
        $this->setEventOnly(1);
    }

    public function execute($_options = null) {
        $xPLinstance = XPLInstance::getXPLInstance(false);
        $source = $xPLinstance->getThisDevice()->deviceName();
        $_target = $this->getEqLogic()->getLogicalId();
        if (trim($_target) == '') {
            $_target = '*';
        }

        if ($_options != null) {
            switch ($this->getType()) {
                case 'action' :
                    switch ($this->getSubType()) {
                        case 'slider':
                            $body = str_replace('#slider#', $_options['slider'], $this->getConfiguration('xPLbody'));
                            break;
                        case 'color':
                            $body = str_replace('#color#', $_options['color'], $this->getConfiguration('xPLbody'));
                            break;
                        case 'message':
                            $replace = array('#title#', '#message#');
                            $replaceBy = array($_options['title'], $_options['message']);
                            if ($_options['message'] == '' || $_options['title'] == '') {
                                throw new Exception(__('Le message et le sujet ne peuvent être vide', __FILE__));
                            }
                            $body = str_replace($replace, $replaceBy, $this->getConfiguration('xPLbody'));
                            break;
                    }
                    break;
            }
        } else {
            $body = $this->getConfiguration('xPLbody');
            $_target = "*"; //A supprimer plus tard
        }

        $message = '';
        switch ($this->getConfiguration('xPLtypeCmd')) {
            case 'XPL-CMND':
                $message .= "xpl-cmnd\n";
                break;
            case 'XPL-STAT':
                $message .= "xpl-stat\n";
                break;
            case 'XPL-TRIG':
                $message .= "xpl-trig\n";
                break;
            default:
                return "";
        }
        $message .= "{\n";
        $message .= "hop=1\n";
        $message .= sprintf("source=%s\n", $source);
        $message .= sprintf("target=%s\n", $_target);
        $message .= "}\n";
        $message .= $this->getConfiguration('xPLschema') . "\n";
        $message .= "{\n";
        $message .= $body;
        $message .= "\n}";
        $message .= "\n";
        $message .= "\n";
        $xPLinstance->sendPlainTextMessage($message);

        if ($this->getType() == 'info') {
            $mc = cache::byKey('xpl' . $this->getId());
            return $mc->getValue();
        }
        return '';
    }

    public function getItem($_key) {
        $lines = explode("\n", $this->getConfiguration('xPLbody'));
        for ($row = 0, $sLines = count($lines); $row < $sLines; $row++) {
            list($name, $value) = explode('=', $lines[$row]);
            if ($name == $_key) {
                return $value;
            }
        }
        return false;
    }

    /*     * **********************Getteur Setteur*************************** */
}

?>
