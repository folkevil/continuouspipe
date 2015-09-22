<?php

namespace ContinuousPipe\River;

use ContinuousPipe\River\Event\TideCreated;
use ContinuousPipe\River\Event\TideEvent;
use ContinuousPipe\River\Repository\FlowRepository;
use ContinuousPipe\River\Task\TaskContext;
use ContinuousPipe\River\Task\TaskFactoryRegistry;
use ContinuousPipe\River\Task\TaskList;
use LogStream\LoggerFactory;
use LogStream\Node\Text;
use Rhumsaa\Uuid\Uuid;

class TideFactory
{
    /**
     * @var LoggerFactory
     */
    private $loggerFactory;

    /**
     * @var TaskFactoryRegistry
     */
    private $taskFactoryRegistry;

    /**
     * @var FlowRepository
     */
    private $flowRepository;

    /**
     * @var TideConfigurationFactory
     */
    private $configurationFactory;

    /**
     * @param LoggerFactory            $loggerFactory
     * @param TaskFactoryRegistry      $taskFactoryRegistry
     * @param FlowRepository           $flowRepository
     * @param TideConfigurationFactory $configurationFactory
     */
    public function __construct(LoggerFactory $loggerFactory, TaskFactoryRegistry $taskFactoryRegistry, FlowRepository $flowRepository, TideConfigurationFactory $configurationFactory)
    {
        $this->loggerFactory = $loggerFactory;
        $this->taskFactoryRegistry = $taskFactoryRegistry;
        $this->flowRepository = $flowRepository;
        $this->configurationFactory = $configurationFactory;
    }

    /**
     * @param Flow          $flow
     * @param CodeReference $codeReference
     *
     * @return Tide
     */
    public function createFromCodeReference(Flow $flow, CodeReference $codeReference)
    {
        $log = $this->loggerFactory->create()->getLog();

        try {
            $configuration = $this->configurationFactory->getConfiguration($flow, $codeReference);
        } catch (TideConfigurationException $e) {
            $configuration = [];

            $logger = $this->loggerFactory->from($log);
            $logger->append(new Text(sprintf(
                'Unable to create tide task list: %s',
                $e->getMessage()
            )));

            $logger->failure();
        }

        $tideContext = TideContext::createTide(
            $flow->getContext(),
            Uuid::uuid1(),
            $codeReference,
            $log,
            $configuration
        );

        $taskList = $this->createTideTaskList($tideContext);

        return Tide::create($taskList, $tideContext);
    }

    /**
     * @param TideEvent[] $events
     *
     * @return Tide
     */
    public function createFromEvents(array $events)
    {
        /** @var TideCreated[] $tideCreatedEvents */
        $tideCreatedEvents = array_values(array_filter($events, function (TideEvent $event) {
            return $event instanceof TideCreated;
        }));

        if (count($tideCreatedEvents) == 0) {
            throw new \RuntimeException('Can\'t recreate a tide from events without the created event');
        }

        $tideCreatedEvent = $tideCreatedEvents[0];
        $tideContext = $tideCreatedEvent->getTideContext();
        $taskList = $this->createTideTaskList($tideContext);

        return Tide::fromEvents($taskList, $events);
    }

    /**
     * @param TideContext $tideContext
     *
     * @throws Task\TaskFactoryNotFound
     *
     * @return TaskList
     */
    private function createTideTaskList(TideContext $tideContext)
    {
        $configuration = $tideContext->getConfiguration();
        $tasksConfiguration = array_key_exists('tasks', $configuration) ? $configuration['tasks'] : [];

        $tasks = [];
        foreach ($tasksConfiguration as $taskId => $taskConfig) {
            $taskName = array_keys($taskConfig)[0];
            $taskConfiguration = $taskConfig[$taskName];

            $taskFactory = $this->taskFactoryRegistry->find($taskName);
            $taskContext = TaskContext::createTaskContext(
                new ContextTree(ArrayContext::fromRaw($taskConfiguration ?: []), $tideContext),
                $taskId
            );

            $tasks[] = $taskFactory->create($taskContext);
        }

        return new TaskList($tasks);
    }
}
