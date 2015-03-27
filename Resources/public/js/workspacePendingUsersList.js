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

    $('#search-user-btn').on('click', function () {
        var search = $('#search-user-input').val();
        var route = Routing.generate(
            'claro_workspace_users_pending_list',
            {
                'workspace': workspaceId,
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
                'claro_workspace_users_pending_list',
                {
                    'workspace': workspaceId,
                    'max': currentMax,
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
    
    $('#max-select').on('change', function () {
        var max = $(this).val();
        var route = Routing.generate(
            'claro_workspace_users_pending_list',
            {
                'workspace': workspaceId,
                'search': currentSearch,
                'max': max
            }
        );
        window.location = route;
    });
})();