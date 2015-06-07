<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\WorkspaceUsersBundle\Controller;

use Claroline\CoreBundle\Entity\Role;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Entity\Workspace\Workspace;
use Claroline\CoreBundle\Entity\Workspace\WorkspaceRegistrationQueue;
use Claroline\CoreBundle\Form\RoleTranslationType;
use Claroline\CoreBundle\Manager\AuthenticationManager;
use Claroline\CoreBundle\Manager\FacetManager;
use Claroline\CoreBundle\Manager\LocaleManager;
use Claroline\CoreBundle\Manager\ResourceManager;
use Claroline\CoreBundle\Manager\RightsManager;
use Claroline\CoreBundle\Manager\RoleManager;
use Claroline\CoreBundle\Manager\UserManager;
use Claroline\CoreBundle\Manager\WorkspaceUserQueueManager;
use Claroline\WorkspaceUsersBundle\Form\WorkspaceRolesListType;
use Claroline\WorkspaceUsersBundle\Form\WorkspaceRoleType;
use Claroline\WorkspaceUsersBundle\Form\WorkspaceUserCreationType;
use Claroline\WorkspaceUsersBundle\Form\WorkspaceUserEditionType;
use Claroline\WorkspaceUsersBundle\Form\WorkspaceUsersImportType;
use Claroline\WorkspaceUsersBundle\Manager\WorkspaceUsersManager;
use JMS\DiExtraBundle\Annotation as DI;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Translation\TranslatorInterface;
use JMS\SecurityExtraBundle\Annotation as SEC;

class WorkspaceUsersController extends Controller
{
    private $authenticationManager;
    private $facetManager;
    private $formFactory;
    private $localeManager;
    private $request;
    private $resourceManager;
    private $rightsManager;
    private $roleManager;
    private $router;
    private $session;
    private $translator;
    private $userManager;
    private $workspaceUsersManager;
    private $workspaceUserQueueManager;

    /**
     * @DI\InjectParams({
     *     "authenticationManager"     = @DI\Inject("claroline.common.authentication_manager"),
     *     "facetManager"              = @DI\Inject("claroline.manager.facet_manager"),
     *     "formFactory"               = @DI\Inject("form.factory"),
     *     "localeManager"             = @DI\Inject("claroline.common.locale_manager"),
     *     "requestStack"              = @DI\Inject("request_stack"),
     *     "resourceManager"           = @DI\Inject("claroline.manager.resource_manager"),
     *     "rightsManager"             = @DI\Inject("claroline.manager.rights_manager"),
     *     "roleManager"               = @DI\Inject("claroline.manager.role_manager"),
     *     "router"                    = @DI\Inject("router"),
     *     "session"                   = @DI\Inject("session"),
     *     "translator"                = @DI\Inject("translator"),
     *     "userManager"               = @DI\Inject("claroline.manager.user_manager"),
     *     "workspaceUsersManager"     = @DI\Inject("claroline.manager.workspace_users_manager"),
     *     "workspaceUserQueueManager" = @DI\Inject("claroline.manager.workspace_user_queue_manager")
     * })
     */
    public function __construct(
        AuthenticationManager $authenticationManager,
        FacetManager $facetManager,
        FormFactory $formFactory,
        LocaleManager $localeManager,
        RequestStack $requestStack,
        ResourceManager $resourceManager,
        RightsManager $rightsManager,
        RoleManager $roleManager,
        RouterInterface $router,
        SessionInterface $session,
        TranslatorInterface $translator,
        UserManager $userManager,
        WorkspaceUsersManager $workspaceUsersManager,
        WorkspaceUserQueueManager $workspaceUserQueueManager
    )
    {
        $this->authenticationManager  = $authenticationManager;
        $this->facetManager = $facetManager;
        $this->formFactory = $formFactory;
        $this->localeManager = $localeManager;
        $this->request = $requestStack->getCurrentRequest();
        $this->resourceManager = $resourceManager;
        $this->rightsManager = $rightsManager;
        $this->roleManager = $roleManager;
        $this->router = $router;
        $this->session = $session;
        $this->translator = $translator;
        $this->userManager = $userManager;
        $this->workspaceUsersManager = $workspaceUsersManager;
        $this->workspaceUserQueueManager = $workspaceUserQueueManager;
    }

