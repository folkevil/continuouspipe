<?php

namespace ContinuousPipe\River\View\Pagination;

use ContinuousPipe\River\Infrastructure\Doctrine\Repository\View\DoctrineTideList;
use ContinuousPipe\River\Tests\View\InMemoryTideList;
use ContinuousPipe\River\View\TideList;
use Knp\Component\Pager\Event\ItemsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TideListPaginatorSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'knp_pager.items' => [
                'items',
                1
            ]
        ];
    }

    /**
     * @param ItemsEvent $event
     */
    public function items(ItemsEvent $event)
    {
        $list = $event->target;
        if (!$list instanceof TideList) {
            return;
        }

        if ($list instanceof DoctrineTideList) {
            $list
                ->getQueryBuilder()
                ->setFirstResult($event->getOffset())
                ->setMaxResults($event->getLimit())
            ;
        } else if ($list instanceof InMemoryTideList) {
            $list = new InMemoryTideList(array_slice(
                $list->toArray(),
                $event->getOffset(),
                $event->getLimit()
            ));
        }

        $tides = $list->toArray();
        $event->count = count($tides);
        $event->items = $tides;

        $event->stopPropagation();
    }
}