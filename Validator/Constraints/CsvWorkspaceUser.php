<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\WorkspaceUsersBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class CsvWorkspaceUser extends Constraint
{
    public $message = 'Each row requires at least 5 parameters.';

    public function validatedBy()
    {
        return 'csv_workspace_user_validator';
    }
}
