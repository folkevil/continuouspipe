<?php

namespace ContinuousPipe\Builder;

use ContinuousPipe\Builder\Request\BuildRequest;
use ContinuousPipe\River\CodeReference;
use ContinuousPipe\River\Task\Build\BuildTaskConfiguration;
use Psr\Log\LoggerInterface;

class BuildRequestCreator
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param CodeReference          $codeReference
     * @param BuildTaskConfiguration $configuration
     *
     * @return Request\BuildRequest[]
     *
     * @throws BuilderException
     */
    public function createBuildRequests(CodeReference $codeReference, BuildTaskConfiguration $configuration)
    {
        $this->logger->info('Creating build requests', [
            'codeReference' => $codeReference,
            'configuration' => $configuration,
        ]);

        $buildRequests = [];
        foreach ($configuration->getServices() as $serviceName => $service) {
            $image = new Image($service->getImage(), $service->getTag());
            $buildRequestRepository = new Repository($codeReference->getRepository()->getAddress(), $codeReference->getCommitSha());
            $buildRequests[] = new BuildRequest(
                $buildRequestRepository,
                $image,
                new Context(
                    $service->getDockerfilePath(),
                    $service->getBuildDirectory()
                ),
                null, null,
                $configuration->getEnvironment()
            );
        }

        return $buildRequests;
    }
}
