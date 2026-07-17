<?php

namespace Tests\Unit;

use App\Support\LandmarkApprovalOrder;
use DateTimeImmutable;
use Google\Cloud\Core\Timestamp;
use PHPUnit\Framework\TestCase;

class LandmarkApprovalOrderTest extends TestCase
{
    public function test_all_statuses_are_grouped_and_landmark_names_sort_alphabetically(): void
    {
        $landmarks = [
            ['id' => 'approved-z', 'name' => 'Zeta Active', 'activation_status' => 'active', 'submitted_at' => '2026-06-10 12:00:00'],
            ['id' => 'pending-b', 'name' => 'Beta Pending', 'activation_status' => 'pending', 'submitted_at' => '2026-06-08 12:00:00'],
            ['id' => 'rejected-a', 'name' => 'Alpha Rejected', 'activation_status' => 'rejected', 'submitted_at' => '2026-06-11 12:00:00'],
            ['id' => 'pending-a', 'name' => 'Alpha Pending', 'activation_status' => 'pending', 'submitted_at' => '2026-06-09 12:00:00'],
            ['id' => 'approved-b', 'name' => 'Beta Active', 'activation_status' => 'active', 'submitted_at' => '2026-06-07 12:00:00'],
            ['id' => 'approved-a', 'name' => 'Alpha Active', 'created_at' => '2026-06-06 12:00:00'],
        ];

        usort($landmarks, fn (array $left, array $right) => LandmarkApprovalOrder::compare(
            $left,
            $left['id'],
            $right,
            $right['id'],
            true
        ));

        $this->assertSame([
            'pending-a',
            'pending-b',
            'approved-a',
            'approved-b',
            'approved-z',
            'rejected-a',
        ], array_column($landmarks, 'id'));
    }

    public function test_specific_status_sort_uses_landmark_name_first(): void
    {
        $landmarks = [
            ['id' => 'zulu', 'name' => 'Zulu', 'created_at' => '2026-06-10 12:00:00'],
            ['id' => 'alpha', 'name' => 'Alpha', 'created_at' => '2026-06-08 12:00:00'],
            ['id' => 'mango', 'name' => 'Mango', 'created_at' => '2026-06-09 12:00:00'],
        ];

        usort($landmarks, fn (array $left, array $right) => LandmarkApprovalOrder::compare(
            $left,
            $left['id'],
            $right,
            $right['id'],
            false
        ));

        $this->assertSame(['alpha', 'mango', 'zulu'], array_column($landmarks, 'id'));
    }

    public function test_site_manager_landmarks_put_active_pending_rejected_in_order_and_sort_each_group_by_name(): void
    {
        $landmarks = [
            ['id' => 'active-z', 'name' => 'Zeta Active', 'activation_status' => 'active', 'created_at' => '2026-06-10 12:00:00'],
            ['id' => 'pending-b', 'name' => 'Beta Pending', 'activation_status' => 'pending', 'created_at' => '2026-06-08 12:00:00'],
            ['id' => 'rejected-b', 'name' => 'Beta Rejected', 'activation_status' => 'rejected', 'created_at' => '2026-06-11 12:00:00'],
            ['id' => 'active-a', 'name' => 'Alpha Active', 'activation_status' => 'active', 'created_at' => '2026-06-07 12:00:00'],
            ['id' => 'rejected-a', 'name' => 'Alpha Rejected', 'activation_status' => 'rejected', 'created_at' => '2026-06-12 12:00:00'],
            ['id' => 'pending-a', 'name' => 'Alpha Pending', 'activation_status' => 'pending', 'created_at' => '2026-06-09 12:00:00'],
        ];

        usort($landmarks, fn (array $left, array $right) => LandmarkApprovalOrder::comparePortfolioStatusThenName(
            $left,
            $left['id'],
            $right,
            $right['id']
        ));

        $this->assertSame([
            'active-a',
            'active-z',
            'pending-a',
            'pending-b',
            'rejected-a',
            'rejected-b',
        ], array_column($landmarks, 'id'));
    }

    public function test_site_manager_landmarks_keep_status_priority_when_sorting_names_descending(): void
    {
        $landmarks = [
            ['id' => 'pending-a', 'name' => 'Alpha Pending', 'activation_status' => 'pending'],
            ['id' => 'active-a', 'name' => 'Alpha Active', 'activation_status' => 'active'],
            ['id' => 'rejected-a', 'name' => 'Alpha Rejected', 'activation_status' => 'rejected'],
            ['id' => 'pending-b', 'name' => 'Beta Pending', 'activation_status' => 'pending'],
            ['id' => 'active-z', 'name' => 'Zeta Active', 'activation_status' => 'active'],
            ['id' => 'rejected-b', 'name' => 'Beta Rejected', 'activation_status' => 'rejected'],
        ];

        usort($landmarks, fn (array $left, array $right) => LandmarkApprovalOrder::comparePortfolioStatusThenName(
            $left,
            $left['id'],
            $right,
            $right['id'],
            'desc'
        ));

        $this->assertSame([
            'active-z',
            'active-a',
            'pending-b',
            'pending-a',
            'rejected-b',
            'rejected-a',
        ], array_column($landmarks, 'id'));
    }

    public function test_firestore_timestamps_break_matching_name_ties_newest_first(): void
    {
        $landmarks = [
            ['id' => 'old', 'name' => 'Same Name', 'submitted_at' => new Timestamp(new DateTimeImmutable('2026-06-08 12:00:00'))],
            ['id' => 'new', 'name' => 'Same Name', 'submitted_at' => new Timestamp(new DateTimeImmutable('2026-06-10 12:00:00'))],
        ];

        usort($landmarks, fn (array $left, array $right) => LandmarkApprovalOrder::compare(
            $left,
            $left['id'],
            $right,
            $right['id'],
            false
        ));

        $this->assertSame(['new', 'old'], array_column($landmarks, 'id'));
    }
}
