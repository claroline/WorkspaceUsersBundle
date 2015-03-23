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
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *  @DI\Service()
 */
class WorkspaceUsersToolListener
{
    private $container;

    /**
     * @DI\InjectParams({
     *     "container" = @DI\Inject("service_container")
     * })
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @DI\Observe("open_tool_workspace_claroline_workspace_users_tool")
     *
     * @param DisplayToolEvent $event
     */
    public function onDisplayWorkspaceAgenda(DisplayToolEvent $event)
    {
        $event->setContent('Change me for workspace !');
    }

    /**
     * @DI\Observe("open_tool_desktop_claroline_workspace_users_tool")
     *
     * @param DisplayToolEvent $event
     */
    public function onDisplayDesktopAgenda(DisplayToolEvent $event)
    {
        $event->setContent('Change me for desktop !');
    }
}
