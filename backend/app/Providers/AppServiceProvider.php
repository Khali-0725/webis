<?php

namespace App\Providers;

use App\Support\Api\ApiResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureModels();
        $this->configureRateLimiting();
        $this->configureUrls();
    }

    /**
     * Strict model behaviour catches whole classes of bug at development time:
     *
     *  - preventLazyLoading   -> N+1 queries become an exception, not a slow page
     *  - preventSilentlyDiscardingAttributes -> a typo'd or non-fillable attribute
     *    throws instead of being quietly dropped (this is also a mass-assignment
     *    safety net)
     *  - preventAccessingMissingAttributes -> reading an unselected column throws
     *
     * All three are enabled outside production only, so a production request is
     * never taken down by a developer-ergonomics guard.
     */
    private function configureModels(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
    }

    /**
     * Every limiter attaches the same rejection response so a 429 always comes
     * back inside the API envelope instead of Laravel's HTML error page.
     */
    private function configureRateLimiting(): void
    {
        $tooMany = fn () => ApiResponse::error(
            'Too many requests. Please slow down and try again shortly.',
            [],
            429
        );

        // Sign-in / registration. Deliberately tight - brute-force defence.
        RateLimiter::for('auth', fn (Request $request) => [
            Limit::perMinute(10)->by('ip:'.$request->ip())->response($tooMany),
            Limit::perMinute(5)
                ->by('cred:'.mb_strtolower((string) $request->input('email')).'|'.$request->ip())
                ->response($tooMany),
        ]);

        // General authenticated API traffic.
        RateLimiter::for('api', fn (Request $request) => $request->user()
            ? Limit::perMinute(120)->by('u:'.$request->user()->id)->response($tooMany)
            : Limit::perMinute(40)->by('ip:'.$request->ip())->response($tooMany));

        // Writes that create records.
        RateLimiter::for('writes', fn (Request $request) => Limit::perMinute(60)
            ->by($request->user() ? 'u:'.$request->user()->id : 'ip:'.$request->ip())
            ->response($tooMany));

        // File uploads.
        RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(10)
            ->by($request->user() ? 'u:'.$request->user()->id : 'ip:'.$request->ip())
            ->response($tooMany));
    }

    private function configureUrls(): void
    {
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
