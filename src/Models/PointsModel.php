<?php

namespace Pbmedia\Gamification\Models;

use Illuminate\Database\Eloquent\Model;

class PointsModel extends Model
{
    protected $table = 'gamification_points';

    protected $fillable = [
        'earner_id',
        'earner_type',
        'rewarder_id',
        'rewarder_type',
        'item_id',
        'item_type',
        'points',
    ];

    protected $casts = [
        'points' => 'int',
    ];

    public function earner()
    {
        return $this->morphTo();
    }

    public function rewarder()
    {
        return $this->morphTo();
    }

    public function item()
    {
        return $this->morphTo();
    }
}
