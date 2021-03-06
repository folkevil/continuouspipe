<?php

namespace ContinuousPipe\Pipe\Kubernetes\PublicEndpoint;

use ContinuousPipe\Pipe\Kubernetes\Client\DeploymentClientFactory;
use ContinuousPipe\Pipe\DeploymentContext;
use ContinuousPipe\Pipe\Environment\PublicEndpoint;
use ContinuousPipe\Pipe\Environment\PublicEndpointPort;
use ContinuousPipe\Pipe\Promise\PromiseBuilder;
use ContinuousPipe\Security\Credentials\Cluster;
use JMS\Serializer\SerializerInterface;
use Kubernetes\Client\Model\Ingress;
use Kubernetes\Client\Model\IngressHttpRulePath;
use Kubernetes\Client\Model\IngressRule;
use Kubernetes\Client\Model\KubernetesObject;
use Kubernetes\Client\Model\LoadBalancerIngress;
use Kubernetes\Client\Model\LoadBalancerStatus;
use Kubernetes\Client\Model\Service;
use Kubernetes\Client\Model\ServicePort;
use Kubernetes\Client\Model\ServiceSpecification;
use Kubernetes\Client\NamespaceClient;
use LogStream\Log;
use LogStream\Logger;
use LogStream\LoggerFactory;
use LogStream\Node\Complex;
use LogStream\Node\Text;
use React;

class LoopPublicEndpointWaiter implements PublicEndpointWaiter
{
    /**
     * @var DeploymentClientFactory
     */
    private $clientFactory;

    /**
     * @var LoggerFactory
     */
    private $loggerFactory;

    /**
     * @var SerializerInterface
     */
    private $serializer;
    /**
     * @var int
     */
    private $endpointTimeout;
    /**
     * @var int
     */
    private $endpointInterval;

    /**
     * @param DeploymentClientFactory $clientFactory
     * @param LoggerFactory $loggerFactory
     * @param SerializerInterface $serializer
     * @param int $endpointTimeout
     * @param int $endpointInterval
     */
    public function __construct(
        DeploymentClientFactory $clientFactory,
        LoggerFactory $loggerFactory,
        SerializerInterface $serializer,
        int $endpointTimeout,
        int $endpointInterval
    ) {
        $this->clientFactory = $clientFactory;
        $this->loggerFactory = $loggerFactory;
        $this->serializer = $serializer;
        $this->endpointTimeout = $endpointTimeout;
        $this->endpointInterval = $endpointInterval;
    }

    /**
     * @param DeploymentContext $context
     * @param KubernetesObject $object
     *
     * @return React\Promise\PromiseInterface
     *
     * @throws EndpointNotFound
     */
    public function waitEndpoints(
        React\EventLoop\LoopInterface $loop,
        DeploymentContext $context,
        KubernetesObject $object
    ) {
        $objectName = $object->getMetadata()->getName();
        $logger = $this->loggerFactory->from($context->getLog())->child(
            new Text('Waiting public endpoint of service ' . $objectName)
        );

        $logger->updateStatus(Log::RUNNING);

        return $this->waitPublicEndpoint($loop, $context, $object, $logger)->then(
            function (PublicEndpoint $endpoint) use ($logger) {
                $logger->updateStatus(Log::SUCCESS);

                return [
                    $endpoint,
                ];
            },
            function (EndpointNotFound $e) use ($logger) {
                $logger->updateStatus(Log::FAILURE);

                throw $e;
            }
        );
    }

    /**
     * @param NamespaceClient $namespaceClient
     * @param KubernetesObject $object
     * @param Logger $logger
     *
     * @return React\Promise\PromiseInterface
     */
    private function waitPublicEndpoint(
        React\EventLoop\LoopInterface $loop,
        DeploymentContext $context,
        KubernetesObject $object,
        Logger $logger
    ) {
        $namespaceClient = $this->clientFactory->get($context);
        $statusLogger = $logger->child(new Text('No public endpoint found yet.'));

        // Get endpoint status
        $publicEndpointStatusPromise = (new PromiseBuilder($loop))
            ->retry(
                $this->endpointInterval,
                function (React\Promise\Deferred $deferred) use ($context, $namespaceClient, $object, $statusLogger) {
                    try {
                        $endpoint = $this->getPublicEndpoint($context, $namespaceClient, $object);

                        $statusLogger->update(new Text('Found endpoint: ' . $endpoint->getAddress()));

                        $deferred->resolve($endpoint);
                    } catch (EndpointNotFound $e) {
                        $statusLogger->update(new Text($e->getMessage()));
                    }
                }
            )
            ->withTimeout($this->endpointTimeout)
            ->getPromise();

        // Get objects' events
        $eventsLogger = $logger->child(new Complex('events'));
        $updateEvents = function () use ($namespaceClient, $object, $eventsLogger) {
            $eventList = $namespaceClient->getEventRepository()->findByObject($object);

            $events = $eventList->getEvents();
            $eventsLogger->update(
                new Complex(
                    'events',
                    [
                        'events' => json_decode($this->serializer->serialize($events, 'json'), true),
                    ]
                )
            );
        };

        $timer = $loop->addPeriodicTimer($this->endpointInterval, $updateEvents);

        return $publicEndpointStatusPromise->then(
            function (PublicEndpoint $endpoint) use ($timer, $updateEvents) {
                $timer->cancel();
                $updateEvents();

                return $endpoint;
            },
            function (\Throwable $reason) use ($timer, $updateEvents) {
                $timer->cancel();
                $updateEvents();

                if ($reason instanceof React\Promise\Timer\TimeoutException) {
                    $reason = new EndpointNotFound('Endpoint still not found. Timed-out.', $reason->getCode(), $reason);
                }

                throw $reason;
            }
        );
    }

