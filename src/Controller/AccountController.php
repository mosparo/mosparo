<?php

namespace Mosparo\Controller;

use Mosparo\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

/**
 * @Route("/account")
 */
class AccountController extends AbstractController
{
    protected $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * @Route("/", name="account_overview")
     */
    public function overview(Request $request): Response
    {
        return $this->render('account/overview.html.twig');
    }

    /**
     * @Route("/change-password", name="account_change_password")
     */
    public function changePassword(Request $request): Response
    {
        $passwordData = new \StdClass();
        $passwordData->oldPassword = '';
        $passwordData->newPassword = '';
        $passwordData->newPasswordConfirmed = '';

        $form = $this->createFormBuilder($passwordData)
            ->add('oldPassword', PasswordType::class, ['constraints' => [new UserPassword()]])
            ->add('newPassword', PasswordType::class)
            ->add('newPasswordConfirmed', PasswordType::class)
            ->getForm();

        $form->handleRequest($request);
        // @todo: switch the new password comparison to form constraint
        if ($form->isSubmitted() && $form->isValid() && $passwordData->newPassword === $passwordData->newPasswordConfirmed) {
            $entityManager = $this->getDoctrine()->getManager();
            $user = $this->getUser();

            // Set the new password and save the user
            $user->setPassword($this->passwordEncoder->encodePassword(
                $user,
                $passwordData->newPassword
            ));

            $entityManager->flush();

            // Set the flash message
            $session = $request->getSession();
            $session->getFlashBag()->add('success', 'Your password was successfully changed.');

            return $this->redirectToRoute('account_overview');
        }

        return $this->render('account/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}