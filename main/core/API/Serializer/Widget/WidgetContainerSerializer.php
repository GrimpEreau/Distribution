<?php

namespace Claroline\CoreBundle\API\Serializer\Widget;

use Claroline\AppBundle\API\Options;
use Claroline\AppBundle\API\Serializer\SerializerTrait;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\API\Finder\Home\WidgetInstanceFinder;
use Claroline\CoreBundle\API\Serializer\File\PublicFileSerializer;
use Claroline\CoreBundle\Entity\File\PublicFile;
use Claroline\CoreBundle\Entity\Widget\WidgetContainer;
use Claroline\CoreBundle\Entity\Widget\WidgetContainerConfig;
use Claroline\CoreBundle\Entity\Widget\WidgetInstance;

class WidgetContainerSerializer
{
    use SerializerTrait;

    /** @var ObjectManager */
    private $om;

    /** @var WidgetInstanceFinder */
    private $widgetInstanceFinder;

    /** @var WidgetInstanceSerializer */
    private $widgetInstanceSerializer;

    /** @var PublicFileSerializer */
    private $publicFileSerializer;

    /**
     * WidgetContainerSerializer constructor.
     *
     * @param ObjectManager            $om
     * @param WidgetInstanceSerializer $widgetInstanceSerializer
     * @param WidgetInstanceFinder     $widgetInstanceFinder
     * @param PublicFileSerializer     $publicFileSerializer
     */
    public function __construct(
        ObjectManager $om,
        WidgetInstanceFinder $widgetInstanceFinder,
        WidgetInstanceSerializer $widgetInstanceSerializer,
        PublicFileSerializer $publicFileSerializer
    ) {
        $this->om = $om;
        $this->widgetInstanceFinder = $widgetInstanceFinder;
        $this->widgetInstanceSerializer = $widgetInstanceSerializer;
        $this->publicFileSerializer = $publicFileSerializer;
    }

    public function getName()
    {
        return 'widget_container';
    }

    public function getClass()
    {
        return WidgetContainer::class;
    }

    public function serialize(WidgetContainer $widgetContainer, array $options = []): array
    {
        $widgetContainerConfig = $widgetContainer->getWidgetContainerConfigs()[0];

        $contents = [];
        $arraySize = count($widgetContainerConfig->getLayout());

        for ($i = 0; $i < $arraySize; ++$i) {
            $contents[$i] = null;
        }

        foreach ($widgetContainer->getInstances() as $widgetInstance) {
            $config = $widgetInstance->getWidgetInstanceConfigs()[0];

            if ($config) {
                $contents[$config->getPosition()] = $this->widgetInstanceSerializer->serialize($widgetInstance, $options);
            }
        }

        return [
            'id' => $widgetContainer->getUuid(),
            'name' => $widgetContainerConfig->getName(),
            'visible' => $widgetContainerConfig->isVisible(),
            'display' => $this->serializeDisplay($widgetContainerConfig),
            'contents' => $contents,
        ];
    }

    public function serializeDisplay(WidgetContainerConfig $widgetContainerConfig)
    {
        $display = [
            'layout' => $widgetContainerConfig->getLayout(),
            'alignName' => $widgetContainerConfig->getAlignName(),
            'color' => $widgetContainerConfig->getColor(),
            'borderColor' => $widgetContainerConfig->getBorderColor(),
            'backgroundType' => $widgetContainerConfig->getBackgroundType(),
            'background' => $widgetContainerConfig->getBackground(),
        ];

        if ('image' === $widgetContainerConfig->getBackgroundType() && $widgetContainerConfig->getBackground()) {
            /** @var PublicFile $file */
            $file = $this->om
                ->getRepository(PublicFile::class)
                ->findOneBy(['url' => $widgetContainerConfig->getBackground()]);

            if ($file) {
                $display['background'] = $this->publicFileSerializer->serialize($file);
            }
        } else {
            $display['background'] = $widgetContainerConfig->getBackground();
        }

        return $display;
    }

    public function deserialize($data, WidgetContainer $widgetContainer, array $options): WidgetContainer
    {
        if (!in_array(Options::REFRESH_UUID, $options)) {
            $this->sipe('id', 'setUuid', $data, $widgetContainer);
        } else {
            $widgetContainer->refreshUuid();
        }

        $widgetContainerConfig = $this->om->getRepository(WidgetContainerConfig::class)
            ->findOneBy(['widgetContainer' => $widgetContainer]);

        if (!$widgetContainerConfig || in_array(Options::REFRESH_UUID, $options)) {
            $widgetContainerConfig = new WidgetContainerConfig();
            $widgetContainerConfig->setWidgetContainer($widgetContainer);
        }

        $this->sipe('name', 'setName', $data, $widgetContainerConfig);
        $this->sipe('visible', 'setVisible', $data, $widgetContainerConfig);
        $this->sipe('display.layout', 'setLayout', $data, $widgetContainerConfig);
        $this->sipe('display.alignName', 'setAlignName', $data, $widgetContainerConfig);
        $this->sipe('display.color', 'setColor', $data, $widgetContainerConfig);
        $this->sipe('display.borderColor', 'setBorderColor', $data, $widgetContainerConfig);
        $this->sipe('display.backgroundType', 'setBackgroundType', $data, $widgetContainerConfig);

        if (isset($data['display'])) {
            if (isset($data['display']['background']) && isset($data['display']['background']['url'])) {
                $this->sipe('display.background.url', 'setBackground', $data, $widgetContainerConfig);
            } else {
                $this->sipe('display.background', 'setBackground', $data, $widgetContainerConfig);
            }
        }

        if (isset($data['contents'])) {
            /** @var WidgetInstance[] $currentInstances */
            $currentInstances = $widgetContainer->getInstances()->toArray();
            $instanceIds = [];

            // updates instances
            foreach ($data['contents'] as $index => $content) {
                if ($content) {
                    if (isset($content['id'])) {
                        $widgetInstance = $widgetContainer->getInstance($content['id']);
                    }

                    if (empty($widgetInstance)) {
                        $widgetInstance = new WidgetInstance();
                    }

                    $this->widgetInstanceSerializer->deserialize($content, $widgetInstance, $options);
                    $widgetInstanceConfig = $widgetInstance->getWidgetInstanceConfigs()[0];
                    $widgetInstanceConfig->setPosition($index);
                    $widgetContainer->addInstance($widgetInstance);

                    $instanceIds[] = $widgetInstance->getUuid();
                }
            }

            // removes instances which no longer exists
            foreach ($currentInstances as $currentInstance) {
                if (!in_array($currentInstance->getUuid(), $instanceIds)) {
                    $widgetContainer->removeInstance($currentInstance);
                    $this->om->remove($currentInstance);
                }
            }
        }

        return $widgetContainer;
    }
}
