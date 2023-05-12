<?php

namespace Mosparo\Controller\Account;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Form\PasswordFormType;
use Mosparo\Helper\InterfaceHelper;
use Mosparo\Helper\LocaleHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/account")
 */
class AccountController extends AbstractController
{
    protected EntityManagerInterface $entityManager;

    protected UserPasswordHasherInterface $userPasswordHasher;

    protected TranslatorInterface $translator;

    protected LocaleHelper $localeHelper;

    protected InterfaceHelper $interfaceHelper;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher, TranslatorInterface $translator, LocaleHelper $localeHelper, InterfaceHelper $interfaceHelper)
    {
        $this->entityManager = $entityManager;
        $this->userPasswordHasher = $userPasswordHasher;
        $this->translator = $translator;
        $this->localeHelper = $localeHelper;
        $this->interfaceHelper = $interfaceHelper;
    }

    /**
     * @Route("/", name="account_overview")
     */
    public function overview(): Response
    {
        return $this->render('account/overview.html.twig');
    }

    /**
     * @Route("/settings", name="account_settings")
     */
    public function settings(Request $request): Response
    {
        /** @var \Mosparo\Entity\User $user */
        $user = $this->getUser();
        $config = [
            'locale' => $user->getConfigValue('locale'),
            'dateFormat' => $user->getConfigValue('dateFormat'),
            'timeFormat' => $user->getConfigValue('timeFormat'),
            'timezone' => $user->getConfigValue('timezone'),
            'colorMode' => $user->getConfigValue('colorMode'),
        ];
        $form = $this->createFormBuilder($config, ['translation_domain' => 'mosparo'])
            ->add('locale', ChoiceType::class, ['label' => 'account.settings.form.locale', 'choices' => $this->localeHelper->findAvailableLanguages(true), 'preferred_choices' => ['default', 'browser'], 'attr' => ['class' => 'form-select']])
            ->add('dateFormat', ChoiceType::class, ['label' => 'account.settings.form.dateFormat', 'choices' => $this->localeHelper->getDateFormats(true), 'preferred_choices' => ['default'], 'attr' => ['class' => 'form-select']])
            ->add('timeFormat', ChoiceType::class, ['label' => 'account.settings.form.timeFormat', 'choices' => $this->localeHelper->getTimeFormats(true), 'preferred_choices' => ['default'], 'attr' => ['class' => 'form-select']])
            ->add('timezone', ChoiceType::class, ['label' => 'account.settings.form.timezone', 'choices' => $this->localeHelper->getTimezones(true), 'preferred_choices' => ['default'], 'attr' => ['class' => 'form-select']])
            ->add('colorMode', ChoiceType::class, ['label' => 'account.settings.form.colorMode', 'choices' => $this->interfaceHelper->getColorModes(true), 'preferred_choices' => ['default'], 'attr' => ['class' => 'form-select']])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setConfigValue('locale', $form->get('locale')->getData());
            $user->setConfigValue('dateFormat', $form->get('dateFormat')->getData());
            $user->setConfigValue('timeFormat', $form->get('timeFormat')->getData());
            $user->setConfigValue('timezone', $form->get('timezone')->getData());
            $user->setConfigValue('colorMode', $form->get('colorMode')->getData());

            // Store the user settings
            $this->entityManager->flush();

            // Try to display the success message in the correct language
            $newLocale = $form->get('locale')->getData();
            if ($newLocale == 'default') {
                $newLocale = '';
            } else if ($newLocale == 'browser') {
                if (!empty($request->getPreferredLanguage())) {
                    $newLocale = $request->getPreferredLanguage();
                } else {
                    $newLocale = '';
                }
            }

            $session = $request->getSession();
            $session->getFlashBag()->add(
                'success',
                $this->translator->trans(
                    'account.settings.message.successfullySaved',
                    [],
                    'mosparo',
                    $newLocale
                )
            );

            // Update the values in the session
            $this->localeHelper->storeUserSettingsInSession($session, $user);
            $this->interfaceHelper->storeUserSettingsInSession($session, $user);

            return $this->redirectToRoute('account_overview');
        }

        return $this->render('account/settings.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/change-password", name="account_change_password")
     */
    public function changePassword(Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createFormBuilder([], ['translation_domain' => 'mosparo'])
            ->add('oldPassword', PasswordType::class, [
                'label' => 'account.changePassword.form.oldPassword',
                'constraints' => [
                    new UserPassword()
                ],
                'attr' => [
                    'autocomplete' => 'off'
                ]
            ])
            ->add('newPassword', PasswordFormType::class, [
                'mapped' => false,
                'is_new_password' => true,
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \Mosparo\Entity\User $user */
            $user = $this->getUser();

            // RuleSet the new password and save the user
            $passwordField = $form->get('newPassword');
            $user->setPassword($this->userPasswordHasher->hashPassword(
                $user,
                $passwordField->get('plainPassword')->getData()
            ));

            $entityManager->flush();

            $session = $request->getSession();
            $session->getFlashBag()->add(
                'success',
                $this->translator->trans(
                    'account.changePassword.message.successfullyChanged',
                    [],
                    'mosparo'
                )
            );

            return $this->redirectToRoute('account_overview');
        }

        return $this->render('account/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}