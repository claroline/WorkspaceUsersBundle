<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CursusBundle\Manager;

use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Entity\Workspace\Workspace;
use Claroline\CoreBundle\Pager\PagerFactory;
use Claroline\CoreBundle\Persistence\ObjectManager;
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
}
