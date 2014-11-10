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

require_once dirname(__FILE__) . '/xpl.schema.php';

define('DEFAULT_HBEAT_INTERVAL', 5);
define('XPL_PORT', 3865);
define('XPL_VENDOR', 'xpl');
define('XPL_DEVICE', 'jeedom');
define('XPL_INSTANCE', gethostname());
define('XPL_IP', "0.0.0.0");
define('XPL_MAX_RETRY_CONNEXION_TO_HUB', '300');


global $XPL_BODY;
$XPL_BODY = array(
    'control.basic' => array(
        'XPL-CMND' => "type=<sensor type>
                       current=<value>
                       [data1=<additional data>]
                       [name=]",
    ),
    'sensor.basic' => array(
        'XPL-CMND' => "request=current
                       device=<sensor name>
                       type=<sensor type>
                       [name=]",
        'XPL-TRIG' => "device=<sensor name>
                       type=<sensor type>
                       current=<current value>
                       [lowest=<lowest recorded value>]
                       [highest=<highest recorded value>]
                       [units=<optional specifier for current units]>",
    ),
    'homeeasy.basic' => array(
        'XPL-CMND' => "address=<sensor address>
                       unit=<sensor unit>
                       command=<command value>",
        'XPL-TRIG' => "address=<sensor address>
                       unit=<sensor unit>
                       command=<command value>",
    ),
    'x10.basic' => array(
        'XPL-CMND' => "device=<sensor address>
                       command=<command value>",
        'XPL-TRIG' => "device=<sensor address>
                       command=<command value>",
    ),
    'x10.security' => array(
        'XPL-TRIG' => "command=<command value>
                       device=<module address>
                       type=<module type>
                       [temper=]
                       [low-battery=]
                       [delay=",
    ),
     'ac.basic' => array(
        'XPL-CMND' => "address=<sensor address>
                       unit=<sensor unit>
                       command=<command value>
                       [level=<level>]",
					   
        'XPL-TRIG' => "address=<sensor address>
                       unit=<sensor unit>
                       command=<command value>
                       [level=<level>]",
    ),
    'osd.basic' => array(
        'XPL-CMND' => "command=clear|write|exclusive|release
                       text=<text to display. each line should be seperated by the \n seperator>
                       [row=<display row>]
                       [column=<display from column>]
                       [delay=<seconds to display>]",
    ),
    'remote.basic' => array(
        'XPL-TRIG' => "keys=<keys remote>
                       device=<device modele>
                       [zone=<command value>]",
        'XPL-CMND' => "keys=<keys remote>
                       device=<device modele>
                       [zone=<command value>]
		       [delay=<delay>]",
    ),
);
?>