    /**
     * @EXT\Route(
     *     "workspace/{workspace}/users/registered/page/{page}/max/{max}/ordered/by/{orderedBy}/order/{order}/search/{search}",
     *     name="claro_workspace_users_registered_user_list",
     *     defaults={"page"=1, "search"="", "max"=50, "orderedBy"="id", "order"="ASC"},
     *     options = {"expose"=true}
     * )
     * @EXT\ParamConverter("authenticatedUser", options={"authenticatedUser" = true})
     * @EXT\Template()
     * @SEC\PreAuthorize("canAccessWorkspace('claroline_workspace_users_tool')")
     */
    public function workspaceUsersListAction(
        User $authenticatedUser,
        Workspace $workspace,
        $search = '',
        $page = 1,
        $max = 50,
        $orderedBy = 'id',
        $order = 'ASC'
    )
    {
        $canEdit = $this->hasWorkspaceUsersToolEditionAccess($workspace);
        $wsRoles = $this->roleManager->getRolesByWorkspace($workspace);
        $preferences = $this->facetManager->getVisiblePublicPreference();
        $registered = array();
        $created = array();

        $pager = $this->workspaceUsersManager->getUsersByWorkspace(
            $workspace,
            $wsRoles,
            $search,
            $preferences['mail'],
            $page,
            $max,
            $orderedBy,
            $order
        );

        $workspaceUsers = $this->workspaceUsersManager->getWorkspaceUsersByWorkspace(
            $workspace,
            $search,
            $preferences['mail'],
            $orderedBy,
            $order
        );

        foreach ($workspaceUsers as $workspaceUser) {
            $userId = $workspaceUser->getUser()->getId();

            if ($workspaceUser->isCreated()) {
                $created[$userId] = true;
            }
            $registered[$userId] = true;
        }

        return array(
            'workspace' => $workspace,
            'pager' => $pager,
            'search' => $search,
            'wsRoles' => $wsRoles,
            'max' => $max,
            'orderedBy' => $orderedBy,
            'order' => $order,
            'currentUser' => $authenticatedUser,
            'showMail' => $preferences['mail'],
            'canEdit' => $canEdit,
            'workspaceUsers' => $workspaceUsers,
            'created' => $created,
            'registered' => $registered
        );
    }

    /**
     * @EXT\Route(
     *     "workspace/{workspace}/roles/list/ordered/by/{orderedBy}/order/{order}/search/{search}",
     *     name="claro_workspace_users_roles_list",
     *     defaults={"search"="", "orderedBy"="id", "order"="ASC"},
     *     options = {"expose"=true}
     * )
     * @EXT\ParamConverter("authenticatedUser", options={"authenticatedUser" = true})
     * @EXT\Template()
     * @SEC\PreAuthorize("canAccessWorkspace({'claroline_workspace_users_tool': 'edit'})")
     */
    public function workspaceRolesListAction(
        Workspace $workspace,
        $search = '',
        $orderedBy = 'id',
        $order = 'ASC'
    )
    {
        $roles = $this->roleManager->getRolesByWorkspace(
            $workspace,
            $search,
            $orderedBy,
            $order
        );

        return array(
            'workspace' => $workspace,
            'roles' => $roles,
            'search' => $search,
            'orderedBy' => $orderedBy,
            'order' => $order
        );
    }

    /**
     * @EXT\Route(
     *     "workspace/{workspace}/pending/users/list/page/{page}/max/{max}/search/{search}",
     *     name="claro_workspace_users_pending_list",
     *     defaults={"page"=1, "search"="", "max"=50},
     *     options = {"expose"=true}
     * )
     * @EXT\ParamConverter("authenticatedUser", options={"authenticatedUser" = true})
     * @EXT\Template()
     * @SEC\PreAuthorize("canAccessWorkspace({'claroline_workspace_users_tool': 'edit'})")
     */
    public function workspacePendingUsersListAction(
        Workspace $workspace,
        $search = '',
        $page = 1,
        $max = 50
    )
    {
        $queues = $this->workspaceUserQueueManager->getAll($workspace, $page, $max, $search);

        return array(
            'workspace' => $workspace,
            'queues' => $queues,
            'max' => $max,
            'search' => $search
        );
    }

