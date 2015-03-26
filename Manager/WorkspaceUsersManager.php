<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\WorkspaceUsersBundle\Manager;

use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Entity\Workspace\Workspace;
use Claroline\CoreBundle\Pager\PagerFactory;
use Claroline\CoreBundle\Persistence\ObjectManager;
use Claroline\WorkspaceUsersBundle\Entity\WorkspaceUser;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * @DI\Service("claroline.manager.workspace_users_manager")
 */
class WorkspaceUsersManager
{
    private $om;
    private $pagerFactory;
    private $workspaceUserRepo;

    /**
     * @DI\InjectParams({
     *     "om"           = @DI\Inject("claroline.persistence.object_manager"),
     *     "pagerFactory" = @DI\Inject("claroline.pager.pager_factory")
     * })
     */
    public function __construct(
        ObjectManager $om,
        PagerFactory $pagerFactory
    )
    {
        $this->om = $om;
        $this->pagerFactory = $pagerFactory;
        $this->workspaceUserRepo =
            $om->getRepository('ClarolineWorkspaceUsersBundle:WorkspaceUser');
    }

    public function addWorkspaceUser(Workspace $workspace, User $user, $created)
    {
        $workspaceUser = $this->getOneWorkspaceUserByWorkspaceAndUser($workspace, $user);

        if (is_null($workspaceUser)) {
            $workspaceUser = new WorkspaceUser();
            $workspaceUser->setWorkspace($workspace);
            $workspaceUser->setUser($user);
            $workspaceUser->setCreated($created);
            $this->om->persist($workspaceUser);
            $this->om->flush();
        }
    }

    public function getOneWorkspaceUserByWorkspaceAndUser(
        Workspace $workspace,
        User $user
    )
    {
        return $this->workspaceUserRepo->findOneBy(
            array('workspace' => $workspace, 'user' => $user)
        );
    }

    public function getOneWorkspaceUserByWorkspaceAndUserAndCreated(
        Workspace $workspace,
        User $user,
        $created
    )
    {
        return $this->workspaceUserRepo->findOneBy(
            array('workspace' => $workspace, 'user' => $user, 'created' => $created)
        );
    }

    public function getWorkspaceUsersByWorkspace(
        Workspace $workspace,
        $search = '',
        $withMail = false,
        $page = 1,
        $max = 50,
        $orderedBy = 'id',
        $order = 'ASC'
    )
    {
        if (empty($search)) {
            $workspaceUsers =  $this->workspaceUserRepo->findWorkspaceUsersByWorkspace(
                $workspace,
                $orderedBy,
                $order
            );
        } else {
            $workspaceUsers = $this->workspaceUserRepo->findSearchedWorkspaceUsersByWorkspace(
                $workspace,
                $search,
                $withMail,
                $orderedBy,
                $order
            );
        }

        return $this->pagerFactory->createPagerFromArray($workspaceUsers, $page, $max);
    }

    public function getUsersByWorkspace(
        Workspace $workspace,
        array $roles,
        $search = '',
        $withMail = false,
        $page = 1,
        $max = 50,
        $orderedBy = 'id',
        $order = 'ASC'
    )
    {
        if (empty($search)) {
            $users =  $this->workspaceUserRepo->findUsersByWorkspace(
                $workspace,
                $roles,
                $orderedBy,
                $order
            );
        } else {
            $users = $this->workspaceUserRepo->findSearchedUsersByWorkspace(
                $workspace,
                $roles,
                $search,
                $withMail,
                $orderedBy,
                $order
            );
        }

        return $this->pagerFactory->createPagerFromArray($users, $page, $max);
    }
}
