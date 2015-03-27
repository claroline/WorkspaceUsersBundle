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
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class WorkspaceUserCreationType extends AbstractType
{
    private $langs;
    private $authenticationDrivers;
    private $workspace;

    public function __construct(
        Workspace $workspace,
        array $langs,
        $authenticationDrivers = null
    )
    {
        $this->authenticationDrivers = $authenticationDrivers;
        $this->langs = empty($langs) ?
            array('en' => 'en', 'fr' => 'fr') :
            $langs;
        $this->workspace = $workspace;
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $workspace = $this->workspace;

        $builder->add('firstName', 'text', array('label' => 'First name', 'required' => true));
        $builder->add('lastName', 'text', array('label' => 'Last name', 'required' => true));
        $builder->add('username', 'text', array('label' => 'User name', 'required' => true));
        $builder->add(
            'plainPassword',
            'repeated',
            array(
                'type' => 'password',
                'first_options' => array('label' => 'password'),
                'second_options' => array('label' => 'verification')
            )
        );
        $builder->add(
            'administrativeCode',
            'text',
            array(
                'required' => false, 'label' => 'administrative_code'
            )
        );
        $builder->add('mail', 'email', array('required' => true, 'label' => 'email'));
        $builder->add('phone', 'text', array('required' => false, 'label' => 'phone'));
        $builder->add(
            'locale',
            'choice',
            array(
                'choices' => $this->langs,
                'required' => false,
                'label' => 'Language'
            )
        );
        $builder->add(
            'authentication',
            'choice',
            array(
                'choices' => $this->authenticationDrivers,
                'required' => false,
                'label' => 'authentication'
            )
        );
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
        return 'profile_form_creation';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array('translation_domain' => 'platform'));
    }
}
