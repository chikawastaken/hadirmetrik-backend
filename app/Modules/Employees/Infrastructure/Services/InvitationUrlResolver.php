<?php

namespace App\Modules\Employees\Infrastructure\Services;

use Illuminate\Http\Request;

/**
 * Builds the base URL for the invitation bridge page.
 *
 * Development hosts change (campus Wi-Fi, tethering, a friend's network), so
 * the link follows the address the client actually used instead of a value
 * frozen into .env. Loopback requests (adb reverse, artisan commands) are
 * replaced with this machine's LAN IPv4 so the link also works on another
 * device on the same network.
 */
final class InvitationUrlResolver
{
    private const LOOPBACK = ['127.0.0.1', '::1', 'localhost'];

    /**
     * @param  array<string, array<string, mixed>>|null  $interfaces  Override for tests.
     */
    public function base(?Request $request = null, ?array $interfaces = null): string
    {
        $explicit = config('invitations.explicit_url');
        if (is_string($explicit) && $explicit !== '') {
            return rtrim($explicit, '/');
        }

        $request ??= request();
        $host = $request->getHost();

        if (! $this->isLoopback($host)) {
            return rtrim($request->getSchemeAndHttpHost(), '/').'/invitations';
        }

        $lan = self::pickAddress($interfaces ?? self::interfaces());
        if ($lan !== null) {
            return $this->withHost($request, $lan).'/invitations';
        }

        return rtrim((string) config('invitations.url'), '/');
    }

    private function withHost(Request $request, string $host): string
    {
        $port = $request->getPort();
        $suffix = in_array($port, [80, 443], true) ? '' : ':'.$port;

        return $request->getScheme().'://'.$host.$suffix;
    }

    private function isLoopback(string $host): bool
    {
        return in_array(strtolower($host), self::LOOPBACK, true)
            || str_starts_with($host, '127.');
    }

    /** @return array<string, array<string, mixed>> */
    private static function interfaces(): array
    {
        return function_exists('net_get_interfaces') ? (net_get_interfaces() ?: []) : [];
    }

    /**
     * Picks a usable LAN IPv4. Virtual adapters (Docker, VPN, emulator
     * bridges) are skipped so the link points at an address other devices on
     * the same Wi-Fi can reach.
     *
     * @param  array<string, array<string, mixed>>  $interfaces
     */
    public static function pickAddress(array $interfaces): ?string
    {
        $candidates = [];

        foreach ($interfaces as $name => $iface) {
            if (($iface['up'] ?? false) !== true) {
                continue;
            }

            if (preg_match('/^(lo|docker|br-|veth|virbr|vmnet|bridge|utun|tun|tap|wg|tailscale|anpi|awdl|llw|ap\d|p2p|vboxnet|gif|stf)/i', $name) === 1) {
                continue;
            }

            foreach ($iface['unicast'] ?? [] as $entry) {
                $ip = $entry['address'] ?? null;

                if (! is_string($ip) || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
                    continue;
                }

                if (str_starts_with($ip, '127.') || str_starts_with($ip, '169.254.')) {
                    continue;
                }

                $candidates[] = ['name' => $name, 'ip' => $ip];
            }
        }

        if ($candidates === []) {
            return null;
        }

        $score = static function (array $candidate): int {
            $name = strtolower($candidate['name']);
            $ip = $candidate['ip'];

            $byName = match (true) {
                str_starts_with($name, 'en0'), str_starts_with($name, 'wlan0'), str_starts_with($name, 'eth0') => 30,
                str_starts_with($name, 'en'), str_starts_with($name, 'wlan'), str_starts_with($name, 'eth') => 20,
                default => 0,
            };

            $byRange = match (true) {
                str_starts_with($ip, '192.168.') => 3,
                str_starts_with($ip, '10.') => 2,
                preg_match('/^172\.(1[6-9]|2\d|3[01])\./', $ip) === 1 => 1,
                default => 0,
            };

            return $byName + $byRange;
        };

        usort($candidates, static fn (array $a, array $b): int => $score($b) <=> $score($a));

        return $candidates[0]['ip'];
    }
}
