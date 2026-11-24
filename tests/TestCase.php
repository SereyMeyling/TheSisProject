<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use PragmaRX\Google2FA\Google2FA;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Safety guard: tests write and roll back data, so never let them
        // run against a real database.
        $db = DB::connection()->getDatabaseName();
        if (! str_ends_with($db, '_testing') && $db !== ':memory:') {
            throw new RuntimeException("Refusing to run tests against database '{$db}'. Use a *_testing database (see phpunit.xml).");
        }
    }

    /**
     * Create a user with the given role who has completed 2FA, and log in as them.
     */
    protected function actingAsRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole($role);
        $user->google2fa_secret = (new Google2FA())->generateSecretKey();
        $user->save();

        $this->actingAs($user)->withSession(['2fa_passed' => true]);

        return $user;
    }
}
