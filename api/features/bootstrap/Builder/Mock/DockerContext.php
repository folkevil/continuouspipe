<?php

namespace Builder\Mock;

use Behat\Behat\Context\Context;
use ContinuousPipe\Builder\Docker\Exception\DaemonException;
use ContinuousPipe\Builder\Tests\Docker\CallbackDockerClient;
use ContinuousPipe\Builder\Tests\Docker\CallbackDockerDockerFacade;

class DockerContext implements Context
{
    /**
     * @var CallbackDockerClient
     */
    private $callbackDockerClient;

    /**
     * @var int
     */
    private $callCount = 0;

    /**
     * @param CallbackDockerClient $callbackDockerClient
     */
    public function __construct(CallbackDockerClient $callbackDockerClient)
    {
        $this->callbackDockerClient = $callbackDockerClient;
    }

    /**
     * @Given the push will fail because of a daemon error the first time
     */
    public function thePushWillFailBecauseOfADaemonErrorTheFirstTime()
    {
        $this->callbackDockerClient->setPushCallback(function() {
            throw new DaemonException('That expected exception');
        });
    }

    /**
     * @Given the Docker build will fail because of :reason
     */
    public function theDockerBuildWillFailBecauseOf($reason)
    {
        $this->callbackDockerClient->setBuildCallback(function() use ($reason) {
            throw new DaemonException($reason);
        });
    }

    /**
     * @Then the file :path in the image :image should exists
     */
    public function theFileInTheImageShouldExists($path, $image)
    {
    }

    /**
     * @Then the file :path in the image :image should contain :contents
     */
    public function theFileInTheImageShouldContain($path, $image, $contents)
    {
    }

    /**
     * @Given the push will be successful the second time
     */
    public function thePushWillBeSuccessfulTheSecondTime()
    {
        $callback = $this->callbackDockerClient->getPushCallback();
        $this->callbackDockerClient->setPushCallback(function($context, $image) use ($callback) {
            $this->callCount++;

            if ($this->callCount == 2) {
                $callback = CallbackDockerClient::getPushSuccessCallback();
            }

            return $callback($context, $image);
        });
    }
}
