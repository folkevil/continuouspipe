<?php

namespace ContinuousPipe\River\Task\Deploy\Event;

use ContinuousPipe\Pipe\View\Deployment;
use ContinuousPipe\River\Event\TideEvent;
use ContinuousPipe\River\Task\TaskEvent;
use Ramsey\Uuid\Uuid;

class DeploymentEvent implements TideEvent, TaskEvent
{
    /**
     * @var Uuid
     */
    private $tideUuid;

    /**
     * @var Deployment
     */
    private $deployment;

    /**
     * @var string
     */
    private $taskId;

    /**
     * @param Uuid       $tideUuid
     * @param Deployment $deployment
     * @param string     $taskId
     */
    public function __construct(Uuid $tideUuid, Deployment $deployment, $taskId = null)
    {
        $this->tideUuid = $tideUuid;
        $this->deployment = $deployment;
        $this->taskId = $taskId;
    }

    /**
     * {@inheritdoc}
     */
    public function getTideUuid()
    {
        return $this->tideUuid;
    }

    /**
     * @return Deployment
     */
    public function getDeployment()
    {
        return $this->deployment;
    }

    /**
     * @return string|null
     */
    public function getTaskId()
    {
        return $this->taskId;
    }
}
