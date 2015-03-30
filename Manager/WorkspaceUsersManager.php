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
use Claroline\CoreBundle\Library\Configuration\PlatformConfigurationHandler;
use Claroline\CoreBundle\Manager\Exception\AddRoleException;
use Claroline\CoreBundle\Manager\RoleManager;
use Claroline\CoreBundle\Manager\UserManager;
use Claroline\CoreBundle\Pager\PagerFactory;
use Claroline\CoreBundle\Persistence\ObjectManager;
use Claroline\WorkspaceUsersBundle\Entity\WorkspaceUser;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * @DI\Service("claroline.manager.workspace_users_manager")
 */
class WorkspaceUsersManager
{
    private $om;
    private $pagerFactory;
    private $platformConfigHandler;
    private $roleManager;
    private $securityContext;
    private $userManager;
    private $workspaceModelRepo;
    private $workspaceUserRepo;

    /**
     * @DI\InjectParams({
     *     "om"                    = @DI\Inject("claroline.persistence.object_manager"),
     *     "pagerFactory"          = @DI\Inject("claroline.pager.pager_factory"),
     *     "platformConfigHandler" = @DI\Inject("claroline.config.platform_config_handler"),
     *     "roleManager"           = @DI\Inject("claroline.manager.role_manager"),
     *     "userManager"           = @DI\Inject("claroline.manager.user_manager"),
     *     "securityContext"       = @DI\Inject("security.context")
     * })
     */
    public function __construct(
        ObjectManager $om,
        PagerFactory $pagerFactory,
        PlatformConfigurationHandler $platformConfigHandler,
        RoleManager $roleManager,
        UserManager $userManager,
        SecurityContextInterface $securityContext
    )
    {
        $this->om = $om;
        $this->pagerFactory = $pagerFactory;
        $this->platformConfigHandler  = $platformConfigHandler;
        $this->roleManager = $roleManager;
        $this->securityContext = $securityContext;
        $this->userManager = $userManager;
        $this->workspaceModelRepo =
            $om->getRepository('ClarolineCoreBundle:Model\WorkspaceModel');
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
            $workspaceUser->setRegistrationDate(new \DateTime());
            $this->om->persist($workspaceUser);
            $this->om->flush();
        }
    }

    public function deleteWorkspaceUsers(Workspace $workspace, array $users)
    {
        $this->om->startFlushSuite();

        foreach ($users as $user) {
            $wsRoles = $this->roleManager->getWorkspaceRolesForUser($user, $workspace);

            foreach ($wsRoles as $role) {
                $this->roleManager->dissociateWorkspaceRole($user, $workspace, $role);
            }

            $workspaceUser = $this->getOneWorkspaceUserByWorkspaceAndUser(
                $workspace,
                $user
            );
            $this->om->remove($workspaceUser);
        }
        $this->om->endFlushSuite();
    }

    public function importWorkspaceUsers(
        Workspace $workspace,
        array $users,
        $sendMail = true,
        $wsRoles = array()
    )
    {
        $createdUsers = array();
        $addedUsers = array();
        $roleUser = $this->roleManager->getRoleByName('ROLE_USER');
        $max = $roleUser->getMaxUsers();
        $total = $this->userManager->countUsersByRoleIncludingGroup($roleUser);

        if ($total + count($users) > $max) {

            throw new AddRoleException();
        }
        $lg = $this->platformConfigHandler->getParameter('locale_language');
        $this->om->startFlushSuite();
        $i = 1;

        foreach ($users as $user) {
            $firstName = $user[0];
            $lastName = $user[1];
            $username = $user[2];
            $pwd = $user[3];
            $email = $user[4];

            if (isset($user[5])) {
                $code = trim($user[5]) === '' ? null: $user[5];
            } else {
                $code = null;
            }

            if (isset($user[6])) {
                $phone = trim($user[6]) === '' ? null: $user[6];
            } else {
                $phone = null;
            }

            if (isset($user[7])) {
                $authentication = trim($user[7]) === '' ? null: $user[7];
            } else {
                $authentication = null;
            }

            if (isset($user[8])) {
                $modelName = trim($user[8]) === '' ? null: $user[8];
            } else {
                $modelName = null;
            }

            if ($modelName) {
                $model = $this->workspaceModelRepo->findOneByName($modelName);
            } else {
                $model = null;
            }
            $existingUser = $this->userManager->getUserByUsernameAndMail($username, $email);

            if (is_null($existingUser)) {
                $newUser = new User();
                $newUser->setFirstName($firstName);
                $newUser->setLastName($lastName);
                $newUser->setUsername($username);
                $newUser->setPlainPassword($pwd);
                $newUser->setMail($email);
                $newUser->setAdministrativeCode($code);
                $newUser->setPhone($phone);
                $newUser->setLocale($lg);
                $newUser->setAuthentication($authentication);
                $this->userManager->createUser($newUser, $sendMail, $wsRoles, $model);
                $this->om->persist($newUser);
                $createdUsers[] = $newUser;
            } else {
                $this->roleManager->associateRoles($existingUser, $wsRoles);
                $addedUsers[] = $existingUser;
            }

            if ($i % 5 === 0) {
                $this->om->forceFlush();
            }
            $i++;
        }
        $this->om->forceFlush();

        foreach ($createdUsers as $createdUser) {
            $this->addWorkspaceUser($workspace, $createdUser, true);
        }

        foreach ($addedUsers as $addedUser) {
            $this->addWorkspaceUser($workspace, $addedUser, false);
        }
        $this->om->endFlushSuite();
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
