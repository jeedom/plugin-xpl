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

// 2014-08-22 00:10:14  xpl-trig { hop=1 source=bnz-rfxcomrx.vesta target=* } homeeasy.basic { address=0x94339a unit=4 command=on }
// 2014-08-22 00:10:20  xpl-trig { hop=1 source=bnz-rfxcomrx.vesta target=* } homeeasy.basic { address=0x94339a unit=4 command=off }

/* ------------------------------------------------------------ Inclusions */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/../class/xpl.core.class.php';
include_file('core', 'xpl', 'class', 'xpl');
include_file('core', 'xpl', 'config', 'xpl');


class lightingDevice {

    public static function parserMessage($_message) {
        if ($_message->getIdentifier() == xPLMessage::xplcmnd) {
            return false;
        }
	$type = $_message->getIdentifier(); // 1,2,3
	$shema = $_message->messageSchemeIdentifier();

        $source = $_message->source();
        $network = $_message->bodyItem('network');
        $device = $_message->bodyItem('device');
        $channel = $_message->bodyItem('channel');

        $state = $_message->bodyItem('state');
        $level = $_message->bodyItem('level');

        $xPL = xPL::byLogicalId($source, 'xpl');
        if (is_object($xPL)) {
            $list_cmd = $xPL->getCmd();
            foreach ($list_cmd as $cmd) {
		$shema_compare = $cmd->getConfiguration('xPLschema');
		$type_compare = $cmd->getConfiguration('xPLtypeCmd'); //'XPL-TRIG'
                $network_compare = $cmd->getItem('network');
                $device_compare = $cmd->getItem('device');
                $channel_compare = $cmd->getItem('channel');

                if ($shema==$shema_compare
		&& ($type==xPLMessage::xpltrig && $type_compare=='XPL-TRIG' || $type==xPLMessage::xplstat && $type_compare=='XPL-STAT')
		&&  $network === $network_compare 
		&&  $device === $device_compare 
		&&  $channel === $channel_compare 
		) {
                    $event_info = array();
                    $event_info['cmd_id'] = $cmd->getId();
                    $event_info['value'] = $state;
                    return array($event_info);
                }
            }
        }
        return array();
    }
}

?>
