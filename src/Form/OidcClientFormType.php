<?php

namespace Mosparo\Form;

use Mosparo\Security\Oidc\Configuration\OidcClientConfiguration;
use Mosparo\Security\Oidc\OidcManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Url;

class OidcClientFormType extends AbstractType implements EventSubscriberInterface
{
    protected OidcManager $oidcManager;

    public function __construct(OidcManager $oidcManager)
    {
        $this->oidcManager = $oidcManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = $options['is_new_oidc_client'] ?? false;
        $clientSecretConstraints = [];
        if ($isNew) {
            $clientSecretConstraints[] = new NotBlank([
                'message' => 'password.form.constraint.notBlank',
            ]);
        }

        $builder
            ->add('enabled', CheckboxType::class, [
                'label' => 'administration.authentication.oidcClient.form.enabled',
                'help' => 'administration.authentication.oidcClient.form.enabledHelp',
                'required' => false,
            ])
            ->add('name', TextType::class, [
                'label' => 'administration.authentication.oidcClient.form.name',
            ])
            ->add('alias', TextType::class, [
                'label' => 'administration.authentication.oidcClient.form.alias',
                'help' => 'administration.authentication.oidcClient.form.aliasHelp',
                'constraints' => [
                    new Regex([
                        'pattern' => '/^[a-z0-9\-\_]+$/i',
                        'message' => 'oidcClient.aliasHasInvalidCharacter',
                    ]),
                ],
            ])
            ->add('wellKnownUrl', UrlType::class, [
                'label' => 'administration.authentication.oidcClient.form.wellKnownUrl',
                'help' => 'administration.authentication.oidcClient.form.wellKnownUrlHelp',
                'constraints' => [
                    new Url(
                        protocols: ['https'],
                    ),
                ],
            ])
            ->add('clientId', TextType::class, [
                'label' => 'administration.authentication.oidcClient.form.clientId',
            ])
            ->add('clientSecret', PasswordType::class, [
                'label' => 'administration.authentication.oidcClient.form.clientSecret',
                'help' => (!$isNew) ? 'administration.authentication.oidcClient.form.clientSecretHelp' : null,
                'required' => $isNew,
                'constraints' => $clientSecretConstraints,
                'mapped' => false,
            ])
            ->add('iconUrl', UrlType::class, [
                'label' => 'administration.authentication.oidcClient.form.iconUrl',
                'help' => 'administration.authentication.oidcClient.form.iconUrlHelp',
                'required' => false,
                'constraints' => [
                    new Url(
                        protocols: ['https'],
                    ),
                ],
            ])
            ->add('assumeEmailVerified', CheckboxType::class, [
                'label' => 'administration.authentication.oidcClient.form.assumeEmailVerified',
                'help' => 'administration.authentication.oidcClient.form.assumeEmailVerifiedHelp',
                'required' => false,
            ])
            ->add('enableRetrieveUserInfo', CheckboxType::class, [
                'label' => 'administration.authentication.oidcClient.form.enableRetrieveUserInfo',
                'help' => 'administration.authentication.oidcClient.form.enableRetrieveUserInfoHelp',
                'required' => false,
            ])
            ->add('additionalScopes', CollectionType::class, [
                'label' => 'administration.authentication.oidcClient.form.additionalScopes',
                'help' => 'administration.authentication.oidcClient.form.additionalScopesHelp',
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
        ;

        $builder->addEventSubscriber($this);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OidcClientConfiguration::class,
            'translation_domain' => 'mosparo',
            'is_new_oidc_client' => false,
        ]);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::SUBMIT => 'ensureAliasUniqueness',
        ];
    }

    public function ensureAliasUniqueness(FormEvent $event): void
    {
        $oidcClient = $event->getData();

        foreach ($this->oidcManager->getClients() as $client) {
            if ($client->getAlias() === $oidcClient->getAlias() && $client !== $oidcClient) {
                throw new TransformationFailedException(
                    'The entered alias is already in use. Choose a different one.',
                    0,
                    null,
                    'oidcClient.aliasNotUnique'
                );
            }
        }
    }
}
