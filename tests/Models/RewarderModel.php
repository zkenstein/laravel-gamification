<?php

namespace Pbmedia\Gamification\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Pbmedia\Gamification\Interfaces\CanRewardPointsInterface;

class RewarderModel extends Model implements CanRewardPointsInterface
{
    protected $table = 'rewarders';
}
