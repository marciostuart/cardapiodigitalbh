<?php

namespace App\Http\Controllers;

use App\Models\ConsumerAccount;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class ConsumerAccountController extends Controller
{
    public function register(Request $request)
    {
        $request->merge(['phone' => $this->normalizePhone((string) $request->input('phone')), 'email' => Str::lower(trim((string) $request->input('email')))]);
        $data = $request->validate($this->registrationRules());
        $phone = $data['phone'];

        $consumer = DB::transaction(function () use ($data, $phone) {
            $consumer = ConsumerAccount::create([
                'name' => trim($data['name']),
                'phone' => $phone,
                'email' => Str::lower(trim($data['email'])),
                'password' => $data['password'],
                'last_login_at' => now(),
            ]);
            $consumer->addresses()->create($this->address($data) + ['is_default' => true]);
            return $consumer->load('defaultAddress');
        });

        Auth::guard('consumer')->login($consumer, true);
        $request->session()->regenerate();

        return response()->json(['message' => 'Conta criada com sucesso.', 'consumer' => $this->consumerData($consumer)], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate(['phone' => ['required', 'string'], 'password' => ['required', 'string']]);
        $phone = $this->normalizePhone($data['phone']);
        $key = 'consumer-login:'.Str::lower($phone).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['message' => 'Muitas tentativas. Aguarde alguns minutos antes de tentar novamente.'], 429);
        }

        $consumer = ConsumerAccount::where('phone', $phone)->with('defaultAddress')->first();
        if (! $consumer || ! Hash::check($data['password'], $consumer->password)) {
            RateLimiter::hit($key, 60);
            return response()->json(['message' => 'Telefone ou senha inválidos.'], 422);
        }

        RateLimiter::clear($key);
        $consumer->update(['last_login_at' => now()]);
        Auth::guard('consumer')->login($consumer, true);
        $request->session()->regenerate();

        return response()->json(['message' => 'Acesso realizado com sucesso.', 'consumer' => $this->consumerData($consumer)]);
    }

    public function logout(Request $request)
    {
        Auth::guard('consumer')->logout();
        $request->session()->regenerateToken();

        return $request->expectsJson() ? response()->json(['message' => 'Você saiu da sua conta.']) : back();
    }

    public function forgotPassword(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        Password::broker('consumers')->sendResetLink(['email' => Str::lower(trim($data['email']))]);

        return response()->json(['message' => 'Se houver uma conta com este e-mail, enviaremos as instruções para redefinir a senha.']);
    }

    public function showResetPassword(Request $request, string $token)
    {
        return view('public.consumer-reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()],
        ]);

        $status = Password::broker('consumers')->reset($data, function (ConsumerAccount $consumer, string $password) use ($request) {
            $consumer->forceFill(['password' => $password, 'remember_token' => Str::random(60), 'last_login_at' => now()])->save();
            event(new PasswordReset($consumer));
            Auth::guard('consumer')->login($consumer, true);
            $request->session()->regenerate();
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => 'Não foi possível redefinir a senha. Solicite um novo link e tente novamente.']);
        }

        return redirect('/')->with('status', 'Senha atualizada. Você já pode continuar seu pedido.');
    }

    private function registrationRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'regex:/^55\\d{10,11}$/', 'unique:consumer_accounts,phone'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:consumer_accounts,email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()],
            'postal_code' => ['required', 'regex:/^\\d{5}-?\\d{3}$/'],
            'street' => ['required', 'string', 'max:150'],
            'number' => ['required', 'string', 'max:30'],
            'complement' => ['nullable', 'string', 'max:100'],
            'neighborhood' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'size:2'],
            'reference' => ['nullable', 'string', 'max:150'],
        ];
    }

    private function address(array $data): array
    {
        return collect($data)->only(['postal_code', 'street', 'number', 'complement', 'neighborhood', 'city', 'state', 'reference'])->all();
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\\D+/', '', $phone);
        if (in_array(strlen($digits), [10, 11], true)) $digits = '55'.$digits;
        return $digits;
    }

    private function consumerData(ConsumerAccount $consumer): array
    {
        return ['name' => $consumer->name, 'phone' => $consumer->phone, 'email' => $consumer->email, 'address' => $consumer->defaultAddress?->only(['postal_code', 'street', 'number', 'complement', 'neighborhood', 'city', 'state', 'reference'])];
    }
}
