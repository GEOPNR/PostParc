<?php

namespace PostparcBundle\Form;
//use FOS\CKEditorBundle\Form\Type\CKEditorType;


use Doctrine\ORM\EntityRepository;
use PostparcBundle\Service\UserConfigService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class EventAlertType extends AbstractType
{
    protected $tokenStorage;
    private $userConfigService;

    public function __construct(
            TokenStorage $tokenStorage,
            UserConfigService $userConfigService
            )
    {
        $this->tokenStorage = $tokenStorage;
        $this->userConfigService = $userConfigService;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $editorFontFamily = $this->userConfigService
                ->getSummerNoteFontFamily($user);
        $editorFontSize = $this->userConfigService
                ->getSummerNoteFontSize($user);   

        $event = $options['event'];
        
        $noreplyEmails = array_combine(array_values($options['noreplyEmails']),array_values($options['noreplyEmails']));

        $builder                
              ->add('name', null, ['label' => 'EventAlert.field.name'])
              ->add('gap', null, ['label' => 'EventAlert.field.gap', 'attr' => ['class' => 'eventAlertProgrammingsInfos']])
              ->add('unit', ChoiceType::class, [
                'label' => 'EventAlert.field.unit',
                'expanded' => false,
                'multiple' => false,
                'choices' => [
                  'units.hour' => 'H',
                  'units.day' => 'D',
                  'units.week' => 'W',
                  'units.month' => 'M',
                ],
                'attr' => ['class' => 'eventAlertProgrammingsInfos'],
              ])
              ->add('addRGPDMessageForPerson', null, ['label' => 'sendMailMassifModule.fields.addRGPDMessageForPerson'])
              ->add('direction', ChoiceType::class, [
                'label' => 'EventAlert.field.direction',
                'expanded' => false,
                'multiple' => false,
                'choices' => [
                  'before' => 'sub',
                  'after' => 'add',
                ],
                'attr' => ['class' => 'eventAlertProgrammingsInfos'],
              ])
              ->add('message', null, [
                'label' => 'EventAlert.field.message',
                'required' => true,
                 'attr' => [
                  'class' => 'summernote',
                  'data-default-font-family' => $editorFontFamily,
                  'data-default-font-size' => $editorFontSize                          
                ],
              ])
              ->add('recipients', ChoiceType::class, [
                'label' => 'EventAlert.field.recipients',
                'expanded' => false,
                'multiple' => false,
                'choices' => [
                  'EventAlert.onlyOrganizer' => '1',
                  'EventAlert.onlyParticipants' => '2',
                  'all' => '3',
                ],
              ])
              ->add('isPrintOnInterface', null, ['label' => 'EventAlert.field.isPrintOnInterface'])
              ->add('onlyForConfirmedContact', null, ['label' => 'EventAlert.field.onlyForConfirmedContact'])
              ->add('onlyForUnConfirmedContact', null, ['label' => 'EventAlert.field.onlyForUnConfirmedContact'])
              ->add('limitToRecipiantsList', null, [
                  'label' => 'EventAlert.field.limitToRecipiantsList',
                  'attr' => ['class' => 'hiddenPilotField', 'data-hiddedclass' => 'eventAlertRecipiantsList'],
                  ])
              ->add('eventAlertPersons', EntityType::class, [
                'class' => 'PostparcBundle:Person',
                'required' => false,
                'label' => 'EventAlert.field.eventAlertPersons',
                'multiple' => true,
                'placeholder' => 'actions.selectPerson',
                'attr' => ['class' => 'eventAlertRecipiantsList select2'],  
                'query_builder' => function (EntityRepository $er) use ($event) {                    
                        return $er->createQueryBuilder('p')
                              ->innerJoin('p.eventPersons', 'ep')
                              ->leftJoin('ep.event', 'e')  
                              ->where('e.id=' . $event->getId())
                              ->orderBy('p.slug', 'ASC')
                        ;                    
                },
              ])
              ->add('eventAlertPfos', EntityType::class, [
                'class' => 'PostparcBundle:Pfo',
                'required' => false,
                'label' => 'EventAlert.field.eventAlertPfos',
                'multiple' => true,
                'placeholder' => 'actions.selectPfo',
                'attr' => ['class' => 'eventAlertRecipiantsList select2'],  
                'query_builder' => function (EntityRepository $er) use ($event) {                    
                        return $er->createQueryBuilder('pfo')
                              ->innerJoin('pfo.eventPfos', 'ep')
                              ->leftJoin('ep.event', 'e')
                              ->leftJoin('pfo.person', 'p')  
                              ->where('e.id=' . $event->getId())
                              ->orderBy('p.slug', 'ASC')
                        ;                    
                },
              ])
              ->add('eventAlertRepresentations', EntityType::class, [
                'class' => 'PostparcBundle:Representation',
                'required' => false,
                'label' => 'EventAlert.field.eventAlertRepresentations',
                'multiple' => true,
                'placeholder' => 'actions.selectRepresentation',
                'attr' => ['class' => 'eventAlertRecipiantsList select2 select2entity'],  
                'query_builder' => function (EntityRepository $er) use ($event) {                    
                        return $er->createQueryBuilder('rep')
                              ->innerJoin('rep.eventRepresentations', 'ep')
                              ->leftJoin('ep.event', 'e')  
                              ->where('e.id=' . $event->getId())
                              ->orderBy('rep.slug', 'ASC')
                        ;                    
                },
              ])          
                
              ->add('isManualAlert', null, [
                'label' => 'EventAlert.field.isManualAlert',
                'attr' => ['class' => 'hiddenPilotField', 'data-showedclass' => 'eventAlertProgrammingsInfos'],
                ])
              ->add('mailFooter', EntityType::class, [
                'class' => 'PostparcBundle:MailFooter',
                'required' => false,
                'label' => 'EventAlert.field.mailFooter',
                'multiple' => false,
                'placeholder' => 'actions.selectMailFooter',
                'query_builder' => function (EntityRepository $er) use ($user) {
                    if ($user) {
                        return $er->createQueryBuilder('mf')
                              ->leftJoin('mf.user', 'u')
                              ->where('u.id=' . $user->getId())
                              ->orderBy('mf.name', 'ASC')
                        ;
                    } else {
                        return $er->createQueryBuilder('mf')
                              ->orderBy('mf.name', 'ASC')
                        ;
                    }
                },
              ])
              ->add('attachments', CollectionType::class, [
                'label' => 'Organization.field.attachments',
                'entry_type' => AttachmentType::class,
                'allow_add' => true,
                'label' => false,
                'by_reference' => true,
                'allow_delete' => true,
                'required' => false,
                'attr' => ['class' => 'hidden'],
              ])
              ->add('senderName', null, ['required' => true,'label' => 'sendMailMassifModule.fields.senderName'])
              ->add('senderEmail', ChoiceType::class, [
                  'required' => true,
                  'label' => 'sendMailMassifModule.fields.senderEmail',
                  'expanded' => false,
                  'multiple' => false,
                  'choices' => $noreplyEmails
                  ])           
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        'data_class' => 'PostparcBundle\Entity\EventAlert',
        'noreplyEmails' => [],
        'event' => null,    
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'postparcbundle_eventalert';
    }
}
