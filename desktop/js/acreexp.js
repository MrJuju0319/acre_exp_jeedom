/* global jeedom, jeedomUtils, eqType */

function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        _cmd = { configuration: {} };
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {};
    }

    let tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td class="hidden-xs"><span class="cmdAttr" data-l1key="id"></span></td>';
    tr += '<td><input class="cmdAttr form-control input-sm" data-l1key="name" /></td>';
    tr += '<td><span class="cmdAttr" data-l1key="type"></span> / <span class="cmdAttr" data-l1key="subType"></span></td>';
    tr += '<td>';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="topic" placeholder="Topic MQTT" />';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="payload" placeholder="Payload action" />';
    tr += '</td>';
    tr += '<td><span class="cmdAttr" data-l1key="htmlstate"></span></td>';
    tr += '<td>';
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
    tr += '<a class="btn btn-danger btn-xs cmdAction" data-action="remove"><i class="fas fa-minus-circle"></i></a>';
    tr += '</td>';
    tr += '</tr>';
    document.querySelector('#table_cmd tbody').insertAdjacentHTML('beforeend', tr);
    document.querySelector('#table_cmd tbody tr:last-child').setJeeValues(_cmd, '.cmdAttr');
}

const syncButton = document.getElementById('bt_syncAcreExp');
if (syncButton) {
    syncButton.addEventListener('click', function () {
        jeedomUtils.ajax({
            type: 'POST',
            url: 'plugins/acreexp/core/ajax/acreexp.ajax.php',
            data: { action: 'sync' },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function () {
                jeedomUtils.showAlert({ message: '{{Synchronisation demandée. Recharge la page si de nouveaux équipements ont été créés.}}', level: 'success' });
            }
        });
    });
}
