<?php

namespace ContinuousPipe\Adapter\Kubernetes\Tests;

use Kubernetes\Client\NamespaceClient;
use Kubernetes\Client\Repository\PodRepository;
use Kubernetes\Client\Repository\ReplicationControllerRepository;
use Kubernetes\Client\Repository\SecretRepository;
use Kubernetes\Client\Repository\ServiceAccountRepository;
use Kubernetes\Client\Repository\ServiceRepository;

class InjectedRepositoriesNamespaceClient implements NamespaceClient
{
    /**
     * @var PodRepository
     */
    private $podRepository;

    /**
     * @var ServiceRepository
     */
    private $serviceRepository;

    /**
     * @var ReplicationControllerRepository
     */
    private $replicationControllerRepository;

    /**
     * @var SecretRepository
     */
    private $secretRepository;

    /**
     * @var ServiceAccountRepository
     */
    private $serviceAccountRepository;

    /**
     * @param PodRepository                   $podRepository
     * @param ServiceRepository               $serviceRepository
     * @param ReplicationControllerRepository $replicationControllerRepository
     * @param SecretRepository                $secretRepository
     * @param ServiceAccountRepository        $serviceAccountRepository
     */
    public function __construct(PodRepository $podRepository, ServiceRepository $serviceRepository, ReplicationControllerRepository $replicationControllerRepository, SecretRepository $secretRepository, ServiceAccountRepository $serviceAccountRepository)
    {
        $this->podRepository = $podRepository;
        $this->serviceRepository = $serviceRepository;
        $this->replicationControllerRepository = $replicationControllerRepository;
        $this->secretRepository = $secretRepository;
        $this->serviceAccountRepository = $serviceAccountRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getPodRepository()
    {
        return $this->podRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getServiceRepository()
    {
        return $this->serviceRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getReplicationControllerRepository()
    {
        return $this->replicationControllerRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getSecretRepository()
    {
        return $this->secretRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getServiceAccountRepository()
    {
        return $this->serviceAccountRepository;
    }
}
