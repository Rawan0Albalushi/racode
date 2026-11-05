<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_health_endpoint_responds(): void
    {
        $this->get('/up')->assertOk();
    }
}
