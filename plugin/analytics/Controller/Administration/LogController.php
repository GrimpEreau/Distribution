<?php

namespace Claroline\AnalyticsBundle\Controller\Administration;

use Claroline\AppBundle\API\FinderProvider;
use Claroline\AppBundle\API\SerializerProvider;
use Claroline\AppBundle\Controller\AbstractSecurityController;
use Claroline\CoreBundle\Entity\Log\Log;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Manager\LogManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @EXT\Route("/tools/admin/logs")
 */
class LogController extends AbstractSecurityController
{
    /** @var FinderProvider */
    private $finder;

    /** @var SerializerProvider */
    private $serializer;

    /** @var LogManager */
    private $logManager;

    /** @var User */
    private $loggedUser;

    /**
     * LogController constructor.
     *
     * @param TokenStorageInterface $tokenStorage
     * @param FinderProvider        $finder
     * @param SerializerProvider    $serializer
     * @param LogManager            $logManager
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        FinderProvider $finder,
        SerializerProvider $serializer,
        LogManager $logManager
    ) {
        $this->loggedUser = $tokenStorage->getToken()->getUser();
        $this->finder = $finder;
        $this->serializer = $serializer;
        $this->logManager = $logManager;
    }

    /**
     * Get the name of the managed entity.
     *
     * @return string
     */
    public function getName()
    {
        return 'log';
    }

    public function getClass()
    {
        return Log::class;
    }

    /**
     * @EXT\Route("/", name="apiv2_admin_tool_logs_list")
     * @EXT\Method("GET")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function listAction(Request $request)
    {
        $this->canOpenAdminTool('dashboard');
        $query = $this->addOrganizationFilter($request->query->all());

        return new JsonResponse($this->finder->search(
            $this->getClass(),
            $query,
            []
        ));
    }

    /**
     * @EXT\Route("/csv", name="apiv2_admin_tool_logs_list_csv")
     * @EXT\Method("GET")
     *
     * @param Request $request
     *
     * @return StreamedResponse
     */
    public function listCsvAction(Request $request)
    {
        $this->canOpenAdminTool('dashboard');
        // Filter data, but return all of them
        $query = $this->addOrganizationFilter($request->query->all());
        $dateStr = date('YmdHis');

        return new StreamedResponse(function () use ($query) {
            $this->logManager->exportLogsToCsv($query);
        }, 200, [
            'Content-Type' => 'application/force-download',
            'Content-Disposition' => 'attachment; filename="actions_'.$dateStr.'.csv"',
        ]);
    }

    /**
     * @EXT\Route("/users/csv", name="apiv2_admin_tool_logs_list_users_csv")
     * @EXT\Method("GET")
     *
     * @param Request $request
     *
     * @return StreamedResponse
     */
    public function userActionsListCsvAction(Request $request)
    {
        $this->canOpenAdminTool('dashboard');
        // Filter data, but return all of them
        $query = $this->addOrganizationFilter($request->query->all());
        $dateStr = date('YmdHis');

        return new StreamedResponse(function () use ($query) {
            $this->logManager->exportUserActionToCsv($query);
        }, 200, [
            'Content-Type' => 'application/force-download',
            'Content-Disposition' => 'attachment; filename="user_actions_'.$dateStr.'.csv"',
        ]);
    }

    private function addOrganizationFilter($query)
    {
        if (!$this->loggedUser->hasRole('ROLE_ADMIN')) {
            $query['hiddenFilters']['organization'] = $this->loggedUser->getAdministratedOrganizations();
        }

        return $query;
    }
}
