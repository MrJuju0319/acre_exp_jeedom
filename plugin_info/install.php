<?php
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function acreexp_install() {
    config::save('spc_host', 'http://192.168.1.100', 'acreexp');
    config::save('spc_user', 'Engineer', 'acreexp');
    config::save('spc_pin', '1111', 'acreexp');
    config::save('spc_language', '253', 'acreexp');
    config::save('mqtt_host', '127.0.0.1', 'acreexp');
    config::save('mqtt_port', '1883', 'acreexp');
    config::save('mqtt_user', '', 'acreexp');
    config::save('mqtt_pass', '', 'acreexp');
    config::save('mqtt_base_topic', 'acre_XXX', 'acreexp');
    config::save('watchdog_refresh_interval', '2', 'acreexp');
    config::save('watchdog_controller_refresh_interval', '60', 'acreexp');
}

function acreexp_update() {
    foreach (array(
        'spc_language' => '253',
        'mqtt_host' => '127.0.0.1',
        'mqtt_port' => '1883',
        'mqtt_base_topic' => 'acre_XXX',
        'watchdog_refresh_interval' => '2',
        'watchdog_controller_refresh_interval' => '60'
    ) as $key => $default) {
        if (config::byKey($key, 'acreexp', null) === null) {
            config::save($key, $default, 'acreexp');
        }
    }
}

function acreexp_remove() {
    acreexp::deamon_stop();
}
