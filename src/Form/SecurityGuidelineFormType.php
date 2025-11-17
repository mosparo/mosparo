<?php

namespace Mosparo\Form;

use Mosparo\Entity\SecurityGuideline;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Cidr;
use Symfony\Component\Validator\Constraints\Country;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class SecurityGuidelineFormType extends AbstractType implements EventSubscriberInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $geoIp2Active = $options['geoIp2Active'] ?? false;

        $builder
            ->add('name', TextType::class, [
                'label' => 'settings.security.guideline.form.guideline.name',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'settings.security.guideline.form.guideline.description',
                'required' => false,
            ])
            ->add('priority', NumberType::class, [
                'label' => 'settings.security.guideline.form.guideline.priority',
                'help' => 'settings.security.guideline.form.guideline.priorityHelp',
            ])
            ->add('subnets', CollectionType::class, [
                'label' => 'settings.security.guideline.form.criteria.subnets',
                'help' => 'settings.security.guideline.form.criteria.subnetsHelp',
                'entry_type' => TextType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'required' => false,
                'entry_options' => [
                    'constraints' => [
                        new NotBlank(),
                        new Cidr(),
                    ]
                ]
            ])
            ->add('formPageUrls', CollectionType::class, [
                'label' => 'settings.security.guideline.form.criteria.formPageUrls',
                'help' => 'settings.security.guideline.form.criteria.formPageUrlsHelp',
                'entry_type' => TextType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'required' => false,
                'entry_options' => [
                    'constraints' => [
                        new NotBlank(),
                    ]
                ]
            ])
            ->add('formActionUrls', CollectionType::class, [
                'label' => 'settings.security.guideline.form.criteria.formActionUrls',
                'help' => 'settings.security.guideline.form.criteria.formActionUrlsHelp',
                'entry_type' => TextType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'required' => false,
                'entry_options' => [
                    'constraints' => [
                        new NotBlank(),
                    ]
                ]
            ])
            ->add('formIds', CollectionType::class, [
                'label' => 'settings.security.guideline.form.criteria.formIds',
                'help' => 'settings.security.guideline.form.criteria.formIdsHelp',
                'entry_type' => TextType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'required' => false,
                'entry_options' => [
                    'constraints' => [
                        new NotBlank(),
                    ]
                ]
            ])
            ->add('configValues', SecuritySettingsFormType::class, [
                'label' => 'settings.security.guideline.form.settings.title',
                'addOverrideOptions' => true,
            ]);

        if ($geoIp2Active) {
            $builder
                ->add('countryCodes', CollectionType::class, [
                    'label' => 'settings.security.guideline.form.criteria.countryCodes',
                    'help' => 'settings.security.guideline.form.criteria.countryCodesHelp',
                    'entry_type' => TextType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'delete_empty' => true,
                    'required' => false,
                    'entry_options' => [
                        'constraints' => [
                            new NotBlank(),
                            new Country(),
                        ]
                    ]
                ])
                ->add('asNumbers', CollectionType::class, [
                    'label' => 'settings.security.guideline.form.criteria.asNumbers',
                    'help' => 'settings.security.guideline.form.criteria.asNumbersHelp',
                    'entry_type' => IntegerType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'delete_empty' => true,
                    'required' => false,
                    'entry_options' => [
                        'constraints' => [
                            new NotBlank(),
                            new Positive(),
                        ]
                    ]
                ]);
        }

        $builder->addEventSubscriber($this);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SecurityGuideline::class,
            'translation_domain' => 'mosparo',
            'geoIp2Active' => false,
        ]);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::SUBMIT => 'ensureOneFieldIsSubmitted',
        ];
    }

    public function ensureOneFieldIsSubmitted(FormEvent $event)
    {
        $guideline = $event->getData();

        if (!$guideline->hasCriteria()) {
            throw new TransformationFailedException(
                'No criteria defined. Guideline is not valid.',
                0,
                null,
                'securityGuideline.atLeastOneCriteriaRequired'
            );
        }
    }
}