    /**
     * @param NamespaceClient $namespaceClient
     * @param KubernetesObject $object
     * @param DeploymentContext $deploymentContext
     *
     * @return PublicEndpoint
     *
     * @throws EndpointNotFound
     */
    private function getPublicEndpoint(DeploymentContext $deploymentContext, NamespaceClient $namespaceClient, KubernetesObject $object)
    {
        $name = $object->getMetadata()->getName();
        $ports = $this->getPorts($object);

        if ($this->isInternalEndpoint($object)) {
            return $this->createInternalPublicEndpoint($namespaceClient, $object, $name, $ports);
        }

        if (
            $object instanceof Service &&
            $object->getSpecification()->getType() == ServiceSpecification::TYPE_NODE_PORT
        ) {
            $upToDateService = $namespaceClient->getServiceRepository()->findOneByName($name);

            return new PublicEndpoint($name, $this->nodePortAddressFromCluster($deploymentContext->getCluster()), array_map(function (ServicePort $port) {
                return new PublicEndpointPort($port->getNodePort() ?: 12345, $port->getProtocol());
            }, $upToDateService->getSpecification()->getPorts()));
        }

        return $this->getPublicEndpointFromIngresses($namespaceClient, $object, $name, $ports);
    }

    /**
     * @param NamespaceClient $namespaceClient
     * @param KubernetesObject $object
     *
     * @return LoadBalancerStatus|null
     *
     * @throws EndpointNotFound
     */
    private function getLoadBalancerStatus(NamespaceClient $namespaceClient, KubernetesObject $object)
    {
        $objectName = $object->getMetadata()->getName();

        if ($object instanceof Service) {
            $status = $namespaceClient->getServiceRepository()->findOneByName($objectName)->getStatus();
        } elseif ($object instanceof Ingress) {
            $status = $namespaceClient->getIngressRepository()->findOneByName($objectName)->getStatus();
        } else {
            $status = null;
        }

        if (null === $status) {
            throw new EndpointNotFound('Status not found');
        }

        return $status->getLoadBalancer();
    }

    /**
     * @param KubernetesObject $object
     *
     * @return array
     */
    private function getPorts(KubernetesObject $object)
    {
        $portFromIngress = function (int $port) {
            return PublicEndpointPort::TCP($port);
        };

        $portFromService = function (ServicePort $servicePort) {
            return new PublicEndpointPort($servicePort->getPort(), $servicePort->getProtocol());
        };

        if ($object instanceof Service) {
            return array_map($portFromService, $object->getSpecification()->getPorts());
        }

        if ($object instanceof Ingress) {
            return array_map($portFromIngress, $this->getIngressPorts($object));
        }

        throw new EndpointNotFound('Unable to get the exposed ports from the ' . get_class($object));
    }

    /**
     * @param Ingress $ingress
     * @return \int[]
     */
    private function getIngressPorts(Ingress $ingress)
    {
        if (null !== $ingress->getSpecification()->getBackend()) {
            return [$ingress->getSpecification()->getBackend()->getServicePort()];
        }

        $portFromPath = function (IngressHttpRulePath $path) {
            return $path->getBackend()->getServicePort();
        };

        $portsFromRules = function (IngressRule $rule) use ($portFromPath) {
            return array_map($portFromPath, $rule->getHttp()->getPaths());
        };

        $ports = array_map($portsFromRules, $ingress->getSpecification()->getRules());

        return array_unique(array_merge(...$ports));
    }

    private function isInternalEndpoint(KubernetesObject $object): bool
    {
        return $object instanceof Service && $object->getMetadata()->getLabelList()->hasKey('internal-endpoint');
    }

    private function createInternalPublicEndpoint(
        NamespaceClient $namespaceClient,
        KubernetesObject $object,
        string $name,
        array $ports
    ): PublicEndpoint {
        return new PublicEndpoint(
            $name,
            sprintf(
                '%s.%s.svc.cluster.local',
                $object->getMetadata()->getName(),
                $namespaceClient->getNamespace()->getMetadata()->getName()
            ),
            $ports
        );
    }

    /**
     * @return LoadBalancerIngress[]
     */
    private function getIngresses(NamespaceClient $namespaceClient, KubernetesObject $object): array
    {
        $loadBalancer = $this->getLoadBalancerStatus($namespaceClient, $object);

        if (null === $loadBalancer) {
            throw new EndpointNotFound('No load balancer found');
        } elseif (0 === (count($ingresses = $loadBalancer->getIngresses()))) {
            throw new EndpointNotFound('No ingress found');
        }
        return $ingresses;
    }

    private function getPublicEndpointFromIngresses(
        NamespaceClient $namespaceClient,
        KubernetesObject $object,
        string $name,
        array $ports
    ): PublicEndpoint {
        foreach ($this->getIngresses($namespaceClient, $object) as $ingress) {
            if ($hostname = $ingress->getHostname()) {
                return new PublicEndpoint($name, $hostname, $ports);
            }

            if ($ip = $ingress->getIp()) {
                return new PublicEndpoint($name, $ip, $ports);
            }
        }

        throw new EndpointNotFound('No hostname or IP address found in ingresses');
    }

    private function nodePortAddressFromCluster(Cluster $cluster)
    {
        foreach ($cluster->getPolicies() as $policy) {
            if ($policy->getName() == 'endpoint' && isset($policy->getConfiguration()['node-port-address'])) {
                return $policy->getConfiguration()['node-port-address'];
            }
        }

        return 'unknown-node-address';
    }
}
