<?php

namespace ContinuousPipe\River\Tide\Concurrency;

use ContinuousPipe\River\Repository\TideRepository;
use ContinuousPipe\River\Tide\Transaction\TransactionManager;
use ContinuousPipe\River\View\TimeResolver;
use ContinuousPipe\River\View\Tide;
use ContinuousPipe\River\View\TideRepository as TideViewRepository;
use ContinuousPipe\Security\Authenticator\AuthenticatorClient;
use LogStream\LoggerFactory;
use ContinuousPipe\River\Tide as TideAggregate;
use Psr\Log\LoggerInterface;

class HourlyLimitedConcurrencyManager implements TideConcurrencyManager
{
    /**
     * @var TideConcurrencyManager
     */
    private $decoratedConcurrencyManager;
    /**
     * @var TideViewRepository
     */
    private $tideViewRepository;
    /**
     * @var TimeResolver
     */
    private $timeResolver;
    /**
     * @var AuthenticatorClient
     */
    private $authenticatorClient;
    /**
     * @var LoggerFactory
     */
    private $loggerFactory;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var integer
     */
    private $limit;
    /**
     * @var TransactionManager
     */
    private $transactionManager;
    /**
     * @var int
     */
    private $retryStartInterval;

    public function __construct(
        TideConcurrencyManager $decoratedConcurrencyManager,
        TideViewRepository $tideViewRepository,
        TimeResolver $timeResolver,
        AuthenticatorClient $authenticatorClient,
        LoggerFactory $loggerFactory,
        LoggerInterface $logger,
        TransactionManager $transactionManager,
        int $retryStartInterval
    ) {
        $this->decoratedConcurrencyManager = $decoratedConcurrencyManager;
        $this->tideViewRepository = $tideViewRepository;
        $this->timeResolver = $timeResolver;
        $this->authenticatorClient = $authenticatorClient;
        $this->loggerFactory = $loggerFactory;
        $this->logger = $logger;
        $this->transactionManager = $transactionManager;
        $this->retryStartInterval = $retryStartInterval;
    }

    /**
     * {@inheritdoc}
     */
    public function tideStartRecommendation(Tide $tide) : StartingTideRecommendation
    {
        $limits = $this->getLimitByTide($tide);

        if ($this->hasReachedLimits($tide, $limits)) {
            $reason = sprintf('You\'ve used your %d tides per hour usage limit. This tide will start automatically in a moment.', $limits);
            $this->transactionManager->apply($tide->getUuid(), function (TideAggregate $tide) use ($reason) {
                $tide->notifyPendingReason($this->loggerFactory, $reason);
            });

            return StartingTideRecommendation::postponeTo(
                (new \DateTime())->add(new \DateInterval('PT'.$this->retryStartInterval.'S')),
                $reason
            );
        }

        return $this->decoratedConcurrencyManager->tideStartRecommendation($tide);
    }

    private function hasReachedLimits(Tide $tide, $limit) : bool
    {
        if (0 === $limit) {
            return false;
        }

        $startedTidesCount = $this->tideViewRepository->countStartedTidesByFlowSince(
            $tide->getFlowUuid(),
            $this->timeResolver->resolve()->modify('-1 hour')
        );

        return $startedTidesCount > $limit;
    }

    private function getLimitByTide(Tide $tide) : int
    {
        if (isset($this->limit)) {
            return $this->limit;
        }

        try {
            $this->limit = $this->authenticatorClient->findTeamUsageLimitsBySlug($tide->getTeam()->getSlug())->getTidesPerHour();
        } catch (\Exception $exception) {
            $this->logger->warning(
                'Can\'t get team usage limits',
                ['exception' => $exception, 'tide' => $tide, 'team' => $tide->getTeam()->getSlug()]
            );
            $this->limit = 0;
        } finally {
            return $this->limit;
        }
    }
}
