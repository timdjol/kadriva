<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        return $this->success($this->userData($request->user()));
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'country_code' => ['sometimes', 'string', 'size:2', 'in:'.implode(',', array_keys(config('cinema.countries')))],
        ]);

        $user = User::create([
            ...$data,
            'role' => 'user',
            'country_code' => $data['country_code'] ?? 'KG',
        ]);
        Auth::guard('web')->login($user);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return $this->success($this->userData($user), [], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return $this->error('INVALID_CREDENTIALS', 'Неверный email или пароль.', 422, [
                'email' => ['Неверные учётные данные.'],
            ]);
        }

        Auth::guard('web')->login($user, $request->boolean('remember'));
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return $this->success($this->userData($user));
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        Auth::guard('sanctum')->forgetUser();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return $this->success(['message' => 'Вы вышли из аккаунта.']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        PasswordBroker::sendResetLink($request->only('email'));

        return $this->success(['message' => 'Если аккаунт существует, мы отправили ссылку для сброса пароля.']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $status = PasswordBroker::reset(
            $data,
            function (User $user, string $password): void {
                $user->forceFill(['password' => $password])->save();
                event(new PasswordReset($user));
            }
        );

        if ($status !== PasswordBroker::PASSWORD_RESET) {
            return $this->error('RESET_FAILED', 'Не удалось сбросить пароль. Проверьте ссылку и попробуйте снова.', 422);
        }

        return $this->success(['message' => 'Пароль обновлён. Теперь можно войти.']);
    }

    private function userData(User $user): array
    {
        return $user->only(['id', 'name', 'email', 'role', 'country_code']);
    }
}
