<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Route as RouteObj;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Visits every GET route that has no URL parameters, as each role that is
 * allowed to see it, and fails if any of them throws a server error.
 */
class RouteSmokeTest extends TestCase
{
    use DatabaseTransactions;

    /** Routes that are not pages (downloads, logout, auth flow) or need extra steps. */
    private const SKIP = [
        'logout', 'login', '2fa/setup', '2fa/verify', 'password/confirm',
        'sanctum/csrf-cookie', '_ignition', 'api/user',
    ];

    /** Image endpoints that correctly return 404 until an image is uploaded in settings. */
    private const EMPTY_404_OK = ['settings/general/logo', 'settings/general/favicon', 'billing/payment/qr-image'];

    private const ROLES = ['admin', 'doctor', 'nurse', 'pharmacist', 'cashier', 'lab_technician'];

    /** @return array<int, RouteObj> */
    private function pageRoutes(): array
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(fn (RouteObj $r) => in_array('GET', $r->methods()))
            ->filter(fn (RouteObj $r) => ! str_contains($r->uri(), '{'))
            ->reject(function (RouteObj $r) {
                foreach (self::SKIP as $s) {
                    if (str_starts_with($r->uri(), $s)) {
                        return true;
                    }
                }
                return false;
            })
            ->values()
            ->all();
    }

    /** Roles allowed by the route's role:... middleware, or null when there is no role restriction. */
    private function allowedRoles(RouteObj $r): ?array
    {
        $allowed = null;
        foreach ($r->gatherMiddleware() as $m) {
            if (is_string($m) && str_starts_with($m, 'role:')) {
                $roles = explode('|', substr($m, 5));
                $allowed = $allowed === null ? $roles : array_values(array_intersect($allowed, $roles));
            }
        }
        return $allowed;
    }

    public function test_guest_is_redirected_from_protected_pages(): void
    {
        $failures = [];
        foreach ($this->pageRoutes() as $r) {
            if (! in_array('auth', $r->gatherMiddleware())) {
                continue;
            }
            $res = $this->get('/' . ltrim($r->uri(), '/'));
            if (! $res->isRedirect() || ! str_contains($res->headers->get('Location'), '/login')) {
                $failures[] = "{$r->uri()} -> {$res->status()}";
            }
        }
        $this->assertSame([], $failures, "Guest could reach protected pages:\n" . implode("\n", $failures));
    }

    /** @dataProvider roles */
    public function test_every_allowed_page_renders_for_role(string $role): void
    {
        $this->actingAsRole($role);

        $failures = [];
        $visited = 0;
        foreach ($this->pageRoutes() as $r) {
            $allowed = $this->allowedRoles($r);
            if ($allowed !== null && ! in_array($role, $allowed)) {
                continue;
            }
            $visited++;
            $res = $this->get('/' . ltrim($r->uri(), '/'));
            $status = $res->baseResponse->getStatusCode(); // works for downloads too
            if ($status === 404 && in_array($r->uri(), self::EMPTY_404_OK)) {
                continue;
            }
            if ($status >= 500) {
                $msg = optional($res->exception)->getMessage();
                $failures[] = "{$r->uri()} -> {$status}: " . substr((string) $msg, 0, 300);
            } elseif (! in_array($status, [200, 302, 403])) {
                // 403 can come from permission:... checks inside a role group.
                $failures[] = "{$r->uri()} -> unexpected {$status}";
            }
        }

        $this->assertGreaterThan(0, $visited);
        $this->assertSame([], $failures, "Broken pages for {$role}:\n" . implode("\n", $failures));
    }

    /** @dataProvider roles */
    public function test_role_is_blocked_from_pages_of_other_roles(string $role): void
    {
        $this->actingAsRole($role);

        $failures = [];
        foreach ($this->pageRoutes() as $r) {
            $allowed = $this->allowedRoles($r);
            if ($allowed === null || in_array($role, $allowed)) {
                continue;
            }
            $status = $this->get('/' . ltrim($r->uri(), '/'))->baseResponse->getStatusCode();
            if ($status === 200 || $status >= 500) {
                $failures[] = "{$r->uri()} -> {$status}";
            }
        }
        $this->assertSame([], $failures, "{$role} was not blocked from:\n" . implode("\n", $failures));
    }

    public function roles(): array
    {
        return array_combine(self::ROLES, array_map(fn ($r) => [$r], self::ROLES));
    }
}
