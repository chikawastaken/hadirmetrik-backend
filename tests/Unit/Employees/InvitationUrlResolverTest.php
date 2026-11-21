<?php

namespace Tests\Unit\Employees;

use App\Modules\Employees\Infrastructure\Services\InvitationUrlResolver;
use Illuminate\Http\Request;
use Tests\TestCase;

class InvitationUrlResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'invitations.explicit_url' => null,
            'invitations.url' => 'http://localhost:8000/invitations',
        ]);
    }

    public function test_it_uses_the_host_the_client_actually_connected_to(): void
    {
        $request = Request::create('http://192.168.18.124:8000/api/v1/hr/employees', 'POST');

        $this->assertSame(
            'http://192.168.18.124:8000/invitations',
            (new InvitationUrlResolver)->base($request, []),
        );
    }

    public function test_an_explicit_url_wins_over_the_request_host(): void
    {
        config(['invitations.explicit_url' => 'https://app.hadirmetrik.site/invitations/']);

        $request = Request::create('http://192.168.18.124:8000/api/v1/hr/employees', 'POST');

        $this->assertSame(
            'https://app.hadirmetrik.site/invitations',
            (new InvitationUrlResolver)->base($request, []),
        );
    }

    public function test_a_loopback_request_uses_the_machines_lan_address(): void
    {
        $interfaces = [
            'lo0' => ['up' => true, 'unicast' => [['address' => '127.0.0.1']]],
            'en0' => ['up' => true, 'unicast' => [['address' => '192.168.18.124']]],
            'bridge0' => ['up' => true, 'unicast' => [['address' => '192.168.64.1']]],
        ];

        $request = Request::create('http://127.0.0.1:8000/api/v1/hr/employees', 'POST');

        $this->assertSame(
            'http://192.168.18.124:8000/invitations',
            (new InvitationUrlResolver)->base($request, $interfaces),
        );
    }

    public function test_it_falls_back_to_the_configured_url_without_a_lan_address(): void
    {
        $request = Request::create('http://127.0.0.1:8000/api/v1/hr/employees', 'POST');

        $this->assertSame(
            'http://localhost:8000/invitations',
            (new InvitationUrlResolver)->base($request, []),
        );
    }

    public function test_pick_address_skips_loopback_and_virtual_adapters(): void
    {
        $interfaces = [
            'lo0' => ['up' => true, 'unicast' => [['address' => '127.0.0.1']]],
            'docker0' => ['up' => true, 'unicast' => [['address' => '172.17.0.1']]],
            'bridge0' => ['up' => true, 'unicast' => [['address' => '192.168.64.1']]],
            'en0' => ['up' => true, 'unicast' => [['address' => '192.168.18.124']]],
        ];

        $this->assertSame('192.168.18.124', InvitationUrlResolver::pickAddress($interfaces));
    }

    public function test_pick_address_ignores_down_interfaces(): void
    {
        $interfaces = [
            'en0' => ['up' => false, 'unicast' => [['address' => '192.168.18.124']]],
            'wlan0' => ['up' => true, 'unicast' => [['address' => '10.0.0.7']]],
        ];

        $this->assertSame('10.0.0.7', InvitationUrlResolver::pickAddress($interfaces));
    }
}