    /**
     * @EXT\Route(
     *     "workspace/{workspace}/user/create/form",
     *     name="claro_workspace_users_user_create_form",
     *     options = {"expose"=true}
     * )
     * @EXT\ParamConverter("authenticatedUser", options={"authenticatedUser" = true})
     * @EXT\Template("ClarolineWorkspaceUsersBundle:WorkspaceUsers:workspaceUserCreateModalForm.html.twig")
     * @SEC\PreAuthorize("canAccessWorkspace({'claroline_workspace_users_tool': 'edit'})")
     */
    public function workspaceUserCreateFormAction(Workspace $workspace)
    {
        $userCreationType = new WorkspaceUserCreationType(
            $workspace,
            $this->localeManager->getAvailableLocales(),
            $this->authenticationManager->getDrivers()
        );
        $form = $this->formFactory->create($userCreationType);

        return array(
            'workspace' => $workspace,
            'form' => $form->createView()
        );
    }

    /**
     * @EXT\Route(
     *     "workspace/{workspace}/user/create",
     *     name="claro_workspace_users_user_create",
     *     options = {"expose"=true}
     * )
     * @EXT\ParamConverter("authenticatedUser", options={"authenticatedUser" = true})
     * @EXT\Template("ClarolineWorkspaceUsersBundle:WorkspaceUsers:workspaceUserCreateModalForm.html.twig")
     * @SEC\PreAuthorize("canAccessWorkspace({'claroline_workspace_users_tool': 'edit'})")
     */
    public function workspaceUserCreateAction(Workspace $workspace)
    {
        $sessionFlashBag = $this->session->getFlashBag();
        $userCreationType = new WorkspaceUserCreationType(
            $workspace,
            $this->localeManager->getAvailableLocales(),
            $this->authenticationManager->getDrivers()
        );
        $user = new User();
        $form = $this->formFactory->create($userCreationType, $user);
        $form->handleRequest($this->request);

        $username = $user->getUsername();
        $mail = $user->getMail();
        $existingUser = $this->userManager->getUserByUsernameAndMail($username, $mail);

        if (!is_null($existingUser)) {
            $newRoles = $form->get('workspaceRoles')->getData();
            $roleIds = array();

            foreach ($newRoles as $newRole) {
                $roleIds[] = $newRole->getId();
            }

            return new JsonResponse(
                array('userId' => $existingUser->getId(), 'roleIds' => $roleIds),
                '206'
            );
        } elseif ($form->isValid()) {
            $newRoles = $form->get('workspaceRoles')->getData();
            $this->userManager->createUser($user, true, $newRoles);
            $this->workspaceUsersManager->addWorkspaceUser($workspace, $user, true);
            $sessionFlashBag->add(
                'success',
                $this->translator->trans('user_creation_success', array(), 'platform')
            );

            return new JsonResponse($user->getId(), '200');
        } else {

            return array(
                'workspace' => $workspace,
                'form' => $form->createView()
            );
        }
    }

    /**
     * @EXT\Route(
     *     "workspace/{workspace}/user/{user}/edit/form",
     *     name="claro_workspace_users_user_edit_form",
     *     options = {"expose"=true}
     * )
     * @EXT\ParamConverter("authenticatedUser", options={"authenticatedUser" = true})
     * @EXT\Template("ClarolineWorkspaceUsersBundle:WorkspaceUsers:workspaceUserEditModalForm.html.twig")
     * @SEC\PreAuthorize("canAccessWorkspace({'claroline_workspace_users_tool': 'edit'})")
     */
    public function workspaceUserEditFormAction(Workspace $workspace, User $user)
    {
        $this->checkWorkspaceUserEditionAccess($workspace, $user);
        $userEditionType = new WorkspaceUserEditionType(
            $this->localeManager->getAvailableLocales(),
            $this->authenticationManager->getDrivers()
        );
        $form = $this->formFactory->create($userEditionType, $user);

        return array(
            'workspace' => $workspace,
            'user' => $user,
            'form' => $form->createView()
        );
    }

