<?php

namespace Tests\Feature;

use Tests\TestCase;

class SessionConfigTest extends TestCase
{
    public function test_session_defaults_use_env_values(): void
    {
        $this->assertFalse(config('session.secure'));
        $this->assertSame('lax', config('session.same_site'));
    }
}
