<?php

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

try {
    if (!isset($_POST['apikey']) || $_POST['apikey'] != jeedom::getApiKey('acreexp')) {
        throw new Exception('Clef API invalide');
    }

    if (!isset($_POST['topic'])) {
        throw new Exception('Topic manquant');
    }

    $topic = trim($_POST['topic']);
    $payload = isset($_POST['payload']) ? trim($_POST['payload']) : '';

    log::add('acreexp', 'debug', 'Callback MQTT topic=' . $topic . ' payload=' . $payload);

    acreexp::handleMqttMessage($topic, $payload);

    echo json_encode(array(
        'state' => 'ok'
    ));
} catch (Exception $e) {
    log::add('acreexp', 'error', 'Erreur callback : ' . $e->getMessage());
    echo json_encode(array(
        'state' => 'error',
        'message' => $e->getMessage()
    ));
}