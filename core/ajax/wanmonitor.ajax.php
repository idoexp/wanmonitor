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

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

  /* Fonction permettant l'envoi de l'entête 'Content-Type: application/json'
    En V3 : indiquer l'argument 'true' pour contrôler le token d'accès Jeedom
    En V4 : autoriser l'exécution d'une méthode 'action' en GET en indiquant le(s) nom(s) de(s) action(s) dans un tableau en argument
  */
    ajax::init();

    // Fonction utilitaire pour calculer les durées par état
    function computeStats($cmdId, $startDate, $now, $currentState) {
        $stats = array('WAN1' => 0, 'WAN2' => 0, 'DOWN' => 0);
        $startTs = strtotime($startDate);
        $nowTs = strtotime($now);

        $events = history::all($cmdId, $startDate, $now);
        // Trouver l'état initial (dernier état avant la fenêtre)
        $before = history::all($cmdId, null, $startDate);
        $initState = $currentState;
        if (count($before) > 0) {
            $last = end($before);
            $initState = $last->getValue();
        } elseif (count($events) > 0) {
            $initState = $events[0]->getValue();
        }

        if (count($events) === 0) {
            if (isset($stats[$initState])) {
                $stats[$initState] = $nowTs - $startTs;
            }
        } else {
            $firstTs = strtotime($events[0]->getDatetime());
            if ($firstTs > $startTs && isset($stats[$initState])) {
                $stats[$initState] += $firstTs - $startTs;
            }
            for ($i = 0; $i < count($events); $i++) {
                $segStart = strtotime($events[$i]->getDatetime());
                $segEnd = ($i + 1 < count($events)) ? strtotime($events[$i + 1]->getDatetime()) : $nowTs;
                $val = $events[$i]->getValue();
                if (isset($stats[$val])) {
                    $stats[$val] += $segEnd - $segStart;
                }
            }
        }
        return $stats;
    }

    if (init('action') == 'getWanHistory') {
        $eqLogicId = init('eqLogic_id');
        $eqLogic = eqLogic::byId($eqLogicId);
        if (!is_object($eqLogic) || $eqLogic->getEqType_name() != 'wanmonitor') {
            throw new Exception(__('Équipement introuvable', __FILE__));
        }

        $cmd = $eqLogic->getCmd(null, 'etat');
        if (!is_object($cmd)) {
            throw new Exception(__('Commande etat introuvable', __FILE__));
        }

        $now = date('Y-m-d H:i:s');
        $start24h = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $currentState = $cmd->execCmd();
        $cmdId = $cmd->getId();

        // Historique complet pour la liste d'événements (depuis le début)
        $historyAll = history::all($cmdId, null, $now);
        $allEvents = array();
        foreach ($historyAll as $h) {
            $allEvents[] = array(
                'datetime' => $h->getDatetime(),
                'value' => $h->getValue()
            );
        }

        // Filtrer les 24 dernières heures pour la timeline
        $history24h = array();
        $initialState24h = $currentState;
        foreach ($allEvents as $evt) {
            if ($evt['datetime'] >= $start24h) {
                $history24h[] = $evt;
            } else {
                $initialState24h = $evt['value'];
            }
        }

        // Stats par période
        $start30d = date('Y-m-d H:i:s', strtotime('-30 days'));
        $startYear = date('Y') . '-01-01 00:00:00';

        $stats24h = computeStats($cmdId, $start24h, $now, $currentState);
        $stats30d = computeStats($cmdId, $start30d, $now, $currentState);
        $statsYear = computeStats($cmdId, $startYear, $now, $currentState);

        $wan1name = config::byKey('wan1name', 'wanmonitor', 'FAI 1');
        $wan2name = config::byKey('wan2name', 'wanmonitor', 'FAI 2');

        ajax::success(array(
            'history24h' => $history24h,
            'historyAll' => $allEvents,
            'initialState' => $initialState24h,
            'current' => $currentState,
            'stats' => array(
                '24h' => $stats24h,
                '30d' => $stats30d,
                'year' => $statsYear
            ),
            'wan1name' => $wan1name,
            'wan2name' => $wan2name
        ));
    }

    throw new Exception(__('Aucune méthode correspondante à', __FILE__) . ' : ' . init('action'));
    /*     * *********Catch exeption*************** */
}
catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}
