<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\WorkspaceUsersBundle\Listener;

use Claroline\CoreBundle\Event\DisplayToolEvent;
use Claroline\CoreBundle\Event\WorkspaceAddUserEvent;
use Claroline\WorkspaceUsersBundle\Manager\WorkspaceUsersManager;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 *  @DI\Service()
 */
class WorkspaceUsersToolListener
{
    private $httpKernel;
    private $request;
    private $workspaceUsersManager;

    /**
     * @DI\InjectParams({
     *     "httpKernel"            = @DI\Inject("http_kernel"),
     *     "requestStack"          = @DI\Inject("request_stack"),
     *     "workspaceUsersManager" = @DI\Inject("claroline.manager.workspace_users_manager")
     * })
     */
    public function __construct(
        HttpKernelInterface $httpKernel,
        RequestStack $requestStack,
        WorkspaceUsersManager $workspaceUsersManager
    )
    {
        $this->httpKernel = $httpKernel;
        $this->request = $requestStack->getCurrentRequest();
        $this->workspaceUsersManager = $workspaceUsersManager;
    }

    /**
     * @DI\Observe("open_tool_workspace_claroline_workspace_users_tool")
     *
     * @param DisplayToolEvent $event
     */
    public function onDisplayWorkspaceUsersTool(DisplayToolEvent $event)
    {
        $params = array();
        $params['_controller'] = 'ClarolineWorkspaceUsersBundle:WorkspaceUsers:workspaceUsersList';
        $params['workspace'] = $event->getWorkspace()->getId();
        $subRequest = $this->request->duplicate(array(), null, $params);
        $response = $this->httpKernel
            ->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        $event->setContent($response->getContent());
        $event->stopPropagation();
    }

    /**
     * @DI\Observe("claroline_workspace_register_user")
     *
     * @param WorkspaceAddUserEvent $event
     */
    public function onWorkspaceUserRegistration(WorkspaceAddUserEvent $event)
    {
        $role = $event->getRole();
        $user = $event->getUser();
        $workspace = $role->getWorkspace();

        if (!is_null($workspace)) {
            $this->workspaceUsersManager->addWorkspaceUser($workspace, $user, false);
        }
    }
}
