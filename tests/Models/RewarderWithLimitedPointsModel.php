<?php

namespace Pbmedia\Gamification\Tests\Models;

use Pbmedia\Gamification\Interfaces\HasLimitedPointsToRewardInterface;

class RewarderWithLimitedPointsModel extends RewarderModel implements HasLimitedPointsToRewardInterface
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
