<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Controller\APINew\Platform;

use Claroline\AnalyticsBundle\Manager\AnalyticsManager;
use Claroline\AppBundle\API\Utils\ArrayUtils;
use Claroline\AppBundle\Controller\RequestDecoderTrait;
use Claroline\AppBundle\Event\Platform\EnableEvent;
use Claroline\AppBundle\Event\Platform\ExtendEvent;
use Claroline\AppBundle\Event\StrictDispatcher;
use Claroline\CoreBundle\API\Serializer\ParametersSerializer;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Library\Configuration\PlatformConfigurationHandler;
use Claroline\CoreBundle\Library\Normalizer\DateNormalizer;
use Claroline\CoreBundle\Manager\VersionManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * REST API to manage platform parameters.
 */
class ParametersController
{
    use RequestDecoderTrait;

    /** @var StrictDispatcher */
    private $dispatcher;
    /** @var PlatformConfigurationHandler */
    private $config;
    /** @var AnalyticsManager */
    private $analyticsManager;
    /** @var VersionManager */
    private $versionManager;
    /** @var ParametersSerializer */
    private $serializer;

    /**
     * ParametersController constructor.
     *
     * @param StrictDispatcher             $dispatcher
     * @param PlatformConfigurationHandler $ch
     * @param AnalyticsManager             $analyticsManager
     * @param VersionManager               $versionManager
     * @param ParametersSerializer         $serializer
     */
    public function __construct(
        StrictDispatcher $dispatcher,
        PlatformConfigurationHandler $ch,
        AnalyticsManager $analyticsManager,
        VersionManager $versionManager,
        ParametersSerializer $serializer
    ) {
        $this->dispatcher = $dispatcher;
        $this->config = $ch;
        $this->serializer = $serializer;
        $this->versionManager = $versionManager;
        $this->analyticsManager = $analyticsManager;
    }

    /**
     * @EXT\Route("/parameters", name="apiv2_parameters_update")
     * @EXT\Method("PUT")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function updateAction(Request $request)
    {
        $parametersData = $this->decodeRequest($request);

        // easy way to protect locked parameters (this may be done elsewhere)
        // avoid replacing locked parameters
        ArrayUtils::remove($parametersData, 'lockedParameters');
        // removes locked parameters values if any
        $locked = $this->config->getParameter('lockedParameters') ?? [];
        foreach ($locked as $lockedParam) {
            ArrayUtils::remove($parametersData, $lockedParam);
        }

        // save updated parameters
        $parameters = $this->serializer->deserialize($parametersData);

        return new JsonResponse($parameters);
    }

    /**
     * @EXT\Route("/info", name="apiv2_platform_info")
     * @EXT\Method("GET")
     */
    public function getAction()
    {
        $parameters = $this->serializer->serialize();

        $analytics = $this->analyticsManager->count();

        // TODO : not the correct place to do it
        $usedStorage = $analytics['storage'];
        $parameters['restrictions']['used_storage'] = $usedStorage;
        $parameters['restrictions']['max_storage_reached'] = isset($parameters['restrictions']['max_storage_size']) &&
            $parameters['restrictions']['max_storage_size'] &&
            $usedStorage >= $parameters['restrictions']['max_storage_size'];
        $this->serializer->deserialize($parameters);

        return new JsonResponse([
            'version' => $this->versionManager->getDistributionVersion(),
            'meta' => $parameters['meta'],
            'analytics' => $analytics, // TODO : add analytics through eventing to avoid hard dependency to a plugin
        ]);
    }

    /**
     * Enables the platform.
     *
     * @EXT\Route("/enable", name="apiv2_platform_enable")
     * @EXT\Method("PUT")
     *
     * @return JsonResponse
     */
    public function enableAction()
    {
        /** @var EnableEvent $event */
        $event = $this->dispatcher->dispatch('platform.enable', EnableEvent::class);
        if ($event->isCanceled()) {
            return new JsonResponse($event->getCancellationMessage(), 422); // not sure it's the correct status
        }

        $this->config->setParameter('restrictions.disabled', false);

        return new JsonResponse(null, 204);
    }

    /**
     * Extends the period of availability of the platform.
     *
     * @EXT\Route("/extend", name="apiv2_platform_extend")
     * @EXT\Method("PUT")
     *
     * @return JsonResponse
     */
    public function extendAction()
    {
        $newEnd = null;

        $dates = $this->config->getParameter('restrictions.dates');
        if (!empty($dates) && !empty($dates[1])) {
            // only do something if there is an end date
            /** @var ExtendEvent $event */
            $event = $this->dispatcher->dispatch('platform.extend', ExtendEvent::class, [
                // by default extend for 1 week
                // event listener can override it
                DateNormalizer::denormalize($dates[1])->add(new \DateInterval('P7D')),
            ]);

            if ($event->isCanceled()) {
                return new JsonResponse($event->getCancellationMessage(), 422); // not sure it's the correct status
            }

            $newEnd = $event->getEnd();

            // replace date in config
            $dates[1] = DateNormalizer::normalize($newEnd);
            $this->config->setParameter('restrictions.dates', $dates);
        }

        return new JsonResponse($newEnd, 204);
    }

    /**
     * @EXT\Route("/disable", name="apiv2_platform_disable")
     * @EXT\Method("PUT")
     *
     * @return JsonResponse
     */
    public function disableAction()
    {
        $this->config->setParameter('restrictions.disabled', true);

        return new JsonResponse(null, 204);
    }

    /**
     * @EXT\Route("/maintenance/enable", name="apiv2_maintenance_enable")
     * @EXT\Method("PUT")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function enableMaintenanceAction(Request $request)
    {
        $this->config->setParameter('maintenance.enable', true);
        if (!empty($request->getContent())) {
            $this->config->setParameter('maintenance.message', $request->getContent());
        }

        return new JsonResponse(
            $this->config->getParameter('maintenance.message')
        );
    }

    /**
     * @EXT\Route("/maintenance/disable", name="apiv2_maintenance_disable")
     * @EXT\Method("PUT")
     *
     * @return JsonResponse
     */
    public function disableMaintenanceAction()
    {
        $this->config->setParameter('maintenance.enable', false);

        return new JsonResponse(null, 204);
    }
}
