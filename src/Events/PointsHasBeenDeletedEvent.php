<?php

namespace Pbmedia\Gamification\Events;

use Illuminate\Queue\SerializesModels;
use Pbmedia\Gamification\Models\PointsModel;

class PointsHasBeenDeletedEvent
{
    use SerializesModels;

    public $pointsModel;

    public function __construct(PointsModel $pointsModel)
    {
        $this->pointsModel = $pointsModel;
    }
}
