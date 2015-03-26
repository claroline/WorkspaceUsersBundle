<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\WorkspaceUsersBundle\Repository;

use Claroline\CoreBundle\Entity\Workspace\Workspace;
use Doctrine\ORM\EntityRepository;

class WorkspaceUserRepository extends EntityRepository
{
    public function findWorkspaceUsersByWorkspace(
        Workspace $workspace,
        $orderedBy = 'id',
        $order = 'ASC'
    )
    {
        $dql = "
            SELECT wu
            FROM Claroline\WorkspaceUsersBundle\Entity\WorkspaceUser wu
            JOIN wu.user u
            WHERE wu.workspace = :workspace
            ORDER BY u.{$orderedBy} {$order}
        ";
        $query = $this->_em->createQuery($dql);
        $query->setParameter('workspace', $workspace);

        return $query->getResult();
    }

    public function findSearchedWorkspaceUsersByWorkspace(
        Workspace $workspace,
        $search = '',
        $withMail = false,
        $orderedBy = 'id',
        $order = 'ASC'
    )
    {
        $dql = "
            SELECT wu
            FROM Claroline\WorkspaceUsersBundle\Entity\WorkspaceUser wu
            JOIN wu.user u
            WHERE wu.workspace = :workspace
            AND (
                UPPER(u.username) LIKE :search
                OR UPPER(u.firstName) LIKE :search
                OR UPPER(u.lastName) LIKE :search";

        if ($withMail) {
            $dql .= "
                OR UPPER(u.mail) LIKE :search";
        }
        $dql .= "
            )
            ORDER BY u.{$orderedBy} {$order}
        ";
        $query = $this->_em->createQuery($dql);
        $query->setParameter('workspace', $workspace);
        $upperSearch = strtoupper($search);
        $query->setParameter('search', "%{$upperSearch}%");

        return $query->getResult();
    }

    public function findUsersByWorkspace(
        Workspace $workspace,
        array $roles,
        $orderedBy = 'id',
        $order = 'ASC'
    )
    {
        $dql = "
            SELECT DISTINCT u, r1, g, r2, ws
            FROM Claroline\CoreBundle\Entity\User u
            LEFT JOIN u.roles r1
            LEFT JOIN u.personalWorkspace ws
            LEFT JOIN u.groups g
            LEFT JOIN g.roles r2
            WHERE u.isEnabled = true
            AND (
                r1 in (:roles)
                OR r2 in (:roles)
                OR EXISTS (
                    SELECT wu
                    FROM Claroline\WorkspaceUsersBundle\Entity\WorkspaceUser wu
                    WHERE wu.user = u
                    AND wu.workspace = :workspace
                    
                )
            )
            ORDER BY u.{$orderedBy} {$order}
        ";

        $query = $this->_em->createQuery($dql);
        $query->setParameter('roles', $roles);
        $query->setParameter('workspace', $workspace);

        return $query->getResult();
    }

    public function findSearchedUsersByWorkspace(
        Workspace $workspace,
        array $roles,
        $search = '',
        $withMail = false,
        $orderedBy = 'id',
        $order = 'ASC'
    )
    {
        $dql = "
            SELECT DISTINCT u, r1, g, r2, ws
            FROM Claroline\CoreBundle\Entity\User u
            LEFT JOIN u.roles r1
            LEFT JOIN u.personalWorkspace ws
            LEFT JOIN u.groups g
            LEFT JOIN g.roles r2
            WHERE u.isEnabled = true
            AND (
                r1 in (:roles)
                OR r2 in (:roles)
                OR EXISTS (
                    SELECT wu
                    FROM Claroline\WorkspaceUsersBundle\Entity\WorkspaceUser wu
                    WHERE wu.user = u
                    AND wu.workspace = :workspace

                )
            )
            AND (
                UPPER(u.username) LIKE :search
                OR UPPER(u.firstName) LIKE :search
                OR UPPER(u.lastName) LIKE :search";

        if ($withMail) {
            $dql .= "
                OR UPPER(u.mail) LIKE :search";
        }
        $dql .= "
            )
            ORDER BY u.{$orderedBy} {$order}
        ";

        $query = $this->_em->createQuery($dql);
        $query->setParameter('roles', $roles);
        $query->setParameter('workspace', $workspace);
        $upperSearch = strtoupper($search);
        $query->setParameter('search', "%{$upperSearch}%");

        return $query->getResult();
    }
}
