<?php

namespace Tests\Unit;

use App\Services\IraqiPhone;
use PHPUnit\Framework\TestCase;

class IraqiPhoneTest extends TestCase
{
    public function test_accepts_local_number_with_and_without_the_leading_trunk_zero(): void
    {
        $this->assertTrue(IraqiPhone::isValidLocal('7701234567'));
        $this->assertTrue(IraqiPhone::isValidLocal('07701234567'));
    }

    public function test_rejects_the_wrong_length_either_way(): void
    {
        $this->assertFalse(IraqiPhone::isValidLocal('770123456')); // 9 digits
        $this->assertFalse(IraqiPhone::isValidLocal('077012345678')); // 12 digits
        $this->assertFalse(IraqiPhone::isValidLocal('0123456789')); // not 07...
        $this->assertFalse(IraqiPhone::isValidLocal(''));
    }

    public function test_to_e164_strips_the_trunk_zero_before_the_country_code(): void
    {
        $this->assertSame('+9647701234567', IraqiPhone::toE164('7701234567'));
        $this->assertSame('+9647701234567', IraqiPhone::toE164('07701234567'));
    }

    public function test_normalize_turns_a_local_number_with_or_without_the_zero_into_plus964(): void
    {
        $this->assertSame('+9647701234567', IraqiPhone::normalize('7701234567'));
        $this->assertSame('+9647701234567', IraqiPhone::normalize('07701234567'));
    }

    public function test_normalize_leaves_a_number_already_written_with_a_country_code_alone(): void
    {
        $this->assertSame('+9647701234567', IraqiPhone::normalize('+9647701234567'));
        $this->assertSame('+9647701234567', IraqiPhone::normalize('9647701234567'));
    }

    public function test_normalize_rejects_nonsense(): void
    {
        $this->assertNull(IraqiPhone::normalize(''));
        $this->assertNull(IraqiPhone::normalize('abc'));
        $this->assertNull(IraqiPhone::normalize('123'));
    }
}
