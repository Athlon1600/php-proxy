<?php

namespace Proxy\Tests;

use Proxy\Http\Response;

class ResponseTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_returns_status_texts()
    {
        $response = new Response('', 200);

        $this->assertEquals('OK', $response->getStatusText());
    }

    /** @test */
    public function it_returns_status_texts_for_unknown_status_codes()
    {
        $response = new Response('', 199);

        $this->assertEquals('Informational', $response->getStatusText());
    }

    /** @test */
    public function it_returns_default_status_text_for_uncommon_status_codes()
    {
        $response = new Response('', 601);

        $this->assertEquals('Unknown', $response->getStatusText());
    }
}
