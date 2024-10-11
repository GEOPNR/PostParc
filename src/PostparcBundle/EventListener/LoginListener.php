<?php

namespace PostparcBundle\EventListener;

use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Cookie;

class LoginListener
{
    /**
     * @var \Doctrine\Common\Persistence\ObjectManager|mixed
     */
    public $em;
    /**
     * @var \Swift_Mailer
     */
    public $mailer;
    private $router;
    private $dispatcher;
    private $current_user;

    /**
     * @param Router                   $router
     * @param ObjectManager            $entityManager
     * @param TokenStorage             $security_context
     * @param \Swift_Mailer            $mailer
     */
    public function __construct(Router $router, EventDispatcherInterface $dispatcher, ObjectManager $entityManager, TokenStorage $security_context, \Swift_Mailer $mailer)
    {
        $this->router = $router;
        $this->dispatcher = $dispatcher;
        $this->em = $entityManager;
        $this->mailer = $mailer;
        if (null != $security_context->getToken()) {
            $this->current_user = $security_context->getToken()->getUser();
        }
    }

    /**
     * function use only for demo.
     *
     * @param InteractiveLoginEvent $event
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $request = $event->getRequest();

        // gestion envoi email pour la démo
        if ($request->request->has('loginEmailSend')) {
            $emailTester = $request->request->get('loginEmailSend');
            $civility = $request->request->get('civilitySend');
            $name = $request->request->get('nameSend');
            $structure = $request->request->get('structureSend');
            $profilDemo = $request->request->get('profilDemo');
            $informMe = $request->request->get('informMeSend');
            $body = 'Bonjour Charline,<br/>';
            $body .= '<br/>Je suis la mémoire centrale de postparc.<br/>';
            $body .= "<br/>Mes sondes m'indiquent qu'un utilisateur ayant l'adresse email <strong>'" . $emailTester . "'</strong> vient de se connecter à la démo de postparc avec le profil <strong>" . $profilDemo . '</strong>.';
            $body .= '<br/>Civilité saisie: <strong>' . $civility . '</strong>';
            $body .= '<br/>Nom saisi: <strong>' . $name . '</strong>';
            $body .= '<br/>Structure saisie: <strong>' . $structure . '</strong>';
            $body .= '<br/>Souhaite être informé des mises à jour : <strong>';
            $body .= ($informMe && 'true' == $informMe) ? 'Oui' : 'Non';
            $body .= "</strong><br/><br/>Je t'invite donc à mettre de côté cette adresse email et le recontacter en temps utile.";
            $body .= '<br/>';
            $body .= '<br/>Fin de la transmission.';

            $transport = new \Swift_SendmailTransport('/usr/sbin/sendmail -bs');
            $mailer = new \Swift_Mailer($transport);
            // Create a message
            $message = \Swift_Message::newInstance()
                ->setSubject('accès demo postparc')
                ->setFrom('no-reply@postparc.fr')
                //->setCc('philippe.godot@probesys.com')
                ->setTo('charline.lombardo@probesys.com')
                ->setBody($body, 'text/html')
                ;

            // Send the message
            $result = $mailer->send($message);
            $this->dispatcher->addListener(KernelEvents::RESPONSE, function (\Symfony\Component\HttpKernel\Event\FilterResponseEvent $event) {
                return $this->redirectToLoginAndAddingCookie($event);
            });
        }

        // test if alert have to be print on interface for current user
        $user = $this->current_user;
        $em = $this->em;
        $eventalerts = $em->getRepository('PostparcBundle:EventAlert')->getEventAlertsHaveToBeprintOnInterfaceForUser($user->getId());
        if (count($eventalerts) > 0) {
            $this->dispatcher->addListener(KernelEvents::RESPONSE, function (\Symfony\Component\HttpKernel\Event\FilterResponseEvent $event) {
                return $this->redirectToEventAlertPage($event);
            });
        }
        // stockage current entityId en session
        $session = new Session();
        $session->set('currentEntityId', $user->getEntity()->getId());
        // stockage specifics config
        $currentEntityConfig = $user->getEntity()->getConfigs();

        
        // surcharge eventuelle de configs par configs user
        if (is_array($user->getConfigs())) {
            $currentEntityConfig = array_merge($user->getEntity()->getConfigs(), $user->getConfigs());
        }

        if (!array_key_exists('tabsOrder', $currentEntityConfig)) {
            $currentEntityConfig['tabsOrder'] = [
                'persons' => 1,
                'pfos' => 2,
                'organizations' => 3,
                'representations' => 4,
            ];
        }
        if (!array_key_exists('empty_search_on_load', $currentEntityConfig)) {
            $currentEntityConfig['empty_search_on_load'] = false;
        }
        if (!array_key_exists('emptySpecificMessageField', $currentEntityConfig)) {
            $currentEntityConfig['emptySpecificMessageField'] = false;
        }
        if (!array_key_exists('hideSpecificMessageField', $currentEntityConfig)) {
            $currentEntityConfig['hideSpecificMessageField'] = false;
        }
        if (!array_key_exists('hideBlocSendWithSendingMailSoftware', $currentEntityConfig)) {
            $currentEntityConfig['hideBlocSendWithSendingMailSoftware'] = false;
        }
        
        // recherche personnalFieldsRestriction
        $personnalFieldsRestriction = $em->getRepository('PostparcBundle:PersonnalFieldsRestriction')->findOneBy(['entity' => $user->getEntity()->getId()]);
        $restrictions = [];
        if ($personnalFieldsRestriction && count($personnalFieldsRestriction->getRestrictions())) {
            $role = $user->getRoles()[0];
            if (array_key_exists($role, $personnalFieldsRestriction->getRestrictions())) {
                $restrictions = $personnalFieldsRestriction->getRestrictions()[$role];
            }
        }
        $currentEntityConfig['personnalFieldsRestriction'] = $restrictions;

        $session->set('currentEntityConfig', $currentEntityConfig);
    }

    /**
     * function to redirect user to eventAlert_showOnInterface.
     *
     * @param FilterResponseEvent $event
     */
    public function redirectToEventAlertPage(FilterResponseEvent $event)
    {
        $response = new RedirectResponse($this->router->generate('eventAlert_showOnInterface'));
        $event->setResponse($response);
    }

    /**
     * function use only for demo.
     *
     * @param FilterResponseEvent $event
     */
    public function redirectToLoginAndAddingCookie(FilterResponseEvent $event)
    {
        $response = new RedirectResponse($this->router->generate('homepage'));
        // stockage dans un cookie du fait que la personne a donné son email
        $cookie = new Cookie('demoPostparcAccessValidate', true, strtotime('now + 1 week'), '/', 'demo.postparc.fr', true, true, true);
        // set cookie in response
        $response->headers->setCookie($cookie);
        $event->setResponse($response);
    }
}
