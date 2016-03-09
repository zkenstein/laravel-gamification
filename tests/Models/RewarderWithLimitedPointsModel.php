<?php

namespace Pbmedia\Gamification\Tests\Models;

use Pbmedia\Gamification\Interfaces\HasLimitedPointsToRewardInterface;
use Pbmedia\Gamification\Models\HasLimitedPointsToRewardTrait;

class RewarderWithLimitedPointsModel extends RewarderModel implements HasLimitedPointsToRewardInterface
{
    use HasLimitedPointsToRewardTrait;
}
