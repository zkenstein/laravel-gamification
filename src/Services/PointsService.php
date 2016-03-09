<?php

namespace Pbmedia\Gamification\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Pbmedia\Gamification\Events;
use Pbmedia\Gamification\Exceptions;
use Pbmedia\Gamification\Interfaces;
use Pbmedia\Gamification\Models\PointsModel;

class PointsService
{
    protected $earner;

    protected $rewarder;

    protected $item;

    protected $points;

    protected $pointsModel;

    public function __construct(PointsModel $pointsModel = null)
    {
        if ($pointsModel) {
            $this->pointsModel = $pointsModel;

            $this->setEarner($pointsModel->earner);
            $this->setRewarder($pointsModel->rewarder);
            $this->setItem($pointsModel->item);
            $this->setPoints($pointsModel->points);
        }
    }

    public function setEarner(Interfaces\CanEarnPointsInterface $earner)
    {
        $this->earner = $earner;

        return $this;
    }

    public function setRewarder(Interfaces\CanRewardPointsInterface $rewarder)
    {
        $this->rewarder = $rewarder;

        return $this;
    }

    public function setItem(Model $item)
    {
        $this->item = $item;

        return $this;
    }

    public function setPoints(int $points)
    {
        $this->points = $points;

        return $this;
    }

    public function getPointsModel()
    {
        return $this->pointsModel;
    }

    public function checkIfModelAttributesAreFilled()
    {
        if (!$this->earner) {
            throw new Exceptions\EarnerIsNotFilledException;
        }

        if (!$this->rewarder) {
            throw new Exceptions\RewarderIsNotFilledException;
        }

        if (!$this->item) {
            throw new Exceptions\ItemIsNotFilledException;
        }

        if (!$this->points) {
            throw new Exceptions\PointsAreNotFilledException;
        }
    }

    public function save()
    {
        $this->checkIfModelAttributesAreFilled();

        if (!$this->rewarderHasEnoughPoints()) {
            throw new Exceptions\RewarderHasNotEnoughPointsException;
        }

        $pointsToSubtractFromRewarder = $this->points;

        if (!$this->pointsModel) {
            $this->pointsModel = new PointsModel;
        } else {
            $pointsToSubtractFromRewarder += $this->pointsModel->points * -1;
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

        $this->pointsModel->save();
        $this->subtractPointsFromRewarder($pointsToSubtractFromRewarder);

        if ($this->pointsModel->wasRecentlyCreated) {
            Event::fire(new Events\PointsHasBeenRewarderdEvent($this->pointsModel));
        }

        $this->pointsModel = $this->pointsModel->fresh();

        return $this;
    }

    public function find(): Collection
    {
        return $this->getBuilder()->get();
    }

    public function getTotalPoints(): int
    {
        return $this->getBuilder()->sum('points');
    }

    protected function getBuilder(): Builder
    {
        $builder = PointsModel::query();

        if ($this->earner) {
            $builder->where('earner_id', $this->earner->getKey())
                ->where('earner_type', $this->earner->getMorphClass());
        }

        if ($this->rewarder) {
            $builder->where('rewarder_id', $this->rewarder->getKey())
                ->where('rewarder_type', $this->rewarder->getMorphClass());
        }

        if ($this->item) {
            $builder->where('item_id', $this->item->getKey())
                ->where('item_type', $this->item->getMorphClass());
        }

        if ($this->points) {
            $builder->where('points', $this->points);
        }

        return $builder;
    }

    public function delete(): Collection
    {
        return $this->find()->each(function ($pointsModel) {
            $rewarder = $pointsModel->rewarder;

            $pointsModel->delete();

            if ($rewarder instanceof Interfaces\HasLimitedPointsToRewardInterface) {
                $rewarder->addPointsToReward($pointsModel->points);
            }

            Event::fire(new Events\PointsHasBeenDeletedEvent($pointsModel));
        });
    }

    protected function rewarderHasEnoughPoints(): bool
    {
        if (!$this->rewarder instanceof Interfaces\HasLimitedPointsToRewardInterface) {
            return true;
        }

        return $this->rewarder->getTotalPointsToRewardLeft() >= $this->points;
    }

    protected function subtractPointsFromRewarder(int $pointsToSubtract): bool
    {
        if (!$this->rewarder instanceof Interfaces\HasLimitedPointsToRewardInterface) {
            return true;
        }

        return $this->rewarder->addPointsToReward($pointsToSubtract * -1);
    }

    public static function getTotalEarnedPointsByEarner(Interfaces\CanEarnPointsInterface $earned): int
    {
        return (new static )->setEarner($earned)->getTotalPoints();
    }

    public static function getTotalEarnedPointsByItem(Model $item): int
    {
        return (new static )->setItem($item)->getTotalPoints();
    }

    public static function getTotalRewardedPointsByRewarder(Interfaces\CanRewardPointsInterface $rewarder): int
    {
        return (new static )->setRewarder($rewarder)->getTotalPoints();
    }
}
