<?php

namespace App\Providers;

use App\Domain\Video\CompositeVideoProvider;
use App\Domain\Video\ExternalLinkProvider;
use App\Domain\Video\MockVideoProvider;
use App\Domain\Video\TrailerFallbackProvider;
use App\Domain\Video\VideoProviderInterface;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(VideoProviderInterface::class, fn () => new CompositeVideoProvider([
            $this->app->make(MockVideoProvider::class),
            $this->app->make(ExternalLinkProvider::class),
            $this->app->make(TrailerFallbackProvider::class),
        ]));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(fn (User $user, string $token) => url('/reset-password/'.$token.'?email='.urlencode($user->email)));

        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('search', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?: $request->ip()));

        foreach (['created', 'updated', 'deleted'] as $action) {
            Event::listen("eloquent.{$action}: *", function (string $event, array $models) use ($action): void {
                $model = $models[0] ?? null;
                $user = auth()->user();
                if (! $model instanceof Model || $model instanceof AuditLog || ! $user) {
                    return;
                }

                AuditLog::create([
                    'user_id' => $user->id,
                    'action' => $action,
                    'auditable_type' => $model->getMorphClass(),
                    'auditable_id' => $model->getKey(),
                    'old_values' => $action === 'updated' ? $model->getOriginal() : null,
                    'new_values' => $action === 'deleted' ? null : $model->getAttributes(),
                    'ip_address' => request()->ip(),
                ]);
            });
        }
    }
}
