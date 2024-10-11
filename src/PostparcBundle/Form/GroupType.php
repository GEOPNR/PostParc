<?php

namespace PostparcBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\Container;

class GroupType extends AbstractType
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityID();
        $currentEntityConfig = $this->container->get('session')->get('currentEntityConfig');
        $object = $builder->getData();
        $user = $options['currentUser'];

        $builder
            //->add('slug')
            ->add('name', null, ['label' => 'Group.field.name'])
            //->add('parent', null, array('label' => 'Group.field.parent'))
            ->add('parent', EntityType::class, [
                'class' => 'PostparcBundle:Group',
                'required' => false,
                'label' => 'Group.field.parent',
                'attr' => ['class' => 'select2'],
                'placeholder' => 'Group.actions.select_one_group',
                'query_builder' => function (EntityRepository $er) use ($entityId, $currentEntityConfig, $user) {
                    if ($entityId) {
                        $dql = $er->createQueryBuilder('g')
                            ->leftJoin('g.entity', 'entity')
                            ->leftJoin('g.createdBy', 'u')
                            ->andWhere('(g.isPrivate!=1 OR (g.isPrivate=1 AND u.id='.$user->getId().'))')
                            ->andWhere('g.deletedAt IS NULL')    
                            ->orderBy('g.name', 'ASC');
                        if ($currentEntityConfig['show_SharedContents']) {
                            $dql->where('entity.id=' . $entityId . ' OR (entity.id!=' . $entityId . ' AND g.isShared=1)');
                        } else {
                            $dql->where('entity.id=' . $entityId);
                        }
                        return $dql;
                    } else {
                        return $er->createQueryBuilder('g')
                            ->leftJoin('g.createdBy', 'u') 
                            ->andWhere('(g.isPrivate!=1 OR (g.isPrivate=1 AND u.id='.$user->getId().'))') 
                            ->andWhere('g.deletedAt IS NULL')    
                            ->orderBy('g.name', 'ASC')
                            ;
                    }
                },
            ])
            ->add('isShared', null, ['label' => 'genericFields.isShared'])
            ->add('isEditableByOtherEntities', null, ['label' => 'genericFields.isEditableByOtherEntities'])
                        
        ;
        if(!($object->getId()) || ( $object && $object->getCreatedBy() && $object->getCreatedBy()->getId() == $user->getId() )) {
            $builder->add('isPrivate', null, ['label' => 'genericFields.isPrivate']);
        }        
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'PostparcBundle\Entity\Group',
            'currentUser' => null
        ]);
    }
}
