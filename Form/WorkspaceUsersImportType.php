<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\WorkspaceUsersBundle\Form;

use Claroline\CoreBundle\Entity\Workspace\Workspace;
use Claroline\WorkspaceUsersBundle\Validator\Constraints\CsvWorkspaceUser;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class WorkspaceUsersImportType extends AbstractType
{
    private $workspace;

    public function __construct(Workspace $workspace)
    {
        $this->workspace = $workspace;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $workspace = $this->workspace;

        $builder->add(
            'file',
            'file',
            array(
                'required' => true,
                'mapped' => false,
                'constraints' => array(
                    new NotBlank(),
                    new File(),
                    new CsvWorkspaceUser()
                )
            )
        );
        $builder->add('sendMail', 'checkbox', array('label' => 'send_mail', 'required' => false));
        $builder->add(
            'workspaceRoles',
            'entity',
            array(
                'label' => 'roles',
                'mapped' => false,
                'class' => 'ClarolineCoreBundle:Role',
                'query_builder' => function (EntityRepository $er) use ($workspace) {

                    return $er->createQueryBuilder('r')
                        ->where('r.workspace = :workspace')
                        ->setParameter('workspace', $workspace)
                        ->orderBy('r.translationKey', 'ASC');
                },
                'property' => 'translationKey',
                'expanded' => true,
                'multiple' => true,
                'required' => false
            )
        );
    }

    public function getName()
    {
        return 'import_user_file';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array('translation_domain' => 'platform'));
    }
}
