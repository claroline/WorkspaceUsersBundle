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
        var max = $(this).data('max');
        var route = Routing.generate(
            'claro_workspace_users_pending_list',
            {
                'workspace': workspaceId,
                'max': max,
                'search': search
            }
        );

        window.location.href = route;
    });

    $('#search-user-input').keypress(function(e) {
        if (e.keyCode === 13) {
            var search = $(this).val();
            var max = $(this).data('max');
            var route = Routing.generate(
                'claro_workspace_users_pending_list',
                {
                    'workspace': workspaceId,
                    'max': max,
                    'search': search
                }
            );

            window.location.href = route;
        }
    });
    
    $('.accept-queue-btn').on('click', function () {
        var queueId = $(this).data('queue-id');
        
        $.ajax({
            url: Routing.generate(
                'claro_workspace_users_accept_pending_user',
                {
                    'workspace': workspaceId,
                    'workspaceRegistrationQueue': queueId
                }
            ),
            type: 'POST',
            success: function () {
                $('#row-queue-' + queueId).remove();
            }
        });
    });
    
    $('.decline-queue-btn').on('click', function () {
        var queueId = $(this).data('queue-id');
        
        $.ajax({
            url: Routing.generate(
                'claro_workspace_users_decline_pending_user',
                {
                    'workspace': workspaceId,
                    'workspaceRegistrationQueue': queueId
                }
            ),
            type: 'POST',
            success: function () {
                $('#row-queue-' + queueId).remove();
            }
        });
    });
})();