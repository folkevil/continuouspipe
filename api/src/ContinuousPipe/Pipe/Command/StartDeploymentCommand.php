<?php

namespace ContinuousPipe\Pipe\Command;

use ContinuousPipe\Message\Message;
use ContinuousPipe\Message\Transaction\LongRunningMessage;
use ContinuousPipe\Pipe\View\Deployment;

class StartDeploymentCommand implements Message, LongRunningMessage
{
    /**
     * @var Deployment
     */
    private $deployment;

    /**
     * @param Deployment $deployment
     */
    public function __construct(Deployment $deployment)
    {
        $this->deployment = $deployment;
    }

    /**
     * @return Deployment
     */
    public function getDeployment()
    {
        return $this->deployment;
    }
}
