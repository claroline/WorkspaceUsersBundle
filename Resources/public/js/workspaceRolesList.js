/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

(function () {
    'use strict';

    var workspaceId = $('#workspace-users-datas-box').data('workspace-id');
    
    $('#create-workspace-role-btn').on('click', function () {
        
        window.Claroline.Modal.displayForm(
            Routing.generate(
                'claro_workspace_users_role_create_form',
                {'workspace': workspaceId}
            ),
            addRoleRow,
            function() {}
        );
    });
    
    $('#roles-table-body').on('click', '.edit-workspace-role-btn', function () {
        var roleId = $(this).data('role-id');
        
        window.Claroline.Modal.displayForm(
            Routing.generate(
                'claro_workspace_users_role_edit_form',
                {'role': roleId, 'workspace': workspaceId}
            ),
            renameRoleRow,
            function() {}
        );
    });

    $('#roles-table-body').on('click', '.delete-workspace-role-btn', function () {
        var roleId = $(this).data('role-id');

        window.Claroline.Modal.confirmRequest(
            Routing.generate(
                'claro_workspace_role_remove',
                {'role': roleId, 'workspace': workspaceId}
            ),
            removeRoleRow,
            roleId,
            Translator.trans('remove_workspace_role_warning', {}, 'platform'),
            Translator.trans('remove_role', {}, 'platform')
        );
    });

    $('#search-role-btn').on('click', function () {
        var search = $('#search-role-input').val();
        var orderedBy = $(this).data('ordered-by');
        var order = $(this).data('order');
        var route = Routing.generate(
            'claro_workspace_users_roles_list',
            {
                'workspace': workspaceId,
                'orderedBy': orderedBy,
                'order': order,
                'search': search
            }
        );

        window.location.href = route;
    });

    $('#search-role-input').keypress(function(e) {
        if (e.keyCode === 13) {
            var search = $(this).val();
            var orderedBy = $(this).data('ordered-by');
            var order = $(this).data('order');
            var route = Routing.generate(
                'claro_workspace_users_roles_list',
                {
                    'workspace': workspaceId,
                    'orderedBy': orderedBy,
                    'order': order,
                    'search': search
                }
            );

            window.location.href = route;
        }
    });
    
    var addRoleRow = function (datas) {
        var id = datas['id'];
        var name = datas['name'];
        
        var roleElement = 
            '<tr id="row-role-' + id + '">' +
                '<td id="row-role-name-' + id + '">' + Translator.trans(name, {}, 'platform') + '</td>' +
                '<td>' +
                    '<span class="btn btn-default edit-workspace-role-btn"' +
                         ' data-role-id="' + id + '"' +
                    '>' +
                        '<i class="fa fa-pencil"></i> ' +
                        Translator.trans('edit', {}, 'platform') +
                    '</span>' +
                '</td>' +
                '<td>' +
                    '<span class="btn btn-default delete-workspace-role-btn"' +
                          'data-role-id="' + id + '"' +
                    '>' +
                        '<i class="fa fa-trash-o"></i> ' +
                        Translator.trans('delete', {}, 'platform') +
                    '</span>' +
                '</td>' +
            '</tr>';
        
        $('#roles-table-body').append(roleElement);
    }
    
    var renameRoleRow = function (datas) {
        var id = datas['id'];
        var name = datas['name'];
        
        $('#row-role-name-' + id).html( Translator.trans(name, {}, 'platform'));
    }
    
    var removeRoleRow = function (event, roleId) {
        $('#row-role-' + roleId).remove();
    }

    var refreshPage = function () {
        window.location.reload();
    }
})();