<?php

namespace ContinuousPipe\Pipe;

use ContinuousPipe\Pipe\DeploymentRequest\Notification;
use ContinuousPipe\Pipe\DeploymentRequest\Specification;
use ContinuousPipe\Pipe\DeploymentRequest\Target;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class DeploymentRequest
{
    /**
     * @var Target
     */
    private $target;

    /**
     * @var Specification
     */
    private $specification;

    /**
     * @var Notification
     */
    private $notification;

    /**
     * @var UuidInterface
     */
    private $credentialsBucket;

    /**
     * @param Target        $target
     * @param Specification $specification
     * @param UuidInterface $credentialsBucket
     * @param Notification  $notification
     */
    public function __construct(Target $target, Specification $specification, UuidInterface $credentialsBucket, Notification $notification = null)
    {
        $this->target = $target;
        $this->specification = $specification;
        $this->notification = $notification;
        $this->credentialsBucket = $credentialsBucket;
    }

    /**
     * @return Target
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @return Specification
     */
    public function getSpecification()
    {
        return $this->specification;
    }

    /**
     * @return Notification
     */
    public function getNotification()
    {
        return $this->notification;
    }

    /**
     * @return UuidInterface
     */
    public function getCredentialsBucket()
    {
        if (is_string($this->credentialsBucket)) {
            $this->credentialsBucket = Uuid::fromString($this->credentialsBucket);
        }

        return $this->credentialsBucket;
    }
}
