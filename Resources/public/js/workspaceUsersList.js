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
    var currentSearch = $('#workspace-users-datas-box').data('search');
    var currentMax = $('#workspace-users-datas-box').data('max');
    var currentOrderedBy = $('#workspace-users-datas-box').data('ordered-by');
    var currentOrder = $('#workspace-users-datas-box').data('order');

    function checkSelection()
    {
        if ($('.registered-user-chk:checked').length > 0) {
            $('.workspace-user-management-btn').removeClass('disabled');
        } else {
            $('.workspace-user-management-btn').addClass('disabled');
        }
    }

    $('#search-user-btn').on('click', function () {
        var search = $('#search-user-input').val();
        var route = Routing.generate(
            'claro_workspace_users_registered_user_list',
            {
                'workspace': workspaceId,
                'orderedBy': currentOrderedBy,
                'order': currentOrder,
                'max': currentMax,
                'search': search
            }
        );

        window.location.href = route;
    });

    $('#search-user-input').keypress(function(e) {
        if (e.keyCode === 13) {
            var search = $(this).val();
            var route = Routing.generate(
                'claro_workspace_users_registered_user_list',
                {
                    'workspace': workspaceId,
                    'orderedBy': currentOrderedBy,
                    'order': currentOrder,
                    'max': currentMax,
                    'search': search
                }
            );

            window.location.href = route;
        }
    });
    
    $('#create-workspace-user-btn').on('click', function () {
        
        window.Claroline.Modal.displayForm(
            Routing.generate(
                'claro_workspace_users_user_create_form',
                {'workspace': workspaceId}
            ),
            reloadPage,
            function() {}
        );
    });
    
    $('#users-table-body').on('click', '.edit-workspace-user-btn', function () {
        var userId = $(this).data('user-id');
        
        window.Claroline.Modal.displayForm(
            Routing.generate(
                'claro_workspace_users_user_edit_form',
                {'workspace': workspaceId, 'user': userId}
            ),
            reloadPage,
            function() {}
        );
    });
    
    $('#users-table-body').on('click', '.remove-role-button', function () {
        var roleElement = $(this).parent('.role-element');
        var userId = $(this).data('user-id');
        var roleId = $(this).data('role-id');
        
        $.ajax({
            url: Routing.generate(
                'claro_workspace_remove_role_from_user',
                {
                    'workspace': workspaceId,
                    'user': userId,
                    'role': roleId
                }
            ),
            type: 'DELETE',
            success: function () {
                roleElement.remove();
            },
            error: function (jqXHR, textStatus, errorThrown) {
                window.Claroline.Modal.hide();
                window.Claroline.Modal.simpleContainer(
                    Translator.trans('error', {}, 'platform'),
                    jqXHR.responseJSON.message
                );
            }
        });
    });
    
    $('#max-select').on('change', function () {
        var max = $(this).val();
        var route = Routing.generate(
            'claro_workspace_users_registered_user_list',
            {
                'workspace': workspaceId,
                'search': currentSearch,
                'max': max,
                'orderedBy': currentOrderedBy,
                'order': currentOrder
            }
        );
        window.location = route;
    });
    
    $('#users-table-body').on('change', '.registered-user-chk', function () {
        checkSelection();
    });
    
    $('#registered-user-chk-all').on('change', function () {
        var checked = $(this).prop('checked');
        
        if (checked) {
            $('.registered-user-chk').prop('checked', true);
        } else {
            $('.registered-user-chk').prop('checked', false);
        }
        checkSelection();
    });
    
    $('#add-workspace-role-btn').on('click', function () {
        var nbUsers = $('.registered-user-chk:checked').length;
        
         window.Claroline.Modal.displayForm(
            Routing.generate(
                'claro_workspace_users_roles_selection_list_form',
                {'workspace': workspaceId, 'nbUsers': nbUsers}
            ),
            associateRoles,
            function() {}
        );
    });
    
    $('#delete-workspace-users-btn').on('click', function () {
        var nbCheckedUsers = $('.registered-user-chk:checked').length;
        var usersIds = [];
        
        if (nbCheckedUsers > 0) {
            var parameters = {}
            var i = 0;
            var deleteMsg = nbCheckedUsers > 1 ?
                Translator.trans(
                    'remove_user_s_confirm_message',
                    {'count': nbCheckedUsers},
                    'platform'
                ) :
                Translator.trans(
                    'remove_user_confirm_message',
                    {'count': nbCheckedUsers},
                    'platform'
                );
            
            $('.registered-user-chk:checked').each(function (index, element) {
                usersIds[i] = element.value;
                i++;
            });
            parameters.userIds = usersIds;
            var route = Routing.generate(
                'claro_workspace_users_delete',
                {'workspace': workspaceId}
            );
            route += '?' + $.param(parameters);

            window.Claroline.Modal.confirmRequest(
                route,
                removeUsersRow,
                usersIds,
                deleteMsg,
                Translator.trans('users_deletion', {}, 'platform')
            );
        }
    });
    
    var reloadPage = function () {
        window.location.reload();
    };
    
    var removeUsersRow = function (event, userIds) {
        
        for (var i = 0; i < userIds.length; i++) {
            $('#row-workspace-user-' + userIds[i]).remove();
        }
    };
    
    var associateRoles = function (datas) {
        var nbCheckedUsers = $('.registered-user-chk:checked').length;
        var usersIds = [];
        
        if (nbCheckedUsers > 0 && datas.length > 0) {
            var parameters = {}
            var i = 0;
            
            $('.registered-user-chk:checked').each(function (index, element) {
                usersIds[i] = element.value;
                i++;
            });
            parameters.roleIds = datas;
            parameters.userIds = usersIds;
            var route = Routing.generate(
                'claro_workspace_users_add_roles',
                {'workspace': workspaceId}
            );
            route += '?' + $.param(parameters);

            $.ajax({
                url: route,
                type: 'POST',
                success: function () {
                    window.location.reload();
                }
            });
        }
    };
    
    checkSelection();
})();