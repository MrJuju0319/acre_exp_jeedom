<?php
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
try {
    acreexp::deamon_stop();
} catch (Exception $e) {
    log::add('acreexp', 'warning', 'Erreur arrêt démon pendant suppression : ' . $e->getMessage());
}
