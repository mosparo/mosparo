<?php

namespace Mosparo\Controller\Account;

use Mosparo\Form\PasswordFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    protected $passwordEncoder;

    protected $translator;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder, TranslatorInterface $translator)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->translator = $translator;
    }

    /**
     * @Route("/", name="account_overview")
     */
    public function overview(): Response
    {
        return $this->render('account/overview.html.twig');
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