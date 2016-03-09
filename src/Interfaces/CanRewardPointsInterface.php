<?php

namespace Pbmedia\Gamification\Interfaces;

interface CanRewardPointsInterface
{
    public function rewardPointsToModel(CanEarnPointsInterface $model);

    public function getTotalRewardedPoints(): int;
}