    /**
     * @EXT\Route(
     *     "workspace/{workspace}/user/{user}/edit",
     *     name="claro_workspace_users_user_edit",
     *     options = {"expose"=true}
     * )
     * @EXT\ParamConverter("authenticatedUser", options={"authenticatedUser" = true})
     * @EXT\Template("ClarolineWorkspaceUsersBundle:WorkspaceUsers:workspaceUserEditModalForm.html.twig")
     * @SEC\PreAuthorize("canAccessWorkspace({'claroline_workspace_users_tool': 'edit'})")
     */
    public function workspaceUserEditAction(Workspace $workspace, User $user)
    {
        $this->checkWorkspaceUserEditionAccess($workspace, $user);
        $sessionFlashBag = $this->session->getFlashBag();
        $userEditionType = new WorkspaceUserEditionType(
            $this->localeManager->getAvailableLocales(),
            $this->authenticationManager->getDrivers()
        );
        $form = $this->formFactory->create($userEditionType, $user);
        $form->handleRequest($this->request);

        if ($form->isValid()) {
            $this->userManager->persistUser($user);
            $sessionFlashBag->add(
                'success',
                $this->translator->trans('user_edition_success', array(), 'platform')
            );

            return new JsonResponse($user->getId(), '200');
        } else {

            return array(
                'workspace' => $workspace,
                'user' => $user,
                'form' => $form->createView()
            );
        }
    }

    /**
     * @EXT\Route(
     *     "workspace/{workspace}/users/delete",
     *     name="claro_workspace_users_delete",
     *     options = {"expose"=true}
     * )
     * @EXT\ParamConverter("authenticatedUser", options={"authenticatedUser" = true})
     * @EXT\ParamConverter(
     *     "users",
     *      class="ClarolineCoreBundle:User",
     *      options={"multipleIds" = true, "name" = "userIds"}
     * )
     * @SEC\PreAuthorize("canAccessWorkspace({'claroline_workspace_users_tool': 'edit'})")
     */
    public function workspaceUsersDeleteAction(Workspace $workspace, array $users)
    {
        $this->workspaceUsersManager->deleteWorkspaceUsers($workspace, $users);

        return new JsonResponse('success', '200');
    }

    /**
     * @EXT\Route(
     *     "workspace/{workspace}/add/user/{user}/with/roles",
     *     name="claro_workspace_users_add_existing_user",
     *     options = {"expose"=true}
     * )
     * @EXT\ParamConverter("authenticatedUser", options={"authenticatedUser" = true})
     * @EXT\ParamConverter(
     *     "roles",
     *      class="ClarolineCoreBundle:Role",
     *      options={"multipleIds" = true, "name" = "roleIds"}
     * )
     * @SEC\PreAuthorize("canAccessWorkspace({'claroline_workspace_users_tool': 'edit'})")
     */
    public function workspaceAddExistingUserAction(
        Workspace $workspace,
        User $user,
        array $roles
    )
    {
        $this->workspaceUsersManager->addWorkspaceUser($workspace, $user, false);
        $sessionFlashBag = $this->session->getFlashBag();
        $this->roleManager->associateRolesToSubjects(array($user), $roles);
        $sessionFlashBag->add(
            'success',
            $this->translator->trans('user_creation_success', array(), 'platform')
        );

        return new JsonResponse('success', 200);
    }

    /**
     * @EXT\Route(
     *     "workspace/{workspace}/users/nb/{nbUsers}/roles/selection/list/form",
     *     name="claro_workspace_users_roles_selection_list_form",
     *     options={"expose"=true}
     * )
     * @EXT\ParamConverter("authenticatedUser", options={"authenticatedUser" = true})
     * @EXT\Template("ClarolineWorkspaceUsersBundle:WorkspaceUsers:workspaceRolesSelectionListModalForm.html.twig")
     * @SEC\PreAuthorize("canAccessWorkspace({'claroline_workspace_users_tool': 'edit'})")
     */
    public function workspaceRolesListFormAction(Workspace $workspace, $nbUsers)
    {
        $form = $this->formFactory->create(new WorkspaceRolesListType($workspace));

        return array(
            'workspace' => $workspace,
            'nbUsers' => $nbUsers,
            'form' => $form->createView()
        );
    }

