<?php

namespace Tests\Unit;

use App\Support\SiteManagerDashboardStatistics;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class SiteManagerDashboardStatisticsTest extends TestCase
{
    public function test_it_aggregates_only_visit_activity_and_ranks_quiz_attempts(): void
    {
        $now = Carbon::parse('2026-06-10 12:00:00');
        $records = [
            ['landmark_id' => 'a', 'activity_type' => 'landmark_view', 'visitor_key' => 'one', 'occurred_at' => '2026-06-10 09:00:00'],
            ['landmark_id' => 'a', 'activity_type' => 'qr_scan', 'visitor_key' => 'one', 'occurred_at' => '2026-06-09 09:00:00'],
            ['landmark_id' => 'b', 'activity_type' => 'landmark_view', 'visitor_key' => 'two', 'occurred_at' => '2026-05-10 09:00:00'],
            ['landmark_id' => 'a', 'activity_type' => 'quiz_attempt', 'visitor_key' => 'three', 'visitor_name' => 'Ana', 'quiz_score' => 8, 'quiz_total' => 10, 'occurred_at' => '2026-06-10 10:00:00'],
            ['landmark_id' => 'b', 'activity_type' => 'quiz_attempt', 'visitor_name' => 'Ben', 'quiz_score' => '9/10', 'occurred_at' => '2026-06-09 10:00:00'],
        ];

        $statistics = SiteManagerDashboardStatistics::fromRecords($records, ['a' => 'Alpha', 'b' => 'Beta'], $now);

        $this->assertSame(2, $statistics['total_visitors']);
        $this->assertSame(['a' => 1, 'b' => 1], $statistics['visitors_by_landmark']);
        $this->assertSame(1, $statistics['daily_visits']);
        $this->assertSame(2, $statistics['weekly_visits']);
        $this->assertSame(2, $statistics['monthly_visits']);
        $this->assertSame(3, $statistics['yearly_visits']);
        $this->assertSame(2, array_sum($statistics['charts']['daily']['values']));
        $this->assertSame('Ben', $statistics['leaderboard'][0]['visitor_name']);
        $this->assertSame('Beta', $statistics['leaderboard'][0]['landmark']);
        $this->assertSame('8/10', $statistics['leaderboard'][1]['score']);
        $this->assertSame(['all', 'a', 'b'], array_column($statistics['landmark_options'], 'id'));
        $this->assertSame(2, $statistics['analytics_by_landmark']['a']['totals']['weekly']);
        $this->assertSame(1, $statistics['analytics_by_landmark']['b']['totals']['yearly']);
        $this->assertSame('Alpha', $statistics['visitor_records'][0]['landmark']);
        $this->assertSame(2, $statistics['visitor_records'][0]['visit_count']);
        $this->assertSame('Ana', $statistics['leaderboard_by_landmark']['a'][0]['visitor_name']);
        $this->assertSame('Ben', $statistics['leaderboard_by_landmark']['b'][0]['visitor_name']);
    }

    public function test_it_ranks_by_highest_percentage_then_latest_completion(): void
    {
        $records = [
            ['landmark_id' => 'a', 'activity_type' => 'quiz_attempt', 'visitor_name' => 'Older', 'quiz_score' => 3999, 'score_percentage' => 67, 'occurred_at' => '2026-06-10 10:00:00'],
            ['landmark_id' => 'a', 'activity_type' => 'quiz_attempt', 'visitor_name' => 'Lower', 'quiz_score' => 5000, 'score_percentage' => 60, 'occurred_at' => '2026-06-10 12:00:00'],
            ['landmark_id' => 'a', 'activity_type' => 'quiz_attempt', 'visitor_name' => 'Latest', 'quiz_score' => 3999, 'score_percentage' => 67, 'occurred_at' => '2026-06-10 11:00:00'],
        ];

        $statistics = SiteManagerDashboardStatistics::fromRecords($records, ['a' => 'Alpha'], Carbon::parse('2026-06-10 12:00:00'));

        $this->assertSame(['Latest', 'Older', 'Lower'], array_column($statistics['leaderboard'], 'visitor_name'));
        $this->assertSame('67%', $statistics['leaderboard'][0]['score']);
        $this->assertSame('Today, 5:00 PM', $statistics['leaderboard'][0]['completed_at_label']);
        $this->assertSame(['Lower', 'Latest', 'Older'], array_column($statistics['leaderboard_by_landmark']['all'], 'visitor_name'));
        $this->assertSame(['5000', '3999', '3999'], array_column($statistics['leaderboard_by_landmark']['all'], 'total_score'));
    }

    public function test_it_groups_quiz_results_for_managed_landmarks(): void
    {
        $records = [
            [
                'activity_type' => 'quiz_attempt',
                'visitor_name' => 'Rena Olivo',
                'landmark_id' => '4aad73e1ea35411291b2',
                'landmark_name' => 'Osmena Peak',
                'quiz_score' => 5161,
                'quiz_total' => 10000,
                'score_percentage' => 52,
                'occurred_at' => '2026-06-30T04:11:39.320Z',
            ],
        ];

        $statistics = SiteManagerDashboardStatistics::fromRecords(
            $records,
            ['4aad73e1ea35411291b2' => 'Osmena Peak'],
            Carbon::parse('2026-06-30 12:00:00')
        );

        $this->assertSame('Rena Olivo', $statistics['leaderboard_by_landmark']['all'][0]['visitor_name']);
        $this->assertSame('Osmena Peak', $statistics['leaderboard_by_landmark']['4aad73e1ea35411291b2'][0]['landmark']);
        $this->assertSame('5161', $statistics['leaderboard_by_landmark']['all'][0]['total_score']);
        $this->assertSame('Today, 12:11 PM', $statistics['leaderboard_by_landmark']['all'][0]['completed_at_label']);
    }

    public function test_it_builds_visitor_records_from_landmark_activity_counts(): void
    {
        $records = [
            [
                'activity_type' => 'landmark_view',
                'visitor_key' => 'rena@example.com',
                'visitor_name' => 'Rena Olivo',
                'landmark_id' => '8966176d8de548c5b439',
                'landmark_name' => "Magellan's Cross",
                'visit_count' => 3,
                'occurred_at' => '2026-06-30T05:36:27.706Z',
            ],
        ];

        $statistics = SiteManagerDashboardStatistics::fromRecords(
            $records,
            ['8966176d8de548c5b439' => "Magellan's Cross"],
            Carbon::parse('2026-06-30 13:45:00')
        );

        $this->assertSame('Rena Olivo', $statistics['visitor_records'][0]['visitor_name']);
        $this->assertSame("Magellan's Cross", $statistics['visitor_records'][0]['landmark']);
        $this->assertSame(3, $statistics['visitor_records'][0]['visit_count']);
        $this->assertSame('Today, 1:36 PM', $statistics['visitor_records'][0]['last_visit_date']);
        $this->assertSame(3, $statistics['daily_visits']);
        $this->assertSame(3, $statistics['analytics_by_landmark']['8966176d8de548c5b439']['totals']['daily']);
    }

    public function test_total_visitors_uses_explicit_unique_visitor_user_count(): void
    {
        $records = [
            [
                'activity_type' => 'landmark_view',
                'visitor_key' => 'visitor-1',
                'visitor_name' => 'Rie',
                'landmark_id' => 'a',
                'visit_count' => 1,
                'visitor_profile_visit_count' => 7,
                'occurred_at' => '2026-07-02T08:35:53.034Z',
            ],
            [
                'activity_type' => 'landmark_view',
                'visitor_key' => 'visitor-1',
                'visitor_name' => 'Rie',
                'landmark_id' => 'a',
                'visit_count' => 1,
                'visitor_profile_visit_count' => 7,
                'occurred_at' => '2026-07-02T08:36:53.034Z',
            ],
        ];

        $statistics = SiteManagerDashboardStatistics::fromRecords($records, ['a' => 'Simala Shrine'], Carbon::parse('2026-07-02 17:00:00'), 1);

        $this->assertSame(1, $statistics['total_visitors']);
        $this->assertSame(2, $statistics['visitor_records'][0]['visit_count']);
        $this->assertSame('Rie', $statistics['visitor_records'][0]['visitor_name']);
        $this->assertSame('Simala Shrine', $statistics['visitor_records'][0]['landmark']);
        $this->assertSame('Today, 4:36 PM', $statistics['visitor_records'][0]['last_visit_date']);
    }

    public function test_it_formats_yesterday_and_older_table_dates_with_time(): void
    {
        $statistics = SiteManagerDashboardStatistics::fromRecords([
            [
                'activity_type' => 'landmark_view',
                'visitor_key' => 'visitor-1',
                'visitor_name' => 'Rie',
                'landmark_id' => 'a',
                'occurred_at' => '2026-07-01T14:45:00Z',
            ],
            [
                'activity_type' => 'quiz_attempt',
                'visitor_name' => 'Rie',
                'landmark_id' => 'a',
                'quiz_score' => 4143,
                'occurred_at' => '2026-06-30T14:45:00Z',
            ],
        ], ['a' => 'Simala Shrine'], Carbon::parse('2026-07-02 17:00:00'));

        $this->assertSame('Yesterday, 10:45 PM', $statistics['visitor_records'][0]['last_visit_date']);
        $this->assertSame('Jun 30, 2026, 10:45 PM', $statistics['leaderboard'][0]['completed_at_label']);
    }

    public function test_it_formats_firebase_utc_timestamps_in_manila_time(): void
    {
        $statistics = SiteManagerDashboardStatistics::fromRecords([
            [
                'activity_type' => 'landmark_view',
                'visitor_key' => 'visitor-1',
                'visitor_name' => 'Rie',
                'landmark_id' => 'a',
                'occurred_at' => '2026-07-02T08:33:53.034Z',
            ],
            [
                'activity_type' => 'quiz_attempt',
                'visitor_name' => 'Rie',
                'landmark_id' => 'a',
                'quiz_score' => 4143,
                'occurred_at' => '2026-07-02T08:30:59.955Z',
            ],
        ], ['a' => 'Simala Shrine'], Carbon::parse('2026-07-02T13:00:00Z'));

        $this->assertSame('Today, 4:33 PM', $statistics['visitor_records'][0]['last_visit_date']);
        $this->assertSame('Today, 4:30 PM', $statistics['leaderboard'][0]['completed_at_label']);
    }
}
