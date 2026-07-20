<?php

namespace App\Providers;

use App\Support\Sse\PollingRevisionChannel;
use App\Support\Sse\RedisRevisionChannel;
use App\Support\Sse\RevisionChannel;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The spectator stream's change-detection transport, chosen by config/sse.php: 'driver'.
        $this->app->bind(RevisionChannel::class, function ($app): RevisionChannel {
            $config = $app['config'];

            if ($config->get('sse.driver') === 'redis') {
                return new RedisRevisionChannel((string) $config->get('sse.redis_connection', 'default'));
            }

            return new PollingRevisionChannel((int) $config->get('sse.poll_ms', 1500));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('viewApiDocs', fn ($user = null) => true);

        $this->configureRateLimiting();

        // The TournamentUpdated -> PublishTournamentUpdate wiring (redis stream driver) is registered
        // by Laravel's listener auto-discovery from app/Listeners; the listener is a no-op under the
        // default 'poll' driver.
    }

    /**
     * Rate limiter for the unauthenticated credential endpoints (/login, /register).
     *
     * Two tiers, both keyed so a 429 carries a Retry-After:
     *   - 5/min per (email + IP): throttles a targeted attack (guessing one account's
     *     password, or hammering one email at register) without letting an attacker lock a
     *     victim out globally — the key includes the attacker's own IP.
     *   - 20/min per IP: a blast-radius cap on credential stuffing / signup spam that rotates
     *     the email to dodge the first tier.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('auth', function (Request $request) {
            $email = mb_strtolower(trim((string) $request->input('email')));
            $ip = (string) $request->ip();

            return [
                Limit::perMinute(5)->by($email !== '' ? $email.'|'.$ip : $ip),
                Limit::perMinute(20)->by($ip),
            ];
        });
    }
}
