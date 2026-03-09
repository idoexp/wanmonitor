<?php
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
  include_file('desktop', '404', 'php');
  die();
}
?>
<form class="form-horizontal">
    <fieldset>
        <legend><i class="fas fa-cog"></i> Paramètres de surveillance WAN</legend>

        <div class="form-group">
            <label class="col-sm-4 control-label">WAN1 - Connexion principale</label>
            <div class="col-sm-2">
                <input class="configKey form-control" data-l1key="wan1name" placeholder="Nom du fournisseur d'accès internet principal (WAN1)">
            </div>
            <label class="col-sm-3 control-label">IP fixe publique de la connexion</label>
            <div class="col-sm-2">
                <input class="configKey form-control" data-l1key="wan1ip"  placeholder="83.42.12.65">
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-4 control-label">WAN2 - Connexion secondaire</label>
            <div class="col-sm-2">
                <input class="configKey form-control" data-l1key="wan2name" placeholder="Nom du fournisseur d'accès internet secondaire (WAN2)">
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-4 control-label">Fréquence de vérification (minutes)</label>
            <div class="col-sm-2">
            <input type="number" class="configKey form-control" data-l1key="refresh_delay" min="1" max="60" value="5" />
            </div>
        </div>
    </fieldset>
</form>