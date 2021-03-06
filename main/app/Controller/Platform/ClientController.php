<?php

namespace Claroline\AppBundle\Controller\Platform;

use Claroline\AppBundle\API\SerializerProvider;
use Claroline\AppBundle\Event\StrictDispatcher;
use Claroline\AppBundle\Manager\SecurityManager;
use Claroline\CoreBundle\API\Serializer\Platform\ClientSerializer;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Event\Layout\InjectJavascriptEvent;
use Claroline\CoreBundle\Event\Layout\InjectStylesheetEvent;
use Claroline\CoreBundle\Library\Configuration\PlatformConfigurationHandler;
use Claroline\CoreBundle\Library\Maintenance\MaintenanceHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * ClientController.
 * It's responsible of the rendering of the Claroline web UI.
 */
class ClientController
{
    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var EngineInterface */
    private $templating;

    /** @var StrictDispatcher */
    private $dispatcher;

    /** @var PlatformConfigurationHandler */
    private $configHandler;

    /** @var SecurityManager */
    private $securityManager;

    /** @var SerializerProvider */
    private $serializer;

    /** @var ClientSerializer */
    private $clientSerializer;

    /**
     * ClientController constructor.
     *
     * @param TokenStorageInterface        $tokenStorage
     * @param EngineInterface              $templating
     * @param StrictDispatcher             $dispatcher
     * @param PlatformConfigurationHandler $configHandler
     * @param SecurityManager              $securityManager
     * @param SerializerProvider           $serializer
     * @param ClientSerializer             $clientSerializer
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        EngineInterface $templating,
        StrictDispatcher $dispatcher,
        PlatformConfigurationHandler $configHandler,
        SecurityManager $securityManager,
        SerializerProvider $serializer,
        ClientSerializer $clientSerializer
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->templating = $templating;
        $this->dispatcher = $dispatcher;
        $this->configHandler = $configHandler;
        $this->securityManager = $securityManager;
        $this->serializer = $serializer;
        $this->clientSerializer = $clientSerializer;
    }

    /**
     * Renders the Claroline web application.
     *
     * @EXT\Route("/", name="claro_index")
     *
     * @return Response
     */
    public function indexAction()
    {
        $currentUser = null;
        if ($this->tokenStorage->getToken()->getUser() instanceof User) {
            $currentUser = $this->serializer->serialize(
                $this->tokenStorage->getToken()->getUser()
            );
        }

        return new Response(
            $this->templating->render('ClarolineAppBundle::index.html.twig', [
                'parameters' => $this->clientSerializer->serialize(),
                'maintenance' => [
                    'enabled' => MaintenanceHandler::isMaintenanceEnabled() || $this->configHandler->getParameter('maintenance.enable'),
                    'message' => $this->configHandler->getParameter('maintenance.message'),
                ],
                'currentUser' => $currentUser,
                'impersonated' => $this->securityManager->isImpersonated(),

                'header' => [
                    'menus' => array_unique(array_values($this->configHandler->getParameter('header'))),
                    'display' => [
                        'name' => $this->configHandler->getParameter('name_active'),
                        'about' => $this->configHandler->getParameter('show_about_button'),
                        'help' => $this->configHandler->getParameter('show_help_button'),
                    ],
                ],
                'footer' => [
                    'content' => $this->configHandler->getParameter('footer.content'),
                    'display' => [
                        'locale' => $this->configHandler->getParameter('footer.show_locale'),
                        'help' => $this->configHandler->getParameter('footer.show_help'),
                        'termsOfService' => $this->configHandler->getParameter('footer.show_terms_of_service'),
                    ],
                ],

                'injectedJavascripts' => $this->injectJavascript(),
                'injectedStylesheets' => $this->injectStylesheet(),
            ])
        );
    }

    /**
     * Gets the javascript injected by the plugins if any.
     *
     * @return string
     */
    private function injectJavascript()
    {
        /** @var InjectJavascriptEvent $event */
        $event = $this->dispatcher->dispatch('layout.inject.javascript', InjectJavascriptEvent::class);

        return $event->getContent();
    }

    /**
     * Gets the styles injected by the plugins if any.
     *
     * @return string
     */
    private function injectStylesheet()
    {
        /** @var InjectStylesheetEvent $event */
        $event = $this->dispatcher->dispatch('layout.inject.stylesheet', InjectStylesheetEvent::class);

        return $event->getContent();
    }
}
