<?php

namespace Pbmedia\Gamification;

class RewardPointsService
{
    protected $earner;

    protected $rewarder;

    protected $item;

    protected $points;

    public function setEarner(CanRewardPointsInterface $earner)
    {
        $this->earner = $earner;

        return $this;
    }

    public function setRewarder(CanRewardPointsInterface $rewarder)
    {
        $this->rewarder = $rewarder;

        return $this;
    }

    public function setItem($item)
    {
        $this->item = $item;

        return $this;
    }

    public function setPoints(int $points)
    {
        $this->points = $points;

        return $this;
    }

    public function apply()
    {
        if (!$this->checkIfRewarderHasEnoughPoints()) {
            throw new RewarderHasNotEnoughPointsException();
        }

        $rewardModel = RewardModel::create([
            'earner_id'     => $this->earner->getKey(),
            'earner_type'   => $this->earner->getMorphClass(),
            'rewarder_id'   => $this->rewarder->getKey(),
            'rewarder_type' => $this->rewarder->getMorphClass(),
            'item_id'       => $this->item->getKey(),
            'item_type'     => $this->item->getMorphClass(),
            'points'        => $this->points,
        ]);

        Event::fire(new PointsHasBeenRewarderdEvent($rewardModel));
    }

    protected function rewarderHasEnoughPoints(): bool
    {
        if (!$this->rewarder instanceof HasLimitedPointsToRewardInterface) {
            return true;
        }

        return $this->rewarder->getTotalPointsToRewardLeft() >= $this->points;
    }
}
