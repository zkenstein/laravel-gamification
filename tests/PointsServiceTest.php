<?php

use Orchestra\Testbench\TestCase;

class PointsServiceTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        app(CreateEarnersTable::class)->up();
        app(CreateRewardersTable::class)->up();
        app(CreateItemsTable::class)->up();
        app(CreateGamificationPointsTable::class)->up();
    }

    public function a_rewarder_can_give_points_to_a_earner_with_an_item()
    {
        $rewarder = factory();
    }
}
