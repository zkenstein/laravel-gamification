<?php

namespace Pbmedia\Gamification\Interfaces;

interface HasLimitedPointsToRewardInterface
{
    public function getTotalPointsToRewardLeft(): int;

    public function addPointsToReward(int $points): bool;
}
