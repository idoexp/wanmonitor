
<?php
/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';
class wanmonitor extends eqLogic {
  public static function cron() {
    $eqLogics = self::byType('wanmonitor', true);
    $delay = intval(config::byKey('refresh_delay', 'wanmonitor', 5));    

    foreach ($eqLogics as $eqLogic) {
      $lastCheck = $eqLogic->getConfiguration('last_check', 0);
      // Exécute le cron si le délai depuis le dernier check est supérieur ou égal au délai configuré 'refresh_delay'
      if ((time() - $lastCheck) >= ($delay * 60)) {
        log::add('wanmonitor', 'debug', 'Exécution du cron automatique toutes les ' . $delay . ' minutes pour l\'équipement : ' . $eqLogic->getName());
        $eqLogic->checkWanStatus();
        $eqLogic->setConfiguration('last_check', time());
        $eqLogic->save();
      }

    }

  }
  /*
  public static function cron5() {
  }

  public static function cron10() {
  }
  */
  /*     * *********************Méthodes d'instance************************* */

  // Fonction exécutée automatiquement avant la création de l'équipement
  public function preInsert() {
  }

  // Fonction exécutée automatiquement après la création de l'équipement
  public function postInsert() {
  }

  // Fonction exécutée automatiquement avant la mise à jour de l'équipement
  public function preUpdate() {
  }


  // Fonction exécutée automatiquement après la mise à jour de l'équipement
  public function postUpdate() {
  }

  // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
  public function preSave() {
  }

  // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
  public function postSave() {
    $this->createCmd('Etat WAN', 'etat', 'info', 'string');
    $this->createCmd('FAI', 'fai', 'info', 'string');
    $this->createCmd('IP Publique', 'ip_publique', 'info', 'string');
    $this->createCmd('Historique', 'historique', 'info', 'string');
    $this->createCmd('Stat WAN1 (min)', 'stats_wan1', 'info', 'numeric');
    $this->createCmd('Stat WAN2 (min)', 'stats_wan2', 'info', 'numeric');
    $this->createCmd('Action vers WAN2', 'action_wan2', 'action', 'other');
    $this->createCmd('Action retour WAN1', 'action_wan1', 'action', 'other');
    $this->createCmd('Rafraîchir', 'refresh', 'action', 'other');
  }


  // Fonction exécutée automatiquement avant la suppression de l'équipement
  public function preRemove() {
  }

  // Fonction exécutée automatiquement après la suppression de l'équipement
  public function postRemove() {
  }

  public function getPublicIP(){
    $ipPub = trim(shell_exec("curl -s --max-time 5 https://api.ipify.org"));
    if (empty($ipPub)) {
      log::add('wanmonitor', 'error', 'Impossible de récupérer l\'IP publique');
      return null;
    }
    return $ipPub;
  }

  public function checkWanStatus() {
    $ipPub = $this->getPublicIP();
    $wan1Ip = config::byKey('wan1ip', 'wanmonitor');
    $wanActif = ($ipPub == $wan1Ip) ? 'WAN1' : 'WAN2';
    $lastWan = $this->getCmd(null, 'etat')->execCmd();

    if ($wanActif == 'WAN1'){
      $fai = config::byKey('wan1name', 'wanmonitor', 'FAI 1');
    } else {
      $fai = config::byKey('wan2name', 'wanmonitor', 'FAI 2');
    }

    log::add('wanmonitor', 'debug', 'Check Wan Status');
    if ($wanActif != $lastWan) {
      $log = date('Y-m-d H:i:s') . " - Passage de $lastWan à $wanActif (IP: $ipPub)";
      $this->appendToHistory($log);
      $this->updateWanStats($lastWan);

      if ($wanActif == 'WAN2') {
        $this->triggerCommand('action_wan2');
        log::add('wanmonitor', 'debug', 'Action vers WAN2 déclenchée');
        
      } elseif ($wanActif == 'WAN1') {
        log::add('wanmonitor', 'debug', 'Action vers WAN1 déclenchée');
        $this->triggerCommand('action_wan1');
      }
      $this->checkAndUpdateCmd('etat', $wanActif);
    }
    
    $this->checkAndUpdateCmd('fai', $fai);
    $this->checkAndUpdateCmd('ip_publique', $ipPub);
  }

  public function appendToHistory($text) {
    $historyCmd = $this->getCmd(null, 'historique');
    $prev = $historyCmd->execCmd();
    $new = $text . "\n" . $prev;
    $this->checkAndUpdateCmd('historique', substr($new, 0, 1000));
  }

  private function updateWanStats($wan) {
    if (!in_array($wan, ['WAN1', 'WAN2'])) return;
    $cmd = $this->getCmd(null, 'stats_' . strtolower($wan));
    $current = intval($cmd->execCmd());
    $this->checkAndUpdateCmd($cmd->getLogicalId(), $current + 5);
  }

private function triggerCommand($logicalId) {
  $cmd = $this->getCmd(null, $logicalId);
  if (is_object($cmd)) {
    log::add('wanmonitor', 'debug', "Exécution dela commande : $logicalId (ID: " . $cmd->getId() . ")");
    scenarioExpression::createAndExec('action', '#' . $cmd->getId() . '#');
  } else {
    log::add('wanmonitor', 'warning', "⚠️ Commande '$logicalId' introuvable pour l'équipement " . $this->getName());
  }
}



  public function toHtml($_version = 'dashboard') {
    $replace = $this->preToHtml($_version);
    if (!is_array($replace)) {
      return $replace;
    }
    $version = jeedom::versionAlias($_version);
    $commandsToReplace = array(
    'ip_publique',
    'fai',
    'etat',
    'historique'
    );
    // Parcourir les commandes à remplacer
    foreach ($commandsToReplace as $commandName) {
      $cmd = $this->getCmd(null, $commandName);
      if (is_object($cmd) && $cmd->getType() == 'info') {
        $commandValue = $cmd->execCmd();
        $replace['#' . $commandName . '#'] = $commandValue;
      } else {
        $replace['#' . $commandName . '#'] = 'Valeur indisponible';
      }
    }

    return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'tile_wanmonitor', 'wanmonitor')));

  }

  private function createCmd($name, $logicalId, $type, $subtype) {
    if ($this->getCmd(null, $logicalId)) return;
    $cmd = new wanmonitorCmd();
    $cmd->setLogicalId($logicalId);
    $cmd->setEqLogic_id($this->getId());
    $cmd->setName($name);
    $cmd->setType($type);
    $cmd->setSubType($subtype);
    $cmd->setIsVisible(1);
    $cmd->setDisplay('showOnDashboard', 1); 
    $cmd->save();
  }


}


class wanmonitorCmd extends cmd {

  // Exécution d'une commande
  public function execute($_options = array()) {
    $eqLogic = $this->getEqLogic();
    switch ($this->getLogicalId()) {
      case 'refresh':
        $eqLogic->checkWanStatus();
        $eqLogic->appendToHistory("Rafraîchissement manuel de l'état WAN");
        break;
    }
    return null;
  }

}
