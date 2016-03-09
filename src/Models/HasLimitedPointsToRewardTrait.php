<?php

namespace Pbmedia\Gamification\Models;

trait HasLimitedPointsToRewardTrait
{
    public function getTotalPointsToRewardLeft(): int
    {
        return (int) $this->points_left;
    }

    public function addPointsToReward(int $points): bool
    {
        $this->points_left = (int) $this->points_left + $points;

        return $this->save();
    }
}
