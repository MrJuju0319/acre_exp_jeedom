<?php
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect('admin')) {
    include_file('desktop', '404', 'php');
    die();
}
?>
<form class="form-horizontal">
    <fieldset>
        <legend><i class="fas fa-shield-alt"></i> {{Centrale ACRE / SPC}}</legend>

        <div class="form-group">
            <label class="col-lg-4 control-label">{{Adresse centrale}}</label>
            <div class="col-lg-4">
                <input class="configKey form-control" data-l1key="spc_host" placeholder="http://192.168.1.100" />
            </div>
        </div>

        <div class="form-group">
            <label class="col-lg-4 control-label">{{Utilisateur web}}</label>
            <div class="col-lg-4">
                <input class="configKey form-control" data-l1key="spc_user" placeholder="Engineer" />
            </div>
        </div>

        <div class="form-group">
            <label class="col-lg-4 control-label">{{PIN / Mot de passe}}</label>
            <div class="col-lg-4">
                <input type="password" class="configKey form-control" data-l1key="spc_pin" placeholder="1111" />
            </div>
        </div>

        <div class="form-group">
            <label class="col-lg-4 control-label">{{Langue SPC}}</label>
            <div class="col-lg-2">
                <input class="configKey form-control" data-l1key="spc_language" placeholder="253" />
            </div>
            <div class="col-lg-4 help-block">253 = Français, 0 = Anglais</div>
        </div>

        <legend><i class="fas fa-broadcast-tower"></i> {{MQTT}}</legend>

        <div class="form-group">
            <label class="col-lg-4 control-label">{{Broker MQTT}}</label>
            <div class="col-lg-4">
                <input class="configKey form-control" data-l1key="mqtt_host" placeholder="127.0.0.1" />
            </div>
        </div>

        <div class="form-group">
            <label class="col-lg-4 control-label">{{Port MQTT}}</label>
            <div class="col-lg-2">
                <input class="configKey form-control" data-l1key="mqtt_port" placeholder="1883" />
            </div>
        </div>

        <div class="form-group">
            <label class="col-lg-4 control-label">{{Utilisateur MQTT}}</label>
            <div class="col-lg-4">
                <input class="configKey form-control" data-l1key="mqtt_user" placeholder="vide si aucun" />
            </div>
        </div>

        <div class="form-group">
            <label class="col-lg-4 control-label">{{Mot de passe MQTT}}</label>
            <div class="col-lg-4">
                <input type="password" class="configKey form-control" data-l1key="mqtt_pass" placeholder="vide si aucun" />
            </div>
        </div>

        <div class="form-group">
            <label class="col-lg-4 control-label">{{Base topic}}</label>
            <div class="col-lg-4">
                <input class="configKey form-control" data-l1key="mqtt_base_topic" placeholder="acre_XXX" />
            </div>
        </div>

        <legend><i class="fas fa-cogs"></i> {{Watchdog}}</legend>

        <div class="form-group">
            <label class="col-lg-4 control-label">{{Intervalle refresh}}</label>
            <div class="col-lg-2">
                <input class="configKey form-control" data-l1key="watchdog_refresh_interval" placeholder="2" />
            </div>
            <div class="col-lg-4 help-block">En secondes. acre_exp accepte les décimales, minimum conseillé : 0.2.</div>
        </div>

        <div class="form-group">
            <label class="col-lg-4 control-label">{{Intervalle état centrale}}</label>
            <div class="col-lg-2">
                <input class="configKey form-control" data-l1key="watchdog_controller_refresh_interval" placeholder="60" />
            </div>
        </div>
    </fieldset>
</form>
