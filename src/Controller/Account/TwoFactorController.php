<?php

namespace Mosparo\Controller\Account;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Util\TokenGenerator;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\QrCode\QrCodeGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/account/two-factor')]
class TwoFactorController extends AbstractController
{
    protected TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    #[Route('/status', name: 'account_two_factor_status')]
    public function status(): Response
    {
        /** @var \Mosparo\Entity\User $user */
        $user = $this->getUser();

        if (!$user->isGoogleAuthenticatorEnabled()) {
            return $this->redirectToRoute('account_two_factor_start');
        }

        return $this->render('account/two-factor-authentication/status.html.twig');
    }

    #[Route('/start', name: 'account_two_factor_start')]
    #[Route('/start/force', name: 'account_two_factor_start_force')]
    public function start(Request $request, GoogleAuthenticatorInterface $googleAuthenticator, QrCodeGenerator $qrCodeGenerator): Response
    {
        /** @var \Mosparo\Entity\User $user */
        $user = $this->getUser();

        if ($user->isGoogleAuthenticatorEnabled() && $request->attributes->get('_route') !== 'account_two_factor_start_force') {
            return $this->redirectToRoute('account_two_factor_status');
        } else {
            $form = $this->createQrCodeForm();
            $secret = $googleAuthenticator->generateSecret();
            $user->setGoogleAuthenticatorSecret($secret);
            $qrCode = $qrCodeGenerator->getGoogleAuthenticatorQrCode($user);
            $form->get('secret')->setData($secret);

            $request->getSession()->set('qrCode', $qrCode->writeString());

            return $this->render('account/two-factor-authentication/start.html.twig', [
                'form' => $form->createView(),
                'secret' => $secret,
            ]);
        }
    }

    #[Route('/qrcode', name: 'account_two_factor_qrcode')]
    public function qrcode(Request $request)
    {
        $response = new Response($request->getSession()->get('qrCode', ''));

        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, 'qrcode.png');
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', 'image/png');

        $request->getSession()->remove('qrCode');

        return $response;
    }

    #[Route('/verify', name: 'account_two_factor_verify')]
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

        return $this->redirectToRoute('account_two_factor_status');
    }

    #[Route('/backup-codes', name: 'account_two_factor_backup_codes')]
    public function backupCodes(Request $request, EntityManagerInterface $entityManager, GoogleAuthenticatorInterface $googleAuthenticator): Response
    {
        $form = $this->createVerifyForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $secret = $form->get('secret')->getData();
            $token = $form->get('token')->getData();

            /** @var \Mosparo\Entity\User $user */
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
            $entityManager->flush();

            return $this->render('account/two-factor-authentication/backup-codes.html.twig', [
                'backupCodes' => $backupCodes
            ]);
        }

        return $this->redirectToRoute('account_two_factor_auth');
    }

    #[Route('/reset-backup-codes', name: 'account_two_factor_reset_backup_codes')]
    public function resetBackupCodes(EntityManagerInterface $entityManager): Response
    {
        // Generate the backup codes
        $backupCodes = $this->generateBackupCodes();

        // Save the secret
        $entityManager->flush();

        return $this->render('account/two-factor-authentication/backup-codes.html.twig', [
            'backupCodes' => $backupCodes
        ]);
    }

    #[Route('/disable', name: 'account_two_factor_disable')]
    public function disable(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var \Mosparo\Entity\User $user */
        $user = $this->getUser();
        $user->setGoogleAuthenticatorSecret(null);

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
        /** @var \Mosparo\Entity\User $user */
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

    protected function createQrCodeForm(): FormInterface
    {
        return $this->createFormBuilder([], ['translation_domain' => 'mosparo'])
            ->setAction($this->generateUrl('account_two_factor_verify'))
            ->add('secret', HiddenType::class)
            ->getForm();
    }

    protected function createVerifyForm(): FormInterface
    {
        return $this->createFormBuilder([], ['translation_domain' => 'mosparo'])
            ->setAction($this->generateUrl('account_two_factor_backup_codes'))
            ->add('token', TextType::class, ['attr' => [
                'autocomplete' => 'one-time-code',
                'autofocus' => 'autofocus',
                'inputmode' => 'numeric',
                'pattern' => '[0-9]*',
            ]])
            ->add('secret', HiddenType::class)
            ->getForm();
    }
}