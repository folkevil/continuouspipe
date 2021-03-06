<?php

namespace ContinuousPipe\River\Flow\Projections;

use ContinuousPipe\River\CodeRepository;
use ContinuousPipe\River\Flex\FlexConfiguration;
use ContinuousPipe\River\Flow;
use ContinuousPipe\River\View\Tide;
use ContinuousPipe\Security\Team\Team;
use ContinuousPipe\Security\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Yaml\Yaml;
use JMS\Serializer\Annotation as JMS;

class FlatFlow
{
    /**
     * @JMS\Groups({"Default"})
     * @JMS\Type("uuid")
     *
     * @var Uuid
     */
    private $uuid;

    /**
     * @JMS\Groups({"Default"})
     * @JMS\Type("ContinuousPipe\Security\Team\Team")
     *
     * @var Team
     */
    private $team;

    /**
     * @JMS\Groups({"Default"})
     * @JMS\Type("ContinuousPipe\Security\User\User")
     *
     * @var User
     */
    private $user;

    /**
     * @JMS\Groups({"Default"})
     * @JMS\Type("array")
     *
     * @var array
     */
    private $configuration;

    /**
     * @JMS\Groups({"Default"})
     * @JMS\Type("array<ContinuousPipe\River\Flow\Projections\FlatPipeline>")
     *
     * @var Collection|FlatPipeline[]
     */
    private $pipelines;

    /**
     * @JMS\Groups({"Default"})
     * @JMS\Type("array<ContinuousPipe\River\View\Tide>")
     *
     * @var Tide[]
     */
    private $tides;

    /**
     * @JMS\Groups({"Default"})
     * @JMS\Type("ContinuousPipe\River\CodeRepository")
     *
     * @var CodeRepository|null
     */
    private $repository;

    /**
     * @JMS\Groups({"Default"})
     * @JMS\Type("array<string>")
     *
     * @var string[]
     */
    private $pinnedBranches;

    /**
     * @JMS\Groups({"Default"})
     * @JMS\Type("ContinuousPipe\River\Flex\FlexConfiguration")
     * @JMS\Accessor(getter="getFlexConfiguration")
     *
     * @var FlexConfiguration
     */
    private $flexConfiguration;

    public function __construct()
    {
        $this->pipelines = new ArrayCollection();
    }

    /**
     * @param Flow $flow
     *
     * @return FlatFlow
     */
    public static function fromFlow(Flow $flow)
    {
        $view = new self();
        $view->uuid = $flow->getUuid();
        $view->team = $flow->getTeam();
        $view->configuration = $flow->getConfiguration() ?: [];
        $view->ymlConfiguration = Yaml::dump($view->configuration);
        $view->user = $flow->getUser();
        $view->pipelines = new ArrayCollection($flow->getPipelines());
        $view->repository = $flow->getCodeRepository();
        $view->pinnedBranches = $flow->getPinnedBranches();
        $view->flexConfiguration = $flow->getFlexConfiguration();

        return $view;
    }

    /**
     * @param FlatFlow $flow
     * @param Tide[]   $tides
     *
     * @return FlatFlow
     */
    public static function fromFlowAndTides(FlatFlow $flow, array $tides)
    {
        $view = clone $flow;
        $view->tides = $tides;

        return $view;
    }

    public function getUuid() : UuidInterface
    {
        return $this->uuid;
    }

    public function getRepository()
    {
        return $this->repository;
    }

    public function getTeam(): Team
    {
        return $this->team;
    }

    public function getConfiguration() : array
    {
        return $this->configuration;
    }

    public function getUser() : User
    {
        return $this->user;
    }

    public function isBranchPinned(CodeRepository\Branch $branch)
    {
        return in_array((string) $branch, $this->pinnedBranches);
    }

    /**
     * @return FlatPipeline[]|Collection
     */
    public function getPipelines()
    {
        return $this->pipelines;
    }

    public function isFlex()
    {
        return $this->hasFlexConfiguration();
    }

    /**
     * @return FlexConfiguration|null
     */
    public function getFlexConfiguration()
    {
        return $this->hasFlexConfiguration() ? $this->flexConfiguration : null;
    }

    private function hasFlexConfiguration() : bool
    {
        return $this->flexConfiguration != null && $this->flexConfiguration->getSmallIdentifier() !== null;
    }
}
