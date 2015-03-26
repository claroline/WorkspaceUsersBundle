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

    $('#search-user-btn').on('click', function () {
        var search = $('#search-user-input').val();
        var orderedBy = $(this).data('ordered-by');
        var order = $(this).data('order');
        var max = $(this).data('max');
        var route = Routing.generate(
            'claro_workspace_users_registered_user_list',
            {
                'workspace': workspaceId,
                'orderedBy': orderedBy,
                'order': order,
                'max': max,
                'search': search
            }
        );

        window.location.href = route;
    });

    $('#search-user-input').keypress(function(e) {
        if (e.keyCode === 13) {
            var search = $(this).val();
            var orderedBy = $(this).data('ordered-by');
            var order = $(this).data('order');
            var max = $(this).data('max');
            var route = Routing.generate(
                'claro_workspace_users_registered_user_list',
                {
                    'workspace': workspaceId,
                    'orderedBy': orderedBy,
                    'order': order,
                    'max': max,
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
    
    var reloadPage = function () {
        window.location.reload();
    };
})();