    /**
     * @EXT\Route(
     *     "workspace/{workspace}/users/nb/{nbUsers}/selected/roles/list",
     *     name="claro_workspace_users_selected_workspace_roles_list",
     *     options={"expose"=true}
     * )
     * @EXT\ParamConverter("authenticatedUser", options={"authenticatedUser" = true})
     * @EXT\Template("ClarolineWorkspaceUsersBundle:WorkspaceUsers:workspaceRolesSelectionListModalForm.html.twig")
     * @SEC\PreAuthorize("canAccessWorkspace({'claroline_workspace_users_tool': 'edit'})")
     */
    public function workspaceRolesSelectedListAction(Workspace $workspace, $nbUsers)
    {
        $form = $this->formFactory->create(new WorkspaceRolesListType($workspace));
        $form->handleRequest($this->request);

        if ($form->isValid()) {
            $roles = $form->get('workspaceRoles')->getData();
            $roleIds = array();

            foreach ($roles as $role) {
                $roleIds[] = $role->getId();
            }

            return new JsonResponse(
                $roleIds,
                200
            );
        } else {

            return array(
                'workspace' => $workspace,
                'nbUsers' => $nbUsers,
                'form' => $form->createView()
            );
        }
    }

    /**
     * @EXT\Route(
     *     "workspace/{workspace}/users/add/roles",
     *     name="claro_workspace_users_add_roles",
     *     options = {"expose"=true}
     * )
     * @EXT\Method("POST")
     * @EXT\ParamConverter("authenticatedUser", options={"authenticatedUser" = true})
     * @EXT\ParamConverter(
     *     "users",
     *      class="ClarolineCoreBundle:User",
     *      options={"multipleIds" = true, "name" = "userIds"}
     * )
     * @EXT\ParamConverter(
     *     "roles",
     *      class="ClarolineCoreBundle:Role",
     *      options={"multipleIds" = true, "name" = "roleIds"}
     * )
     * @SEC\PreAuthorize("canAccessWorkspace({'claroline_workspace_users_tool': 'edit'})")
     */
    public function workspaceUsersAddRoleAction(
        Workspace $workspace,
        array $users,
        array $roles
    )
    {
        $sessionFlashBag = $this->session->getFlashBag();
        $this->roleManager->associateRolesToSubjects($users, $roles);
        $sessionFlashBag->add(
            'success',
            $this->translator->trans('roles_association_success', array(), 'platform')
        );

        return new JsonResponse('success', 200);
    }

    /**
     * @EXT\Route(
     *     "workspace/{workspace}/role/create/form",
     *     name="claro_workspace_users_role_create_form",
     *     options={"expose"=true}
     * )
     * @EXT\ParamConverter("authenticatedUser", options={"authenticatedUser" = true})
     * @EXT\Template("ClarolineWorkspaceUsersBundle:WorkspaceUsers:workspaceRoleCreateModalForm.html.twig")
     * @SEC\PreAuthorize("canAccessWorkspace({'claroline_workspace_users_tool': 'edit'})")
     */
    public function workspaceRoleCreateFormAction(Workspace $workspace)
    {
        $form = $this->formFactory->create(new WorkspaceRoleType());

        return array('workspace' => $workspace, 'form' => $form->createView());
    }

