<?php

namespace ContinuousPipe\Pipe\Tests;

use ContinuousPipe\Adapter\Adapter;
use ContinuousPipe\Adapter\ProviderRepository;

class FakeAdapter implements Adapter
{
    /**
     * @var ProviderRepository
     */
    private $providerRepository;
    /**
     * @var FakeClientFactory
     */
    private $fakeClientFactory;

    /**
     * @param ProviderRepository $providerRepository
     * @param FakeClientFactory  $fakeClientFactory
     */
    public function __construct(ProviderRepository $providerRepository, FakeClientFactory $fakeClientFactory)
    {
        $this->providerRepository = $providerRepository;
        $this->fakeClientFactory = $fakeClientFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'fake';
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationClass()
    {
        return FakeProvider::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository()
    {
        return $this->providerRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getEnvironmentClientFactory()
    {
        return $this->fakeClientFactory;
    }
}
