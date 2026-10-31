<?php

namespace Tests\Feature;

use Tests\TestCase;

class InvitationLandingTest extends TestCase
{
    public function test_it_renders_the_app_bridge_for_a_well_formed_token(): void
    {
        $token = str_repeat('a', 64);

        $response = $this->get("/invitations/{$token}");

        $response->assertOk();
        $response->assertHeader('Referrer-Policy', 'no-referrer');
        $response->assertSee('noindex', false);
        $response->assertSee("hadirmetrik://invitations/{$token}", false);
        $response->assertSee("intent://invitations/{$token}#Intent;scheme=hadirmetrik;package=com.hadirmetrik.hadirmetrik;end", false);
    }

    public function test_it_rejects_a_malformed_token(): void
    {
        $this->get('/invitations/short')->assertNotFound();
    }
}
