<?php

namespace ContinuousPipe\Adapter\Kubernetes\Client;

use ContinuousPipe\Adapter\Kubernetes\KubernetesProvider;
use Kubernetes\Client\Client;

interface KubernetesClientFactory
{
    /**
     * @param KubernetesProvider $provider
     *
     * @return Client
     */
    public function getByProvider(KubernetesProvider $provider);
}
