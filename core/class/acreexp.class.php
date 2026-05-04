<?php

class acreexp extends eqLogic {
    public static function getMqttConfig() {
        $config = array(
            'host' => config::byKey('mqtt_host', 'acreexp', '127.0.0.1'),
            'port' => config::byKey('mqtt_port', 'acreexp', '1883'),
            'user' => config::byKey('mqtt_user', 'acreexp', ''),
            'pass' => config::byKey('mqtt_pass', 'acreexp', ''),
        );

        if (config::byKey('use_mqtt_manager', 'acreexp', 0) != 1) {
            return $config;
        }

        // Compatibilité avec plugin officiel mqtt2
        $managerHost = trim((string) config::byKey('mqtt::address', 'mqtt2', ''));
        $managerPort = trim((string) config::byKey('mqtt::port', 'mqtt2', ''));
        $managerUser = (string) config::byKey('mqtt::username', 'mqtt2', '');
        $managerPass = (string) config::byKey('mqtt::password', 'mqtt2', '');

        if ($managerHost != '') {
            $config['host'] = $managerHost;
        }
        if ($managerPort != '') {
            $config['port'] = $managerPort;
        }
        $config['user'] = $managerUser;
        $config['pass'] = $managerPass;

        return $config;
    }

    public static function deamon_info() {
        $return = array();
        $return['log'] = 'acreexpd';
        $return['state'] = 'nok';
        $return['launchable'] = 'ok';

        $pid = trim(shell_exec("pgrep -f 'resources/acred/acred.py'"));
        if ($pid != '') {
            $return['state'] = 'ok';
        }

        return $return;
    }

    public static function deamon_start($_debug = false) {
        self::deamon_stop();

        $mqttConfig = self::getMqttConfig();
        $mqttHost = $mqttConfig['host'];
        $mqttPort = $mqttConfig['port'];
        $mqttUser = $mqttConfig['user'];
        $mqttPass = $mqttConfig['pass'];
        $baseTopic = config::byKey('mqtt_base_topic', 'acreexp', 'acre_XXX');

        $logLevel = 'info';
        if ($_debug || log::convertLogLevel(log::getLogLevel('acreexp')) == 'debug') {
            $logLevel = 'debug';
        }

        $python = '/usr/bin/python3';
        $daemon = realpath(dirname(__FILE__) . '/../../resources/acred/acred.py');
        $callback = network::getNetworkAccess('internal') . '/plugins/acreexp/core/php/jeeAcreexp.php';
        $apikey = jeedom::getApiKey('acreexp');

        $cmd = $python . ' ' . escapeshellarg($daemon);
        $cmd .= ' --mqtt-host ' . escapeshellarg($mqttHost);
        $cmd .= ' --mqtt-port ' . escapeshellarg($mqttPort);
        $cmd .= ' --base-topic ' . escapeshellarg($baseTopic);
        $cmd .= ' --callback ' . escapeshellarg($callback);
        $cmd .= ' --apikey ' . escapeshellarg($apikey);
        $cmd .= ' --loglevel ' . escapeshellarg($logLevel);

        if ($mqttUser != '') {
            $cmd .= ' --mqtt-user ' . escapeshellarg($mqttUser);
            $cmd .= ' --mqtt-pass ' . escapeshellarg($mqttPass);
        }

        $cmd .= ' >> ' . log::getPathToLog('acreexpd') . ' 2>&1 &';

        log::add('acreexp', 'info', 'Lancement démon acreexpd');
        log::add('acreexp', 'debug', 'Commande démon : ' . $cmd);

        exec($cmd);

        sleep(2);

        $info = self::deamon_info();
        if ($info['state'] != 'ok') {
            log::add('acreexp', 'error', 'Le démon acreexpd ne semble pas démarré');
        } else {
            log::add('acreexp', 'info', 'Démon acreexpd démarré');
        }
    }

    public static function deamon_stop() {
        log::add('acreexp', 'info', 'Arrêt démon acreexpd');
        exec("pkill -f 'resources/acred/acred.py' || true");
        sleep(1);
    }

