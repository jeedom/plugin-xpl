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
require_once dirname(__FILE__) . '/../class/xpl.core.class.php';
include_file('core', 'xpl', 'class', 'xpl');
include_file('core', 'xpl', 'config', 'xpl');

class sensorBasic {

    public static function parserMessage($_message) {
        if ($_message->getIdentifier() == xPLMessage::xplcmnd) {
            return false;
        }
        $source = $_message->source();
        $device = $_message->bodyItem('device');
        $type = $_message->bodyItem('type');
        $value = $_message->bodyItem('current');
        $xPL = xPL::byLogicalId($source, 'xpl');
        if (is_object($xPL)) {
            $list_cmd = $xPL->getCmd();
            foreach ($list_cmd as $cmd) {
                $device_compare = $cmd->getItem('device');
                $type_compare = $cmd->getItem('type');
                if ($device === $device_compare && $type === $type_compare) {
                    $event_info = array();
                    $event_info['cmd_id'] = $cmd->getId();
                    $event_info['value'] = $value;
                    return array($event_info);
                }
            }
        }
        return array();
    }

}

?>
