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


class teleinfoBasic {

    public static function parserMessage($_message) {
        if ($_message->getIdentifier() == xPLMessage::xplcmnd) {
            return false;
        }
	$list_event = array();

	$type = $_message->getIdentifier(); // 1,2,3
	$shema = $_message->messageSchemeIdentifier();

        $source = $_message->source();
        $adco = $_message->bodyItem('adco'); // <Adresse du compteur>

        $xPL = xPL::byLogicalId($source, 'xpl');
        if (is_object($xPL)) {
            $list_cmd = $xPL->getCmd();
            foreach ($list_cmd as $cmd) {
		$shema_compare = $cmd->getConfiguration('xPLschema');
		$type_compare = $cmd->getConfiguration('xPLtypeCmd'); //'XPL-STAT'
                $adco_compare = $cmd->getItem('adco');
                $request = $cmd->getItem('request');

                if ($shema==$shema_compare
		&& ($type==xPLMessage::xpltrig && $type_compare=='XPL-TRIG' || $type==xPLMessage::xplstat && $type_compare=='XPL-STAT')
		&&  $adco === $adco_compare
		) {
                    $event_info = array();
                    $event_info['cmd_id'] = $cmd->getId();
/*
[ptec=<Période|tarifaire actuelle>]
[adps=<Avertissement|de dépassement>]
*/
			switch ($request) {
			case "optarif": 
				$event_info['value'] =  $_message->bodyItem('optarif'); //<Option tarifaire>
				break;
			case "isousc": 
				$event_info['value'] =  $_message->bodyItem('isousc'); // <Intensité souscrite>
				break;
			case "motdetat": 
				$event_info['value'] =  $_message->bodyItem('motdetat'); // <Mot d'état du compteur>
				break;
			case "papp": 
				$event_info['value'] =  $_message->bodyItem('papp'); // <Puissance|apparente> (en Volt.ampères)
				break;
			// Abonnement monophase (abo<=4)
			case "iinst": 
				$event_info['value'] =  $_message->bodyItem('iinst'); // <Intensité instantanée> (en ampères)
				break;
			case "imax": 
				$event_info['value'] =  $_message->bodyItem('imax'); // <Intensité maximale appelée> (en ampères)
				break;
			// Abonnement base (abo==1)
			case "base": 
				$event_info['value'] =  $_message->bodyItem('BASE'); // <Index option base> (en Wh)
				break;
			// Abonnement heures creuses (abo==2)
			case "hchc": 
				$event_info['value'] =  $_message->bodyItem('hchc'); // <Heures|creuses> (en Wh)
				break;
			case "hchp": 
				$event_info['value'] =  $_message->bodyItem('hchp'); // <Heures|pleines> (en Wh)
				break;
			case "hhphc": 
				$event_info['value'] =  $_message->bodyItem('hhphc'); // <Horaire|heure pleine/heure creuse>
				break;
			// abonnement Effacement des jours de pointes (EJP) (abo==3)
			case "ejphn": 
				$event_info['value'] =  $_message->bodyItem('EJPHN'); // <Heures|normales> (en Wh)
				break;
			case "ejphpm": 
				$event_info['value'] =  $_message->bodyItem('EJPHPM'); // <Heures|de pointe> (en Wh)
				break;
			case "pejp": 
				$event_info['value'] =  $_message->bodyItem('PEJP'); // <Préavis|début EJP (30min)>
				break;
			// abonnement Tempo (Bleu,blanc,rouge) (abo==4)
			case "bbrhcjb": 
				$event_info['value'] =  $_message->bodyItem('BBRHCJB'); // <Heures|creuses jours bleus> (en Wh)
				break;
			case "bbrhpjb": 
				$event_info['value'] =  $_message->bodyItem('BBRHPJB'); // <Heures|pleines jours bleus> (en Wh)
				break;
			case "bbrhcjw": 
				$event_info['value'] =  $_message->bodyItem('BBRHCJW'); // <Heures|creuses jours blancs> (en Wh)
				break;
			case "bbrhpjw": 
				$event_info['value'] =  $_message->bodyItem('BBRHPJW'); // <Heures|pleines jours blancs> (en Wh)
				break;
			case "bbrhcjr": 
				$event_info['value'] =  $_message->bodyItem('BBRHCJR'); // <Heures|creuses jours rouges> (en Wh)
				break;
			case "bbrhpjr": 
				$event_info['value'] =  $_message->bodyItem('BBRHPJR'); // <Heures|pleines jours rouges> (en Wh)
				break;
			case "demain": 
				$event_info['value'] =  $_message->bodyItem('DEMAIN'); // <Couleur|du lendemain>
				break;
			// Abonnement triphase (abo>=5)
			case "iinst1": 
				$event_info['value'] =  $_message->bodyItem('IINST1'); // <Intensité|instantanée phase 1>
				break;
			case "iinst2": 
				$event_info['value'] =  $_message->bodyItem('IINST2'); // <Intensité|instantanée phase 2>
				break;
			case "iinst3": 
				$event_info['value'] =  $_message->bodyItem('IINST3'); //<Intensité|instantanée phase 3> 
				break;
			case "imax1": 
				$event_info['value'] =  $_message->bodyItem('IMAX1'); // <Intensité|maximale phase 1>
				break;
			case "imax2": 
				$event_info['value'] =  $_message->bodyItem('IMAX2'); // <Intensité|maximale phase 2>
				break;
			case "imax3": 
				$event_info['value'] =  $_message->bodyItem('IMAX3'); // <Intensité|maximale phase 3>
				break;
			case "ppot": 
				$event_info['value'] =  $_message->bodyItem('PPOT'); // <Présence|des potentiels>
				break;
			case "pmax": 
				$event_info['value'] =  $_message->bodyItem('PMAX'); // <Puissance|maximale triphasée>
				break;
			case "adir1": 
				$event_info['value'] =  $_message->bodyItem('ADIR1'); // <Dépassement d'intensité sur la phase 1>
				break;
			case "adir2": 
				$event_info['value'] =  $_message->bodyItem('ADIR2'); // <Dépassement d'intensité sur la phase 2>
				break;
			case "adir3": 
				$event_info['value'] =  $_message->bodyItem('ADIR3'); // <Dépassement d'intensité sur la phase 3>
				break;
			}
                    $list_event[] = $event_info;
                }
            }
        }
        return $list_event;
    }
}

?>
