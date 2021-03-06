<?php

namespace GitHub\WebHook\Event;

use GitHub\WebHook\AbstractEvent;
use GitHub\WebHook\Model\PullRequest;
use GitHub\WebHook\Model\Repository;
use GitHub\WebHook\Model\User;
use JMS\Serializer\Annotation as JMS;

class PullRequestEvent extends AbstractEvent
{
    const ACTION_OPENED = 'opened';
    const ACTION_CLOSED = 'closed';
    const ACTION_SYNCHRONIZED = 'synchronize';
    const ACTION_ASSIGNED = 'assigned';
    const ACTION_UNASSIGNED = 'unassigned';
    const ACTION_LABELED = 'labeled';
    const ACTION_UNLABELED = 'unlabeled';
    const ACTION_REOPENED = 'reopened';

    /**
     * @var string
     *
     * @JMS\Type("string")
     */
    private $action;

    /**
     * @var int
     *
     * @JMS\Type("integer")
     */
    private $number;

    /**
     * @var PullRequest
     *
     * @JMS\Type("GitHub\WebHook\Model\PullRequest")
     */
    private $pullRequest;

    /**
     * @var Repository
     *
     * @JMS\Type("GitHub\WebHook\Model\Repository")
     */
    private $repository;

    /**
     * @var User
     *
     * @JMS\Type("GitHub\WebHook\Model\User")
     */
    private $sender;

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @return PullRequest
     */
    public function getPullRequest()
    {
        return $this->pullRequest;
    }

    /**
     * @return Repository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @return User
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'pull_request';
    }
}
