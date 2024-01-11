<?php

namespace Mosparo\Controller;

use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="security_login", condition="ip_on_allow_list_routing(request.getClientIp(), env('backend_access_ip_allow_list'))")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/login", name="security_login_unauthorized")
     */
    public function loginAccessDenied(): Response
    {
        /**
         * It's necessary to have these two /login routes. The problem is that the firewall will redirect the
         * user to the /login route, as long as the condition is not met. This will end in an endless loop, so
         * with this additional route, we can make sure that there is no endless loop if the backend access is
         * restricted.
         */
        throw new AccessDeniedHttpException('Access denied.');
    }

    /**
     * @Route("/logout", name="security_logout")
     */
    public function logout()
    {
        throw new LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
