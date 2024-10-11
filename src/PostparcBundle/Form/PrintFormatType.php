<?php

namespace PostparcBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class PrintFormatType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder

            ->add('name', null, ['label' => 'PrintFormat.field.name'])
            ->add('description', null, ['label' => 'PrintFormat.field.description'])
            ->add('format', null, ['label' => 'PrintFormat.field.format', 'attr' => ['data-help' => 'ex: A4']])
            ->add(
                'orientation',
                ChoiceType::class,
                [
                        'label' => 'PrintFormat.field.format',
                        'expanded' => false,
                        'multiple' => false,
                        'choices' => [
                            'PrintFormat.format.portrait' => 'P',
                            'PrintFormat.format.landscape' => 'L',
                        ],
                        ]
            )

            ->add('marginTop', null, ['label' => 'PrintFormat.field.marginTop', 'attr' => ['data-help' => 'PrintFormat.help.millimeters']])
            ->add('marginBottom', null, ['label' => 'PrintFormat.field.marginBottom', 'attr' => ['data-help' => 'PrintFormat.help.millimeters']])
            ->add('marginLeft', null, ['label' => 'PrintFormat.field.marginLeft', 'attr' => ['data-help' => 'PrintFormat.help.millimeters']])
            ->add('marginRight', null, ['label' => 'PrintFormat.field.marginRight', 'attr' => ['data-help' => 'PrintFormat.help.millimeters']])
            ->add('numberPerRow', null, ['label' => 'PrintFormat.field.numberPerRow'])
            ->add('stickerHeight', null, ['label' => 'PrintFormat.field.stickerHeight', 'attr' => ['data-help' => 'PrintFormat.help.millimeters']])
            ->add('stickerWidth', null, ['label' => 'PrintFormat.field.stickerWidth', 'attr' => ['data-help' => 'PrintFormat.help.millimeters']])
            ->add('paddingHorizontalInterSticker', null, ['label' => 'PrintFormat.field.paddingHorizontalInterSticker', 'attr' => ['data-help' => 'PrintFormat.help.millimeters']])
            ->add('paddingVerticalInterSticker', null, ['label' => 'PrintFormat.field.paddingVerticalInterSticker', 'attr' => ['data-help' => 'PrintFormat.help.millimeters']])
            ->add('marginHorizontalInterSticker', null, ['label' => 'PrintFormat.field.marginHorizontalInterSticker', 'attr' => ['data-help' => 'PrintFormat.help.millimeters']])
            ->add('marginVerticalInterSticker', null, ['label' => 'PrintFormat.field.marginVerticalInterSticker', 'attr' => ['data-help' => 'PrintFormat.help.millimeters']])
            ->add(
                'stickerFonts',
                ChoiceType::class,
                [
                        'label' => 'PrintFormat.field.stickerFonts',
                        'expanded' => false,
                        'multiple' => false,
                        'choices' => [
                            'Arial' => 'arial',
                            'Courier' => 'courier',
                            'Courier Bold' => 'courierB',
                            'Courier Bold Italic' => 'courierBI',
                            'Courier Italic' => 'courierI',
                            'Helvetica' => 'helvetica',
                            'Helvetica Bold' => 'helveticaB',
                            'Helvetica Bold Italic' => 'helveticaBI',
                            'Helvetica Italic' => 'helveticaI',
                            'Symbol' => 'symbol',
                            'Times New Roman' => 'times',
                            'Times New Roman Bold' => 'timesB',
                            'Times New Roman Bold Italic' => 'timesBI',
                            'Times New Roman Italic' => 'timesI',
                            'Zapf Dingbats' => 'zapfdingbats',
                        ],
                        ]
            )
            ->add('stickerFontsize', null, [
                'label' => 'PrintFormat.field.stickerFontsize',
                'required' => true,
                ])
            ->add('isShared', null, ['label' => 'genericFields.isShared'])
            ->add('isEditableByOtherEntities', null, ['label' => 'genericFields.isEditableByOtherEntities'])
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'PostparcBundle\Entity\PrintFormat',
        ]);
    }
}