    public static function dependancy_info() {
        $return = array();
        $return['log'] = 'acreexp_update';
        $return['progress_file'] = jeedom::getTmpFolder('acreexp') . '/dependance';
        $return['state'] = 'ok';

        if (!file_exists('/usr/local/bin/acre_exp_watchdog.py')) {
            $return['state'] = 'nok';
        }

        return $return;
    }

    public static function dependancy_install() {
        log::remove('acreexp_update');

        $script = realpath(dirname(__FILE__) . '/../../resources/install.sh');
        $cmd = '/bin/bash ' . escapeshellarg($script);
        $cmd .= ' >> ' . log::getPathToLog('acreexp_update') . ' 2>&1 &';

        log::add('acreexp', 'info', 'Installation des dépendances acreexp');
        exec($cmd);
    }

    public static function handleMqttMessage($_topic, $_payload) {
        $baseTopic = config::byKey('mqtt_base_topic', 'acreexp', 'acre_XXX');
        $topic = trim($_topic);
        $payload = trim($_payload);

        if (strpos($topic, $baseTopic . '/') !== 0) {
            log::add('acreexp', 'debug', 'Topic ignoré : ' . $topic);
            return;
        }

        $relative = substr($topic, strlen($baseTopic) + 1);
        $parts = explode('/', $relative);

        if (count($parts) < 2) {
            log::add('acreexp', 'debug', 'Topic trop court : ' . $topic);
            return;
        }

        $type = $parts[0];
        $id = $parts[1];
        $field = isset($parts[2]) ? $parts[2] : 'value';
        self::ensureAlarmStructure($type, $id);

        $eqLogicalId = 'acreexp_' . $type . '_' . $id;

        $eqLogic = self::byLogicalId($eqLogicalId, 'acreexp');
        if (!is_object($eqLogic)) {
            $eqLogic = new self();
            $eqLogic->setEqType_name('acreexp');
            $eqLogic->setLogicalId($eqLogicalId);
            $eqLogic->setName(ucfirst($type) . ' ' . $id);
            $eqLogic->setIsEnable(1);
            $eqLogic->setIsVisible(1);
            $eqLogic->setConfiguration('acre_type', $type);
            $eqLogic->setConfiguration('acre_id', $id);
            $eqLogic->save();

            log::add('acreexp', 'info', 'Création équipement : ' . $eqLogic->getName());
            self::createDefaultCommands($eqLogic, $type, $id);
        }

        $cmdLogicalId = $field;

        $cmd = $eqLogic->getCmd(null, $cmdLogicalId);
        if (!is_object($cmd)) {
            $cmd = new acreexpCmd();
            $cmd->setName($field);
            $cmd->setLogicalId($cmdLogicalId);
            $cmd->setEqLogic_id($eqLogic->getId());
            $cmd->setType('info');
            $cmd->setSubType('string');
            $cmd->setIsVisible(1);
            $cmd->setIsHistorized(0);
            $cmd->save();

            log::add('acreexp', 'info', 'Création commande info : ' . $eqLogic->getName() . ' / ' . $field);
        }

        $cmd->event($payload);
    }

    private static function ensureAlarmStructure($_type, $_id) {
        $centralId = 'acreexp_centrale';
        $central = self::byLogicalId($centralId, 'acreexp');
        if (!is_object($central)) {
            $central = new self();
            $central->setEqType_name('acreexp');
            $central->setLogicalId($centralId);
            $central->setName('Centrale Alarme');
            $central->setIsEnable(1);
            $central->setIsVisible(1);
            $central->setConfiguration('acre_type', 'centrale');
            $central->setConfiguration('acre_id', 'master');
            $central->save();
            log::add('acreexp', 'info', 'Création automatique de la centrale d\'alarme');
        }

        if (!in_array($_type, array('zones', 'outputs', 'inputs', 'entrees', 'sorties'))) {
            return;
        }

        $pointId = 'acreexp_point_' . $_type . '_' . $_id;
        $point = self::byLogicalId($pointId, 'acreexp');
        if (is_object($point)) {
            return;
        }

        $point = new self();
        $point->setEqType_name('acreexp');
        $point->setLogicalId($pointId);
        $point->setName('Point ' . strtoupper($_type) . ' ' . $_id);
        $point->setIsEnable(1);
        $point->setIsVisible(1);
        $point->setConfiguration('acre_type', 'point');
        $point->setConfiguration('acre_id', $_id);
        $point->setConfiguration('point_kind', $_type);
        $point->save();

        log::add('acreexp', 'info', 'Création automatique du point E/S : ' . $point->getName());
    }

