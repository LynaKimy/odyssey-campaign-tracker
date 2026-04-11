<?php

namespace Tests\Unit\Enums;

use App\Enums\SessionStatus;
use PHPUnit\Framework\TestCase;

class SessionStatusTest extends TestCase
{
    public function test_enum_has_expected_cases(): void
    {
        $cases = SessionStatus::cases();

        $this->assertCount(3, $cases);
        $this->assertContains(SessionStatus::Planned, $cases);
        $this->assertContains(SessionStatus::Played, $cases);
        $this->assertContains(SessionStatus::Skipped, $cases);
    }

    public function test_enum_values_are_correct(): void
    {
        $this->assertSame('planned', SessionStatus::Planned->value);
        $this->assertSame('played', SessionStatus::Played->value);
        $this->assertSame('skipped', SessionStatus::Skipped->value);
    }

    public function test_enum_can_be_created_from_value(): void
    {
        $this->assertSame(SessionStatus::Planned, SessionStatus::from('planned'));
        $this->assertSame(SessionStatus::Played, SessionStatus::from('played'));
        $this->assertSame(SessionStatus::Skipped, SessionStatus::from('skipped'));
    }

    public function test_invalid_value_throws_exception(): void
    {
        $this->expectException(\ValueError::class);
        SessionStatus::from('invalid');
    }

    public function test_try_from_returns_null_for_invalid_value(): void
    {
        $this->assertNull(SessionStatus::tryFrom('invalid'));
    }
}
