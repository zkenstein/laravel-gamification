<?php

namespace Pbmedia\Gamification;

interface CanRewardPointsInterface
{
    public function rewardPointsToModel(CanEarnPointsInterface $model);

    public function getTotalRewardedPoints(): int;
}
