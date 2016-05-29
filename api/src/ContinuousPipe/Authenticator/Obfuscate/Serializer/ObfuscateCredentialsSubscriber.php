<?php

namespace ContinuousPipe\Authenticator\Obfuscate\Serializer;

use ContinuousPipe\Authenticator\Security\User\SystemUser;
use ContinuousPipe\Security\Credentials\Cluster;
use ContinuousPipe\Security\Credentials\DockerRegistry;
use ContinuousPipe\Security\Credentials\GitHubToken;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ObfuscateCredentialsSubscriber implements EventSubscriberInterface
{
    const OBFUSCATE_PLACEHOLDER = 'OBFUSCATED';

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var array
     */
    private $overrides = [];

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            [
                'event' => 'serializer.pre_serialize',
                'method' => 'preSerializeKubernetesCluster',
                'class' => Cluster\Kubernetes::class,
            ],
            [
                'event' => 'serializer.pre_serialize',
                'method' => 'preSerializeDockerRegistry',
                'class' => DockerRegistry::class,
            ],
            [
                'event' => 'serializer.pre_serialize',
                'method' => 'preSerializeGithubToken',
                'class' => GitHubToken::class,
            ],
            [
                'event' => 'serializer.post_serialize',
                'method' => 'postSerialize',
            ],
        ];
    }

    /**
     * @param ObjectEvent $event
     */
    public function preSerializeKubernetesCluster(ObjectEvent $event)
    {
        if ($this->shouldObfuscate()) {
            $this->override($event->getObject(), 'password', self::OBFUSCATE_PLACEHOLDER);
        }
    }

    /**
     * @param ObjectEvent $event
     */
    public function preSerializeDockerRegistry(ObjectEvent $event)
    {
        if ($this->shouldObfuscate()) {
            $this->override($event->getObject(), 'password', self::OBFUSCATE_PLACEHOLDER);
        }
    }

    /**
     * @param ObjectEvent $event
     */
    public function preSerializeGithubToken(ObjectEvent $event)
    {
        if ($this->shouldObfuscate()) {
            $this->override($event->getObject(), 'accessToken', self::OBFUSCATE_PLACEHOLDER);
        }
    }

    /**
     * @param ObjectEvent $event
     */
    public function postSerialize()
    {
        while (null !== ($override = array_pop($this->overrides))) {
            $property = $this->getProperty($override[0], $override[1]);
            $property->setValue($override[0], $override[2]);
        }
    }

    /**
     * @param mixed  $object
     * @param string $propertyName
     * @param string $value
     */
    private function override($object, $propertyName, $value)
    {
        $property = $this->getProperty($object, $propertyName);

        $previousValue = $property->getValue($object);
        $property->setValue($object, $value);

        $this->overrides[] = [$object, $propertyName, $previousValue];
    }

    /**
     * @return bool
     */
    private function shouldObfuscate()
    {
        if (null === ($token = $this->tokenStorage->getToken())) {
            return true;
        }

        $isSystemUser = $token->getUser() instanceof SystemUser;

        return !$isSystemUser;
    }

    /**
     * @param mixed  $object
     * @param string $propertyName
     *
     * @return \ReflectionProperty
     */
    private function getProperty($object, $propertyName)
    {
        $property = (new \ReflectionObject($object))->getProperty($propertyName);
        $property->setAccessible(true);

        return $property;
    }
}
