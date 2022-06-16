<?php

namespace Mosparo\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Form\PasswordFormType;
use Mosparo\Form\ResetPasswordRequestFormType;
use Mosparo\Helper\MailHelper;
use Mosparo\Repository\UserRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

/**
 * @Route("/password")
 */
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    protected EntityManagerInterface $entityManager;

    protected ResetPasswordHelperInterface $resetPasswordHelper;

    protected UserPasswordHasherInterface $userPasswordHasher;

    protected TranslatorInterface $translator;

    public function __construct(EntityManagerInterface $entityManager, ResetPasswordHelperInterface $resetPasswordHelper, UserPasswordHasherInterface $userPasswordHasher, TranslatorInterface $translator)
    {
        $this->entityManager = $entityManager;
        $this->resetPasswordHelper = $resetPasswordHelper;
        $this->userPasswordHasher = $userPasswordHasher;
        $this->translator = $translator;
    }

    /**
     * Display & process form to request a password reset.
     *
     * @Route("", name="security_reset")
     */
    public function request(Request $request, UserRepository $userRepository, MailerInterface $mailer, MailHelper $mailHelper): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->processSendingPasswordResetEmail(
                $form->get('email')->getData(),
                $userRepository,
                $mailer,
                $mailHelper
            );
        }

        return $this->render('password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    /**
     * Confirmation page after a user has requested a password reset.
     *
     * @Route("/check-email", name="security_check_email")
     */
    public function checkEmail(): Response
    {
        // Generate a fake token if the user does not exist or someone hit this page directly.
        // This prevents exposing whether a user was found with the given email address
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }

        return $this->render('password/check_email.html.twig', [
            'resetToken' => $resetToken,
        ]);
    }

    /**
     * Validates and process the reset URL that the user clicked in their email.
     *
     * @Route("/reset/{token}", name="security_reset_password")
     */
    public function reset(Request $request, string $token = null): Response
    {
        if ($token) {
            // We store the token in session and remove it from the URL, to avoid the URL being
            // loaded in a browser and potentially leaking the token to 3rd party JavaScript.
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('security_reset_password');
        }

        $token = $this->getTokenFromSession();
        if (null === $token) {
            throw $this->createNotFoundException(
                $this->translator->trans(
                    'password.reset.error.tokenNotFound',
                    [],
                    'mosparo'
                )
            );
        }

        try {
            /** @var \Mosparo\Entity\User $user */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('reset_password_error', $this->translator->trans(
                'password.reset.error.errorOccurred',
                [ '%error%' => $e->getReason() ],
                'mosparo'
            ));

            return $this->redirectToRoute('app_forgot_password_request');
        }

        // The token is valid; allow the user to change their password.
        $form = $this->createForm(PasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // A password reset token should be used only once, remove it.
            $this->resetPasswordHelper->removeResetRequest($token);

            // Encode the plain password, and set it.
            $encodedPassword = $this->userPasswordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );

            $user->setPassword($encodedPassword);
            $this->entityManager->flush();

            // The session is cleaned up after the password has been changed.
            $this->cleanSessionAfterReset();

            $this->addFlash('success', $this->translator->trans(
                'password.reset.message.successfullyReset',
                [ ],
                'mosparo'
            ));

            return $this->redirectToRoute('security_login');
        }

        return $this->render('password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }

    private function processSendingPasswordResetEmail(string $emailFormData, UserRepository $userRepository, MailerInterface $mailer, MailHelper $mailHelper): RedirectResponse
    {
        $user = $userRepository->findOneBy([
            'email' => $emailFormData,
        ]);

        // Do not reveal whether a user account was found or not.
        if (!$user) {
            return $this->redirectToRoute('security_check_email');
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            return $this->redirectToRoute('security_check_email');
        }


        $email = (new TemplatedEmail())
            ->to($user->getEmail())
            ->subject($this->translator->trans('password.email.subject', [], 'mosparo'))
            ->htmlTemplate('non_http/email/password/email.html.twig')
            ->context([
                'resetToken' => $resetToken,
                'cssCode' => $mailHelper->getEmailCssCode(),
            ]);

        $mailer->send($email);

        // Store the token object in session for retrieval in check-email route.
        $this->setTokenObjectInSession($resetToken);

        return $this->redirectToRoute('security_check_email');
    }
}
