<?php

namespace Pbmedia\Gamification;

interface HasLimitedPointsToRewardInterface
{
    public function getTotalPointsToRewardLeft(): int;
}