    /**
     * @EXT\Route(
     *     "workspace/{workspace}/role/create",
     *     name="claro_workspace_users_role_create",
     *     options={"expose"=true}
     * )
     * @EXT\ParamConverter("authenticatedUser", options={"authenticatedUser" = true})
     * @EXT\Template("ClarolineWorkspaceUsersBundle:WorkspaceUsers:workspaceRoleCreateModalForm.html.twig")
     * @SEC\PreAuthorize("canAccessWorkspace({'claroline_workspace_users_tool': 'edit'})")
     */
    public function workspaceRoleCreateAction(Workspace $workspace, User $authenticatedUser)
    {
        $role = new Role();
        $form = $this->formFactory->create(new WorkspaceRoleType(), $role);
        $form->handleRequest($this->request);

        if ($form->isValid()) {
            $translationKey = $role->getTranslationKey();
            $requireDir = $form->get('requireDir')->getData();
            $role = $this->roleManager->createWorkspaceRole(
                'ROLE_WS_' . strtoupper($translationKey) . '_' . $workspace->getGuid(),
                $translationKey,
                $workspace
            );

            //add the role to every resource of that workspace
            $nodes = $this->resourceManager->getByWorkspace($workspace);

            foreach ($nodes as $node) {
                $this->rightsManager->create(0, $role, $node, false, array());
            }

            if ($requireDir) {
                $resourceTypes = $this->resourceManager->getAllResourceTypes();
                $creations = array();

                foreach ($resourceTypes as $resourceType) {
                    $creations[] = array('name' => $resourceType->getName());
                }

                $this->resourceManager->create(
                    $this->resourceManager->createResource(
                        'Claroline\CoreBundle\Entity\Resource\Directory',
                        $translationKey
                    ),
                    $this->resourceManager->getResourceTypeByName('directory'),
                    $authenticatedUser,
                    $workspace,
                    $this->resourceManager->getWorkspaceRoot($workspace),
                    null,
                    array(
                        'ROLE_WS_' .  strtoupper($translationKey) => array(
                            'open' => true,
                            'edit' => true,
                            'copy' => true,
                            'delete' => true,
                            'export' => true,
                            'create' => $creations,
                            'role' => $role
                        ),
                        'ROLE_WS_MANAGER' => array(
                            'open' => true,
                            'edit' => true,
                            'copy' => true,
                            'delete' => true,
                            'export' => true,
                            'create' => $creations,
                            'role' => $this->roleManager->getManagerRole($workspace)
                        )
                    )
                );
            }

            return new JsonResponse(
                array('id' => $role->getId(), 'name' => $role->getTranslationKey()),
                200
            );
        } else {

            return array('form' => $form->createView(), 'workspace' => $workspace);
        }
    }

    /**
     * @EXT\Route(
     *     "workspace/{workspace}/role/{role}/edit/form",
     *     name="claro_workspace_users_role_edit_form",
     *     options={"expose"=true}
     * )
     * @EXT\ParamConverter("authenticatedUser", options={"authenticatedUser" = true})
     * @EXT\Template("ClarolineWorkspaceUsersBundle:WorkspaceUsers:workspaceRoleEditModalForm.html.twig")
     * @SEC\PreAuthorize("canAccessWorkspace({'claroline_workspace_users_tool': 'edit'})")
     */
    public function workspaceRoleEditFormAction(Role $role, Workspace $workspace)
    {
        $form = $this->formFactory->create(
            new RoleTranslationType($workspace->getGuid()),
            $role
        );

        return array(
            'role' => $role,
            'workspace' => $workspace,
            'form' => $form->createView()
        );
    }

    /**
     * @EXT\Route(
     *     "workspace/{workspace}/role/{role}/edit",
     *     name="claro_workspace_users_role_edit",
     *     options={"expose"=true}
     * )
     * @EXT\ParamConverter("authenticatedUser", options={"authenticatedUser" = true})
     * @EXT\Template("ClarolineWorkspaceUsersBundle:WorkspaceUsers:workspaceRoleEditModalForm.html.twig")
     * @SEC\PreAuthorize("canAccessWorkspace({'claroline_workspace_users_tool': 'edit'})")
     */
    public function workspaceRoleEditAction(Role $role, Workspace $workspace)
    {
        $form = $this->formFactory->create(
            new RoleTranslationType($workspace->getGuid()),
            $role
        );
        $form->handleRequest($this->request);

        if ($form->isValid()) {
            $this->roleManager->edit($role);

            return new JsonResponse(
                array('id' => $role->getId(), 'name' => $role->getTranslationKey()),
                200
            );
        } else {

            return array(
                'role' => $role,
                'workspace' => $workspace,
                'form' => $form->createView()
            );
        }
    }

    /**
     * @EXT\Route(
     *     "workspace/{workspace}/pending/user/accept/queue/{workspaceRegistrationQueue}",
     *     name="claro_workspace_users_accept_pending_user",
     *     options={"expose"=true}
     * )
     * @SEC\PreAuthorize("canAccessWorkspace({'claroline_workspace_users_tool': 'edit'})")
     */
    public function pendingUsersValidationAction(
        WorkspaceRegistrationQueue $workspaceRegistrationQueue,
        Workspace $workspace
    )
    {
        $this->workspaceUserQueueManager->validateRegistration(
            $workspaceRegistrationQueue,
            $workspace
        );
        $user = $workspaceRegistrationQueue->getUser();
        $this->workspaceUsersManager->addWorkspaceUser($workspace, $user, false);

        return new JsonResponse('success', 200);
    }

