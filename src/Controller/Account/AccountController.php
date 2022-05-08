<?php

namespace Mosparo\Controller\Account;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Form\PasswordFormType;
use Mosparo\Helper\LocaleHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/account")
 */
class AccountController extends AbstractController
{
    protected $entityManager;

    protected $passwordEncoder;

    protected $translator;

    protected $localeHelper;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder, TranslatorInterface $translator, LocaleHelper $localeHelper)
    {
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->translator = $translator;
        $this->localeHelper = $localeHelper;
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
        $user = $this->getUser();
        $config = [
            'locale' => $user->getConfigValue('locale'),
            'dateFormat' => $user->getConfigValue('dateFormat'),
            'timeFormat' => $user->getConfigValue('timeFormat'),
            'timezone' => $user->getConfigValue('timezone'),
        ];
        $form = $this->createFormBuilder($config, ['translation_domain' => 'mosparo'])
            ->add('locale', ChoiceType::class, ['label' => 'account.settings.form.locale', 'choices' => $this->localeHelper->findAvailableLanguages(true), 'preferred_choices' => ['default', 'browser'], 'attr' => ['class' => 'form-select']])
            ->add('dateFormat', ChoiceType::class, ['label' => 'account.settings.form.dateFormat', 'choices' => $this->localeHelper->getDateFormats(true), 'preferred_choices' => ['default'], 'attr' => ['class' => 'form-select']])
            ->add('timeFormat', ChoiceType::class, ['label' => 'account.settings.form.timeFormat', 'choices' => $this->localeHelper->getTimeFormats(true), 'preferred_choices' => ['default'], 'attr' => ['class' => 'form-select']])
            ->add('timezone', ChoiceType::class, ['label' => 'account.settings.form.timezone', 'choices' => $this->localeHelper->getTimezones(true), 'preferred_choices' => ['default'], 'attr' => ['class' => 'form-select']])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setConfigValue('locale', $form->get('locale')->getData());
            $user->setConfigValue('dateFormat', $form->get('dateFormat')->getData());
            $user->setConfigValue('timeFormat', $form->get('timeFormat')->getData());
            $user->setConfigValue('timezone', $form->get('timezone')->getData());

            // Store the user settings
            $this->entityManager->flush();

            $session = $request->getSession();
            $session->getFlashBag()->add(
                'success',
                $this->translator->trans(
                    'account.settings.message.successfullySaved',
                    [],
                    'mosparo'
                )
            );

            // Update the values in the session
            $this->localeHelper->storeUserSettingsInSession($session, $user);

            return $this->redirectToRoute('account_settings');
        }

        return $this->render('account/settings.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/change-password", name="account_change_password")
     */
    public function changePassword(Request $request): Response
    {
        $form = $this->createFormBuilder([], ['translation_domain' => 'mosparo'])
            ->add('oldPassword', PasswordType::class, ['label' => 'account.changePassword.form.oldPassword', 'constraints' => [new UserPassword()]])
            ->add('newPassword', PasswordFormType::class, [
                'mapped' => false,
                'is_new_password' => true,
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $user = $this->getUser();

            // RuleSet the new password and save the user
            $passwordField = $form->get('newPassword');
            $user->setPassword($this->passwordEncoder->encodePassword(
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