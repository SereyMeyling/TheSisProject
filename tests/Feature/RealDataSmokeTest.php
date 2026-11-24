<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Route as RouteObj;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Opens every page — including every detail page for every real record — as a
 * real user of each role. Meant to run on a COPY of production data loaded
 * into the *_testing database (see TestCase guard). Skips itself when the
 * testing DB has no real data (e.g. a fresh migrate:fresh --seed).
 */
class RealDataSmokeTest extends TestCase
{
    use DatabaseTransactions;

    private const SKIP_PREFIX = [
        'logout', 'login', '2fa/', 'password/confirm', 'sanctum/', '_ignition', 'api/',
        'billing/payment/check-status', // calls the live Bakong API
    ];

    private const EMPTY_404_OK = ['settings/general/logo', 'settings/general/favicon', 'billing/payment/qr-image'];

    /** URL parameter name per route prefix -> [table, column]. */
    private const PARAM_SOURCES = [
        'appointment/'              => ['appointments', 'appointment_id'],
        'billing/pending-charges/'  => ['patients', 'patient_id'],
        'billing/'                  => ['invoices', 'id'],
        'department/'               => ['departments', 'department_id'],
        'doctor/consultation/'      => ['medical_records', 'record_id'],
        'lab/results/'              => ['lab_results', 'result_id'],
        'medical-records/'          => ['medical_records', 'record_id'],
        'patients/'                 => ['patients', 'patient_id'],
        'pharmacy/'                 => ['medicines', 'medicine_id'],
        'profile/avatar/'           => ['users', 'id'],
        'room/'                     => ['rooms', 'room_id'],
        'settings/backup/download/' => ['database_backups', 'filename'],
    ];

    private const ROLES = ['admin', 'doctor', 'nurse', 'pharmacist', 'cashier', 'lab_technician'];

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::table('patients')->count() === 0) {
            $this->markTestSkipped('No real data in the testing DB. Load a production copy first.');
        }
    }

    /** Real user with this role (2FA secret replaced inside the rolled-back transaction). */
    private function loginAsRealUser(string $role): User
    {
        $user = User::role($role)->orderBy('id')->first();
        if (! $user) {
            return $this->actingAsRole($role); // no real user with this role yet
        }
        $user->google2fa_secret = (new Google2FA())->generateSecretKey();
        $user->save();
        $this->actingAs($user)->withSession(['2fa_passed' => true]);

        return $user;
    }

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

    /** @return string[] concrete URLs to visit for this route */
    private function urlsFor(RouteObj $r): array
    {
        $uri = $r->uri();
        if (! str_contains($uri, '{')) {
            return ['/' . $uri];
        }
        foreach (self::PARAM_SOURCES as $prefix => [$table, $col]) {
            if (str_starts_with($uri, $prefix)) {
                return DB::table($table)->pluck($col)
                    ->map(fn ($v) => '/' . preg_replace('/\{[^}]+\}/', rawurlencode((string) $v), $uri))
                    ->all();
            }
        }
        return []; // unknown parameter: reported by test_all_parameter_routes_are_mapped
    }

    private function pageRoutes()
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(fn (RouteObj $r) => in_array('GET', $r->methods()))
            ->reject(function (RouteObj $r) {
                foreach (self::SKIP_PREFIX as $s) {
                    if (str_starts_with($r->uri(), $s)) {
                        return true;
                    }
                }
                return false;
            });
    }

    public function test_all_parameter_routes_are_mapped(): void
    {
        $unmapped = $this->pageRoutes()
            ->filter(fn (RouteObj $r) => str_contains($r->uri(), '{') && $this->urlsFor($r) === []
                && ! collect(self::PARAM_SOURCES)->keys()->contains(fn ($p) => str_starts_with($r->uri(), $p)))
            ->map(fn (RouteObj $r) => $r->uri())->values()->all();

        $this->assertSame([], $unmapped, 'Routes with parameters this test does not know how to fill.');
    }

    /** @dataProvider roles */
    public function test_every_page_and_record_opens_for_role(string $role): void
    {
        $user = $this->loginAsRealUser($role);

        $failures = [];
        $visited = 0;
        foreach ($this->pageRoutes() as $r) {
            $allowed = $this->allowedRoles($r);
            if ($allowed !== null && ! in_array($role, $allowed)) {
                continue;
            }
            foreach ($this->urlsFor($r) as $url) {
                $visited++;
                $res = $this->get($url);
                $status = $res->baseResponse->getStatusCode();

                if ($status === 404 && in_array($r->uri(), self::EMPTY_404_OK)) {
                    continue;
                }
                // Paid/cancelled invoices are intentionally not editable (UI hides the button).
                if ($status === 422 && $r->uri() === 'billing/{id}/edit') {
                    $inv = DB::table('invoices')->where('id', basename(dirname($url)))->first();
                    if ($inv && $inv->status !== 'unpaid') {
                        continue;
                    }
                }
                if ($status >= 500) {
                    $e = $res->exception;
                    $where = $e ? (basename($e->getFile()) . ':' . $e->getLine()) : '';
                    $failures[] = "{$url} -> {$status} {$where} " . substr((string) optional($e)->getMessage(), 0, 250);
                } elseif ($status === 302 && str_contains((string) $res->headers->get('Location'), '/login')) {
                    $failures[] = "{$url} -> logged out (redirect to login)";
                } elseif (! in_array($status, [200, 302, 403, 404])) {
                    $failures[] = "{$url} -> unexpected {$status}";
                }
            }
        }

        fwrite(STDERR, "\n[{$role}] as user #{$user->id} ({$user->email}): {$visited} URLs visited, " . count($failures) . " problems\n");
        $this->assertSame([], $failures, "Problems for {$role} (user #{$user->id}):\n" . implode("\n", $failures));
    }

    public function roles(): array
    {
        return array_combine(self::ROLES, array_map(fn ($r) => [$r], self::ROLES));
    }
}
