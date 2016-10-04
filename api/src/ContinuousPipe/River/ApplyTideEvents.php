<?php

namespace ContinuousPipe\River;

use ContinuousPipe\River\Event\TideEvent;
use ContinuousPipe\River\Repository\TideRepository;
use SimpleBus\Message\Bus\MessageBus;

class ApplyTideEvents implements TideSaga
{
    /**
     * @var MessageBus
     */
    private $eventBus;

    /**
     * @var TideRepository
     */
    private $tideRepository;

    /**
     * @param MessageBus     $eventBus
     * @param TideRepository $tideRepository
     */
    public function __construct(MessageBus $eventBus, TideRepository $tideRepository)
    {
        $this->eventBus = $eventBus;
        $this->tideRepository = $tideRepository;
    }

    /**
     * A new tide-related event is received, we'll apply it to the tide and then
     * dispatch newly created events if there's any.
     *
     * @param TideEvent $event
     */
    public function notify(TideEvent $event)
    {
        $tide = $this->tideRepository->find($event->getTideUuid());
        $tide->apply($event);

        $events = $tide->popNewEvents();
        foreach ($events as $newEvent) {
            $this->eventBus->handle($newEvent);
        }
    }
}
