<?php

use Orchestra\Testbench\TestCase;

class PointsServiceTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function setUpDatabase()
    {
        include_once __DIR__ . '/../resources/migrations/create_gamification_points_table.php.stub';
        include_once __DIR__ . '/create_earners_table.php.stub';
        include_once __DIR__ . '/create_items_table.php.stub';
        include_once __DIR__ . '/create_rewarders_table.php.stub';

        (new \CreateGamificationPointsTable)->up();
        (new \CreateEarnersTable)->up();
        (new \CreateRewardersTable)->up();
        (new \CreateItemsTable)->up();
    }

    public function a_rewarder_can_give_points_to_a_earner_with_an_item()
    {
        $rewarder = factory();
    }
}