    /**
     * @EXT\Route(
     *     "workspace/{workspace}/pending/user/pending/decline/queue/{workspaceRegistrationQueue}",
     *     name="claro_workspace_users_decline_pending_user",
     *     options={"expose"=true}
     * )
     * @SEC\PreAuthorize("canAccessWorkspace({'claroline_workspace_users_tool': 'edit'})")
     */
    public function pendingUsersDeclineAction(
        WorkspaceRegistrationQueue $workspaceRegistrationQueue,
        Workspace $workspace
    )
    {
        $this->workspaceUserQueueManager->removeRegistrationQueue($workspaceRegistrationQueue);

        return new JsonResponse('success', 200);
    }

    /**
     * @EXT\Route(
     *     "workspace/{workspace}/users/import/form",
     *     name="claro_workspace_users_import_form",
     *     options = {"expose"=true}
     * )
     * @EXT\ParamConverter("authenticatedUser", options={"authenticatedUser" = true})
     * @EXT\Template()
     * @SEC\PreAuthorize("canAccessWorkspace({'claroline_workspace_users_tool': 'edit'})")
     */
    public function workspaceUsersImportFormAction(Workspace $workspace)
    {
        $form = $this->formFactory->create(new WorkspaceUsersImportType($workspace));

        return array('workspace' => $workspace, 'form' => $form->createView());
    }

    /**
     * @EXT\Route(
     *     "workspace/{workspace}/users/import",
     *     name="claro_workspace_users_import",
     *     options = {"expose"=true}
     * )
     * @EXT\ParamConverter("authenticatedUser", options={"authenticatedUser" = true})
     * @EXT\Template("ClarolineWorkspaceUsersBundle:WorkspaceUsers:workspaceUsersImportForm.html.twig")
     * @SEC\PreAuthorize("canAccessWorkspace({'claroline_workspace_users_tool': 'edit'})")
     */
    public function workspaceUsersImportAction(Workspace $workspace)
    {
        $form = $this->formFactory->create(new WorkspaceUsersImportType($workspace));
        $form->handleRequest($this->request);

        if ($form->isValid()) {
            $users = array();
            $file = $form->get('file')->getData();
            $sendMail = $form->get('sendMail')->getData();
            $lines = str_getcsv(file_get_contents($file), PHP_EOL);

            foreach ($lines as $line) {
                $userLine = str_getcsv($line, ';');

                if (count($userLine) >= 5) {
                    $users[] = $userLine;
                }
            }
            $roleUser = $this->roleManager->getRoleByName('ROLE_USER');
            $max = $roleUser->getMaxUsers();
            $total = $this->userManager->countUsersByRoleIncludingGroup($roleUser);

            if ($total + count($users) > $max) {

                return array(
                    'workspace' => $workspace,
                    'form' => $form->createView(),
                    'error' => 'role_user unavailable'
                );
            }
            $workspaceRoles = $form->get('workspaceRoles')->getData();
            $this->workspaceUsersManager->importWorkspaceUsers(
                $workspace,
                $users,
                $sendMail,
                $workspaceRoles
            );

            return new RedirectResponse(
                $this->router->generate(
                    'claro_workspace_users_registered_user_list',
                    array('workspace' => $workspace->getId())
                )
            );
        } else {

            return array('workspace' => $workspace, 'form' => $form->createView());
        }
    }

    private function checkWorkspaceUserEditionAccess(Workspace $workspace, User $user)
    {
        $workspaceUser = $this->workspaceUsersManager
            ->getOneWorkspaceUserByWorkspaceAndUserAndCreated($workspace, $user, true);

        if (is_null($workspaceUser)) {

            throw new AccessDeniedException();
        }
    }

    private function hasWorkspaceUsersToolEditionAccess(Workspace $workspace)
    {
        return $this->get('security.authorization_checker')->isGranted(
            array('claroline_workspace_users_tool', 'edit'),
            $workspace
        );
    }
}
