
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

$(function() {
    $('#table_cmd tbody').delegate('tr .cmdAttr[data-l1key=configuration][data-l2key=xPLtypeCmd]', 'change', function() {
        changexPLTypeCmd($(this));
    });

    $('#table_cmd tbody').delegate('tr .cmdAttr[data-l1key=configuration][data-l2key=xPLschema]', 'change', function() {
        changexPLTypeCmd($(this));
    });

    $("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
});


function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }

    var selxPlschema = '<select class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="xPLschema" style="width : 150px;">';
    selxPlschema += '<option value="control.basic">Control.basic</option>';
    selxPlschema += '<option value="sensor.basic">Sensor.basic</option>';
    selxPlschema += '<option value="homeeasy.basic">homeeasy.basic</option>';
    selxPlschema += '<option value="remote.basic">Remote.basic</option>';
    selxPlschema += '<option value="x10.basic">x10.basic</option>';
    selxPlschema += '<option value="ac.basic">ac.basic</option>';
    selxPlschema += '<option value="osd.basic">osd.basic</option>';
    selxPlschema += '<option value="x10.security">x10.security</option>';
    selxPlschema += '</select>';

    var typeXmdxPL = '<select class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="xPLtypeCmd" style="width : 150px;margin-top : 5px;">';
    typeXmdxPL += '<option value="XPL-CMND">XPL-CMND</option>';
    //typeXmdxPL += '<option value="XPL-STAT">XPL-STAT</option>';
    typeXmdxPL += '<option value="XPL-TRIG">XPL-TRIG</option>';
    typeXmdxPL += '</select>';

    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td>';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="id" style="display : none;">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" value="' + init(_cmd.name) + '"></td>';
    tr += '<td>';
    tr += '<span class="type" type="' + init(jeedom.cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
    tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
    tr += '</td>';
    tr += '<td>' + selxPlschema + typeXmdxPL + '</td>';
    tr += '<td class="xPLbody">';
    tr += '<textarea style="height : 100px;" class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="xPLbody"></textarea>';
    tr += '</td>';
    tr += '<td>';
    tr += '<span><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" /> {{Historiser}}<br/></span>';
    tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width : 40%;display : inline-block;"> ';
    tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width : 40%;display : inline-block;">';
    tr += '</td>';
    tr += '<td><input class="cmdAttr input-sm form-control" data-l1key="unite" style="width : 100px;"></td>';
    tr += '<td>';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
    }
    tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
    tr += '</tr>';
    $('#table_cmd tbody').append(tr);
    $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
}

function changexPLTypeCmd(_el, _xPLbody) {
    var tr = _el.closest('tr');
    tr.find('.cmdAttr[data-l1key=isHistorized]').show();
    tr.find('.cmdAttr[data-l1key=cache][data-l2key=enable]').parent().show();
    tr.find('.cmdAttr[data-l1key=eventOnly]').parent().show();
    switch (_el.value()) {
        case 'XPL-CMND' :
            tr.find('.test_xpl').show();
            tr.find('.eventOnly').parent().hide();
            break;
        case 'XPL-STAT' :
            tr.find('.test_xpl').hide();
            break;
        case 'XPL-TRIG' :
            tr.find('.eventOnly').prop('checked', true);
            tr.find('.test_xpl').hide();
            break;
    }
    updatexPLbody(tr.find('.cmdAttr[data-l1key=configuration][data-l2key=xPLschema]'), _xPLbody);
}

function updatexPLbody(_el, _xPLbody) {
    if (!isset(_xPLbody)) {
        var xPLschema = _el.value();
        var xPltypeCmd = _el.parent().find('.cmdAttr[data-l1key=configuration][data-l2key=xPLtypeCmd]').value();
        var tr = _el.closest('tr');
        tr.find('.cmdAttr[data-l1key=configuration][data-l2key=xPLbody]').value(getxPLbody(xPLschema, xPltypeCmd));
    }
}

function getxPLbody(_xPLschema, _xPltypeCmd) {
    var body = '';
    $.ajax({// fonction permettant de faire de l'ajax
        type: "POST", // methode de transmission des données au fichier php
        url: "plugins/xpl/core/ajax/xpl.ajax.php", // url du fichier php
        data: {
            action: "getxPLbody",
            xPLschema: _xPLschema,
            xPLtypeCmd: _xPltypeCmd
        },
        dataType: 'json',
        async: false,
        error: function(request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function(data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            body = $.trim(data.result);
        }
    });
    return body;
}