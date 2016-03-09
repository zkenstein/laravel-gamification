<?php

use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\TestCase;
use Pbmedia\Gamification\Models\PointsModel;
use Pbmedia\Gamification\Services\PointsService;
use Pbmedia\Gamification\Tests\Models\EarnerModel;
use Pbmedia\Gamification\Tests\Models\ItemModel;
use Pbmedia\Gamification\Tests\Models\RewarderModel;
use Pbmedia\Gamification\Tests\Models\RewarderWithLimitedPointsModel;

class PointsServiceTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function setUpDatabase()
    {
        $this->app['config']->set('database.default', 'sqlite');
        $this->app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        include_once __DIR__ . '/../resources/migrations/create_gamification_points_table.php.stub';
        include_once __DIR__ . '/create_earners_table.php.stub';
        include_once __DIR__ . '/create_items_table.php.stub';
        include_once __DIR__ . '/create_rewarders_table.php.stub';

        (new \CreateGamificationPointsTable)->up();
        (new \CreateEarnersTable)->up();
        (new \CreateRewardersTable)->up();
        (new \CreateItemsTable)->up();
    }

    protected function createEarner()
    {
        return EarnerModel::create();
    }

    protected function createItem()
    {
        return ItemModel::create();
    }

    protected function createRewarder()
    {
        return RewarderModel::create();
    }

    protected function createRewarderWithLimitedPoints()
    {
        return RewarderWithLimitedPointsModel::create();
    }

    public function testRewarderGivesPointsToEarnerWithItem()
    {
        Event::shouldReceive('fire')->once();

        $pointsService = (new PointsService)->setEarner($earner = $this->createEarner())
            ->setItem($item = $this->createItem())
            ->setRewarder($rewarder = $this->createRewarder())
            ->setPoints($points = rand(1, 100))
            ->save();

        $pointsModel = $pointsService->getPointsModel();

        $this->assertEquals($pointsModel->earner, $earner->fresh());
        $this->assertEquals($pointsModel->rewarder, $rewarder->fresh());
        $this->assertEquals($pointsModel->item, $item->fresh());
        $this->assertEquals($pointsModel->points, $points);

        // change the points
        $pointsService->setPoints($points = rand(101, 200))->save();
        $this->assertEquals(PointsModel::first()->points, $points);
    }

    public function testPointsCanBeDeleted()
    {
        Event::shouldReceive('fire')->once();

        $pointsService = (new PointsService)->setEarner($earner = $this->createEarner())
            ->setItem($item = $this->createItem())
            ->setRewarder($rewarder = $this->createRewarder())
            ->setPoints($points = rand(1, 100))
            ->save();

        Event::shouldReceive('fire')->once();
        $pointsService->delete();

        $this->assertEmpty(PointsModel::all());
    }

    /**
     * @expectedException Pbmedia\Gamification\Exceptions\RewarderHasNotEnoughPointsException
     */
    public function testRewarderHasNotEnoughPoints()
    {
        $pointsService = (new PointsService)->setEarner($earner = $this->createEarner())
            ->setItem($item = $this->createItem())
            ->setRewarder($rewarder = $this->createRewarderWithLimitedPoints())
            ->setPoints($points = rand(1, 100))
            ->save();
    }

    public function testRewarderHasEnoughPoints()
    {
        $rewarder = $this->createRewarderWithLimitedPoints();
        $rewarder->addPointsToReward(200);

        $pointsService = (new PointsService)->setEarner($earner = $this->createEarner())
            ->setItem($item = $this->createItem())
            ->setRewarder($rewarder)
            ->setPoints($points = 50)
            ->save();

        $this->assertEquals($rewarder->getTotalPointsToRewardLeft(), 150);

        $pointsService->setPoints(75)->save();
        $this->assertEquals($rewarder->fresh()->getTotalPointsToRewardLeft(), 125);

        $pointsService->setPoints(25)->save();
        $this->assertEquals($rewarder->fresh()->getTotalPointsToRewardLeft(), 175);
    }

    public function testRewarderGetsPointsBackAfterDeletion()
    {
        $rewarder = $this->createRewarderWithLimitedPoints();
        $rewarder->addPointsToReward(200);

        $pointsService = (new PointsService)->setEarner($earner = $this->createEarner())
            ->setItem($item = $this->createItem())
            ->setRewarder($rewarder)
            ->setPoints($points = 50)
            ->save();

        $this->assertEquals($rewarder->getTotalPointsToRewardLeft(), 150);

        $pointsService->delete();

        $this->assertEquals($rewarder->fresh()->getTotalPointsToRewardLeft(), 200);
    }

    public function testTotalPointsEarned()
    {
        $pointsService = (new PointsService)->setEarner($earner = $this->createEarner())
            ->setItem($item = $this->createItem())
            ->setRewarder($rewarder = $this->createRewarder())
            ->setPoints($pointsA = rand(1, 100))
            ->save();

        $pointsService = (new PointsService)->setEarner($earner)
            ->setItem($item = $this->createItem())
            ->setRewarder($rewarder = $this->createRewarder())
            ->setPoints($pointsB = rand(1, 100))
            ->save();

        $this->assertEquals(PointsService::getTotalEarnedPointsByEarner($earner), $pointsA + $pointsB);
    }

    public function testTotalPointsItem()
    {
        $pointsService = (new PointsService)->setEarner($earner = $this->createEarner())
            ->setItem($item = $this->createItem())
            ->setRewarder($rewarder = $this->createRewarder())
            ->setPoints($pointsA = rand(1, 100))
            ->save();

        $pointsService = (new PointsService)->setEarner($earner = $this->createEarner())
            ->setItem($item)
            ->setRewarder($rewarder = $this->createRewarder())
            ->setPoints($pointsB = rand(1, 100))
            ->save();

        $this->assertEquals(PointsService::getTotalEarnedPointsByItem($item), $pointsA + $pointsB);
    }

    public function testTotalPointsRewarded()
    {
        $pointsService = (new PointsService)->setEarner($earner = $this->createEarner())
            ->setItem($item = $this->createItem())
            ->setRewarder($rewarder = $this->createRewarder())
            ->setPoints($pointsA = rand(1, 100))
            ->save();

        $pointsService = (new PointsService)->setEarner($earner = $this->createEarner())
            ->setItem($item = $this->createItem())
            ->setRewarder($rewarder)
            ->setPoints($pointsB = rand(1, 100))
            ->save();

        $this->assertEquals(PointsService::getTotalRewardedPointsByRewarder($rewarder), $pointsA + $pointsB);
    }
}
