<?php

namespace Mosparo\Controller\Account;

use Mosparo\Entity\User;
use Mosparo\Form\PasswordFormType;
use Mosparo\Util\TokenGenerator;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\QrCode\QrCodeGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraint;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Token;

/**
 * @Route("/account/two-factor")
 */
class TwoFactorController extends AbstractController
{
    protected $translator;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder, TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @Route("/status", name="account_two_factor_status")
     */
    public function status(Request $request): Response
    {
        if (!$this->getUser()->isGoogleAuthenticatorEnabled()) {
            return $this->redirectToRoute('account_two_factor_start');
        }

        return $this->render('account/two-factor-authentication/status.html.twig');
    }

    /**
     * @Route("/start", name="account_two_factor_start")
     * @Route("/start/force", name="account_two_factor_start_force")
     */
    public function start(Request $request, GoogleAuthenticatorInterface $googleAuthenticator, QrCodeGenerator $qrCodeGenerator): Response
    {
        if ($this->getUser()->isGoogleAuthenticatorEnabled() && $request->get('_route') !== 'account_two_factor_start_force') {
            return $this->redirectToRoute('account_two_factor_status');
        } else {
            $user = $this->getUser();

            $form = $this->createQrCodeForm();
            $secret = $googleAuthenticator->generateSecret();
            $user->setGoogleAuthenticatorSecret($secret);
            $qrCode = $qrCodeGenerator->getGoogleAuthenticatorQrCode($user);
            $form->get('secret')->setData($secret);

            return $this->render('account/two-factor-authentication/start.html.twig', [
                'form' => $form->createView(),
                'secret' => $secret,
                'qrCode' => $qrCode->writeDataUri()
            ]);
        }
    }

    /**
     * @Route("/verify", name="account_two_factor_verify")
     */
    public function verify(Request $request): Response
    {
        $form = $this->createQrCodeForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $verifyForm = $this->createVerifyForm();
            $verifyForm->get('secret')->setData($form->get('secret')->getData());

            return $this->render('account/two-factor-authentication/verify.html.twig', [
                'form' => $verifyForm->createView(),
            ]);
        }
    }

    /**
     * @Route("/backup-codes", name="account_two_factor_backup_codes")
     */
    public function backupCodes(Request $request, GoogleAuthenticatorInterface $googleAuthenticator): Response
    {
        $form = $this->createVerifyForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $secret = $form->get('secret')->getData();
            $token = $form->get('token')->getData();

            $user = $this->getUser();
            $user->setGoogleAuthenticatorSecret($secret);

            // If the verification fails, redirect back to the start
            if (!$googleAuthenticator->checkCode($user, $token)) {
                $session = $request->getSession();
                $session->getFlashBag()->add(
                    'error',
                    $this->translator->trans(
                        'account.twoFactor.error.verificationFailed',
                        [],
                        'mosparo'
                    )
                );

                return $this->redirectToRoute('account_two_factor_start');
            }

            // Generate the backup codes
            $backupCodes = $this->generateBackupCodes();

            // Save the secret
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

            return $this->render('account/two-factor-authentication/backup-codes.html.twig', [
                'backupCodes' => $backupCodes
            ]);
        }

        return $this->redirectToRoute('account_two_factor_auth');
    }

    /**
     * @Route("/reset-backup-codes", name="account_two_factor_reset_backup_codes")
     */
    public function resetBackupCodes(Request $request): Response
    {
        // Generate the backup codes
        $backupCodes = $this->generateBackupCodes();

        // Save the secret
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->flush();

        return $this->render('account/two-factor-authentication/backup-codes.html.twig', [
            'backupCodes' => $backupCodes
        ]);
    }

    /**
     * @Route("/disable", name="account_two_factor_disable")
     */
    public function disable(Request $request): Response
    {
        $user = $this->getUser();
        $user->setGoogleAuthenticatorSecret(null);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->flush();

        $session = $request->getSession();
        $session->getFlashBag()->add(
            'success',
            $this->translator->trans(
                'account.twoFactor.message.successfullyDisabled',
                [],
                'mosparo'
            )
        );

        return $this->redirectToRoute('account_overview');
    }

    protected function generateBackupCodes(): array
    {
        $user = $this->getUser();
        $user->resetBackupCodes();
        $backupCodes = [];
        $tokenGenerator = new TokenGenerator();

        for ($i = 0; $i < 4; $i++) {
            $code = $tokenGenerator->generateShortToken();
            $backupCodes[] = $code;
            $user->addBackupCode($code);
        }

        return $backupCodes;
    }

    protected function createQrCodeForm(): Form
    {
        $form = $this->createFormBuilder([], ['translation_domain' => 'mosparo'])
            ->setAction($this->generateUrl('account_two_factor_verify'))
            ->add('secret', HiddenType::class)
            ->getForm();

        return $form;
    }

    protected function createVerifyForm(): Form
    {
        $form = $this->createFormBuilder([], ['translation_domain' => 'mosparo'])
            ->setAction($this->generateUrl('account_two_factor_backup_codes'))
            ->add('token', TextType::class, ['attr' => [
                'autocomplete' => 'one-time-code',
                'autofocus' => 'autofocus',
                'inputmode' => 'numeric',
                'pattern' => '[0-9]*',
            ]])
            ->add('secret', HiddenType::class)
            ->getForm();

        return $form;
    }
}