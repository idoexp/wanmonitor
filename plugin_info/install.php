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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function getPublicIP(){
    $ch = curl_init('https://api.ipify.org');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    $ipPub = trim(curl_exec($ch));
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (empty($ipPub) || $httpCode != 200) {
        log::add('wanmonitor', 'error', 'Impossible de récupérer l\'IP publique');
        return null;
    }
    log::add('wanmonitor', 'info', 'Récupération de l\'IP publique lors de l\'installation : ' . $ipPub);
    return $ipPub;
}
// Fonction exécutée automatiquement après l'installation du plugin
function wanmonitor_install() {

    $wan1ip   = getPublicIP(); // Récupère l'IP publique actuelle
    $wan2ip   =  '';
    $wan1name = 'FAI 1';
    $wan2name = 'FAI 2';

    $refresh_delay = 5; // minutes
    config::save('wan1ip', $wan1ip, 'wanmonitor');
    config::save('wan1name', $wan1name, 'wanmonitor');
    config::save('wan2name', $wan2name, 'wanmonitor');
    config::save('refresh_delay', $refresh_delay, 'wanmonitor');
    log::add('wanmonitor', 'info', "Installation des valeurs par défaut.");

}

// Fonction exécutée automatiquement après la mise à jour du plugin
function wanmonitor_update() {
    // Activer l'historisation sur la commande etat pour les installations existantes
    foreach (eqLogic::byType('wanmonitor') as $eqLogic) {
        $cmd = $eqLogic->getCmd(null, 'etat');
        if (is_object($cmd) && $cmd->getIsHistorized() != 1) {
            $cmd->setIsHistorized(1);
            $cmd->save();
            log::add('wanmonitor', 'info', 'Historisation activée sur la commande etat pour ' . $eqLogic->getName());
        }
    }
}

// Fonction exécutée automatiquement après la suppression du plugin
function wanmonitor_remove() {
}
