<?php

namespace Pbmedia\Gamification;

use Pbmedia\Gamification\Events\PointsHasBeenRewarderdEvent;
use Pbmedia\Gamification\Exceptions\EarnerIsNotFilledException;
use Pbmedia\Gamification\Exceptions\ItemIsNotFilledException;
use Pbmedia\Gamification\Exceptions\PointsAreNotFilledException;
use Pbmedia\Gamification\Exceptions\RewarderHasNotEnoughPointsException;
use Pbmedia\Gamification\Exceptions\RewarderIsNotFilledException;
use Pbmedia\Gamification\Interfaces\CanEarnPointsInterface;
use Pbmedia\Gamification\Interfaces\CanRewardPointsInterface;
use Pbmedia\Gamification\Interfaces\HasLimitedPointsToRewardInterface;
use Pbmedia\Gamification\Models\PointsModel;

class PointsService
{
    protected $earner;

    protected $rewarder;

    protected $item;

    protected $points;

    protected $pointsModel;

    public function __construct(PointsModel $pointsModel)
    {
        $this->pointsModel = $pointsModel;

        $this->setEarner($pointsModel->earner);
        $this->setRewarder($pointsModel->rewarder);
        $this->setItem($pointsModel->item);
        $this->setPoints($pointsModel->points);
    }

    public function setEarner(CanEarnPointsInterface $earner)
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

    public function checkIfModelAttributesAreFilled()
    {
        if (!$this->earner) {
            throw new EarnerIsNotFilledException;
        }

        if (!$this->rewarder) {
            throw new RewarderIsNotFilledException;
        }

        if (!$this->item) {
            throw new ItemIsNotFilledException;
        }

        if (!$this->points) {
            throw new PointsAreNotFilledException;
        }
    }

    public function save()
    {
        $this->checkIfModelAttributesAreFilled();

        if (!$this->checkIfRewarderHasEnoughPoints()) {
            throw new RewarderHasNotEnoughPointsException();
        }

        if (!$this->pointsModel) {
            $this->pointsModel = new PointsModel;
        }

        $this->pointsModel->fill([
            'earner_id'     => $this->earner->getKey(),
            'earner_type'   => $this->earner->getMorphClass(),
            'rewarder_id'   => $this->rewarder->getKey(),
            'rewarder_type' => $this->rewarder->getMorphClass(),
            'item_id'       => $this->item->getKey(),
            'item_type'     => $this->item->getMorphClass(),
            'points'        => $this->points,
        ]);

        if ($this->pointsModel->exists) {
            $this->pointsModel->update();
        } else {
            $this->pointsModel->save();
            Event::fire(new PointsHasBeenRewarderdEvent($pointsModel));
        }
    }

    public function find()
    {
        return $this->getQuery()->get();
    }

    protected function getQuery()
    {
        $query = PointsModel::getQuery();

        if ($this->earner) {
            $query->where('earner_id', $this->earner->getKey())
                ->where('earner_type', $this->earner->getMorphClass());
        }

        if ($this->rewarder) {
            $query->where('rewarder_id', $this->rewarder->getKey())
                ->where('rewarder_type', $this->rewarder->getMorphClass());
        }

        if ($this->item) {
            $query->where('item_id', $this->item->getKey())
                ->where('item_type', $this->item->getMorphClass());
        }

        if ($this->points) {
            $query->where('points', $this->points);
        }

        return $query->get();
    }

    public function delete()
    {
        return $this->find()->each(function ($pointsModel) {
            $pointsModel->delete();
            Event::fire(new PointsHasBeenDeletedEvent($pointsModel));
        });
    }

    public function deleteAndRollbackRewardersPoints()
    {
        return $this->delete()->each(function ($pointsModel) {
            $rewarder = $pointsModel->rewarder;

            if (!$rewarder instanceof HasLimitedPointsToRewardInterface) {
                return;
            }

            $rewarder->addPointsToReward($pointsModel->points);
        });
    }

    protected function rewarderHasEnoughPoints(): bool
    {
        if (!$this->rewarder instanceof HasLimitedPointsToRewardInterface) {
            return true;
        }

        return $this->rewarder->getTotalPointsToRewardLeft() >= $this->points;
    }

    public static function getTotalRewardedPointsByRewarder(CanRewardPointsInterface $rewarder)
    {
        $pointsService = new static;

        $query = $pointsService->setRewarder($rewarder)->getQuery();

        return $query->sum('points');
    }
}
