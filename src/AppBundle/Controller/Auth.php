<?php

/**
 * Authentication controller
 *
 * @package ArionCRM
 * @author Isaac Raway <iraway@metasushi [dot] com>
 * @author Antoinette Smith <asmith@metasushi [dot] com>
 * @link http://arioncrm.com/
 * @copyright (c)2015-2022. MetaSushi, LLC. All rights reserved. Your use of this software in any way indicates agreement
 * to the software license available currenty at http://arioncrm.com/ 
 * This open source edition is released under GPL 3.0. available at https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Psr\Log\LoggerInterface;
use AppBundle\Service\SiteService;
use AppBundle\Service\AuthService;
use AppBundle\Service\AccountService;
use AppBundle\Entity\User;
use AppBundle\Form\UserForm;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;

/**
 * @Route(service="app.auth_controller")
 */
class Auth extends BaseController {

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    protected $router;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface
     */
    protected $view;

    /**
     * @var \AppBundle\Service\AuthService $authService
     */
    protected $authService;

    /**
     * @var \AppBundle\Service\SiteService $siteService
     */
    protected $siteService;

    /**
     * @var \AppBundle\Service\AccountService $accountService
     */
    protected $accountService;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var UserPasswordEncoderInterface
     */
    protected $passwordEncoder;

    /**
     * @var Doctrine
     */
    protected $doctrine;

    public function __construct(LoggerInterface $logger, RouterInterface $router, EngineInterface $view,
                                FormFactoryInterface $formFactory,
                                UserPasswordEncoderInterface $passwordEncoder,
                                Doctrine $doctrine,
                                AuthService $authService,
                                SiteService $siteService, AccountService $accountService)
    {
        $this->logger = $logger;
        $this->router = $router;
        $this->view = $view;
        $this->formFactory = $formFactory;
        $this->passwordEncoder = $passwordEncoder;
        $this->doctrine = $doctrine;

        $this->authService = $authService;
        $this->siteService = $siteService;
        $this->accountService = $accountService;
        
    }
    

    /**
     * @Route("/auth/login", name="auth_login")
     */
    public function login(Request $request)
    {
        $loginResult = $this->authService->login($request->getMethod() == 'GET');

        $this->logger->debug(__METHOD__.'::'.
                json_encode(['method'        => $request->getMethod(),
                             'last_username' => $loginResult->lastUsername,
                             'error'         => $loginResult->error,
                            ]));
        
        
        // Security system only returns if this is the initial form load or an error
        // has occured (either way, display the login form)
        return $this->view->renderResponse(
            'auth/login.html.twig',
            array(
                // last username entered by the user
                'last_username' => $loginResult->lastUsername,
                'error'         => $loginResult->error,
                'message'       => '',
            )
        );

        /*
        // Security system should perform redirect for us upon successful login
        if(!$result->error) {
            // Redirect to items list
            return new RedirectResponse($this->router->generate('items',
                                ['itemType' => 'tasks'],
                                UrlGeneratorInterface::ABSOLUTE_URL));
        } else {
            $vars['message'] .= 'Invalid login. Be sure you have activated your account (check your email!)';
        }
        */
        
    }

    /**
     * @Route("/login_check", name="login_check")
     */
    public function loginCheckAction()
    {
        // this controller will not be executed,
        // as the route is handled by the Security system
    }

    /**
     * @Route("/auth/register", name="auth_register")
     */
    public function register(Request $request)
    {
        $this->logger->debug(__METHOD__.'::'.$request->getMethod());
        // $vars = [
        //     'site' => $this->siteService,
        //     'message' => '',
        // ];

        // if($request->request->method() == 'POST') {
        //     $email = $request->request->get('email');
        //     $username = $request->request->get('username');
        //     $password = $request->request->get('password');
        //     $password_confirm = $request->request->get('password-confirm');

        //     if($password === $password_confirm) {
        //         $accountResult = $this->accountService->create($email, $username);
        //         $this->logger->debug(__METHOD__.'::account create result::'.json_encode($accountResult));
        //         if($accountResult->account)
        //         {
        //             $result = $this->authService->register($email, $username, $password, $accountResult->account->id, 0, AuthService::ADMIN);
        //             if($result)
        //             {
        //                 $this->session->putFlash('auth.message', 'Your account was created! You should get an email in a few minutes with an activation link: link that link before trying to log in or you\'ll be sad!');
        //             } else {
        //                 $this->session->putFlash('auth.error_message', 'Ugh, creating your account failed. Email us at the support contact address below and we\'ll get you sorted out.');
        //             }
        //             return $this->response->redirect($this->urlBuilder->toRoute('auth.login'));
        //         } else {
        //             $vars['message'] = 'There is already an account using that username or we had some other problem creating the root account. Try again with a different username.';
        //         }
        //     } else {
        //         $vars['message'] = 'Passwords do not match!';
        //     }


        //     // $this->logger->debug(__METHOD__.'::result::'.print_r($result, true));

        // }

        // return $this->view->renderResponse('auth.register', $vars);

        // 1) build the form
        $vars = [   'form'      => '',
                'error'         => '',
                'message'       => '',
                ];

        $user = new User();
        $user->setUserType(AuthService::ADMIN);
        $form = $this->formFactory->create(UserForm::class, $user);

        // 2) handle the submit (will only happen on POST)
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if(!$form->isValid()) {
                $vars['error'] = 'Registration was not valid, check your values and try again';
            } else {
                // Create an account for the new admin user
                // account name is same as user email address
                $this->accountService->create($user->getEmail()); 
                // 3) Encode the password (you could also do this via Doctrine listener)
                $password = $this->passwordEncoder->encodePassword($user, $user->getPlainPassword());
                $user->setPassword($password);

                // 4) save the User!
                $this->doctrine->getEntityManager()->persist($user);
                $this->doctrine->getEntityManager()->flush();

                // ... do any other work - like send them an email, etc
                // maybe set a "flash" success message for the user
                $this->logger->debug('registration success');
                return new RedirectResponse($this->router->generate('auth_login',
                                    [], UrlGeneratorInterface::ABSOLUTE_URL));
            }
        }

        $vars['form'] = $form->createView();
        return $this->view->renderResponse('auth/register.html.twig', $vars);
    }

    /**
     * @Route("/auth/forgot_password", name="auth_forgot_password")
     */
    public function forgot_password(Request $request)
    {
        //$this->logger->debug(__METHOD__.'::'.$request->request->method());
        $vars = [
            'site' => $this->siteService,
            'message' => '',
        ];

        return $this->view->renderResponse('auth.forgot_password', $vars);
    }

    /**
     * @Route("/auth/logout", name="logout")
     */
    public function logout(Request $request)
    {
        // this controller will not be executed,
        // as the route is handled by the Security system
    }

}