    private static function createDefaultCommands($_eqLogic, $_type, $_id) {
        if ($_type == 'secteurs' || $_type == 'areas') {
            self::addAction($_eqLogic, 'MES totale', 'set/fullset');
            self::addAction($_eqLogic, 'MHS', 'set/unset');
            self::addAction($_eqLogic, 'Nuit', 'set/nightset');
            self::addAction($_eqLogic, 'Partielle B', 'set/partsetb');
        }

        if ($_type == 'doors' || $_type == 'portes') {
            self::addAction($_eqLogic, 'Normal', 'set/normal');
            self::addAction($_eqLogic, 'Verrouiller', 'set/lock');
            self::addAction($_eqLogic, 'Déverrouiller', 'set/unlock');
            self::addAction($_eqLogic, 'Impulsion', 'set/pulse');
        }

        if ($_type == 'outputs' || $_type == 'sorties') {
            self::addAction($_eqLogic, 'ON', 'set/on');
            self::addAction($_eqLogic, 'OFF', 'set/off');
        }

        if ($_type == 'zones') {
            self::addAction($_eqLogic, 'Inhiber', 'set/inhibit');
            self::addAction($_eqLogic, 'Dé-inhiber', 'set/uninhibit');
            self::addAction($_eqLogic, 'Isoler', 'set/isolate');
            self::addAction($_eqLogic, 'Dé-isoler', 'set/unisolate');
            self::addAction($_eqLogic, 'Test JDB', 'set/walktest');
            self::addAction($_eqLogic, 'Restaurer', 'set/restore');
        }
    }

    private static function addAction($_eqLogic, $_name, $_topicSuffix) {
        $cmd = $_eqLogic->getCmd(null, $_topicSuffix);
        if (is_object($cmd)) {
            return;
        }

        $cmd = new acreexpCmd();
        $cmd->setName($_name);
        $cmd->setLogicalId($_topicSuffix);
        $cmd->setEqLogic_id($_eqLogic->getId());
        $cmd->setType('action');
        $cmd->setSubType('other');
        $cmd->setIsVisible(1);
        $cmd->save();
    }
}

class acreexpCmd extends cmd {

    public function execute($_options = array()) {
        $eqLogic = $this->getEqLogic();

        $baseTopic = config::byKey('mqtt_base_topic', 'acreexp', 'acre_XXX');
        $mqttConfig = acreexp::getMqttConfig();
        $mqttHost = $mqttConfig['host'];
        $mqttPort = $mqttConfig['port'];
        $mqttUser = $mqttConfig['user'];
        $mqttPass = $mqttConfig['pass'];

        $type = $eqLogic->getConfiguration('acre_type');
        $id = $eqLogic->getConfiguration('acre_id');
        $suffix = $this->getLogicalId();

        $topic = $baseTopic . '/' . $type . '/' . $id . '/' . $suffix;

        $cmd = 'mosquitto_pub';
        $cmd .= ' -h ' . escapeshellarg($mqttHost);
        $cmd .= ' -p ' . escapeshellarg($mqttPort);
        $cmd .= ' -t ' . escapeshellarg($topic);
        $cmd .= ' -m ' . escapeshellarg('1');

        if ($mqttUser != '') {
            $cmd .= ' -u ' . escapeshellarg($mqttUser);
            $cmd .= ' -P ' . escapeshellarg($mqttPass);
        }

        log::add('acreexp', 'debug', 'Commande MQTT : ' . $cmd);
        exec($cmd . ' 2>&1', $output, $returnCode);

        if ($returnCode != 0) {
            log::add('acreexp', 'error', 'Erreur mosquitto_pub : ' . implode("\n", $output));
        }
    }
}
