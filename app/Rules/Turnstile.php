<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Translation\PotentiallyTranslatedString;

class Turnstile implements ValidationRule
{
    /**
     * The form field that carries the Turnstile token.
     */
    public const string RESPONSE_FIELD = 'cf-turnstile-response';

    private const string VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /**
     * Indicates whether the rule should be implicit.
     *
     * @var bool
     */
    public $implicit = true;

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! config('services.turnstile.enabled')) {
            return;
        }

        if (! is_string($value) || $value === '' || ! $this->isAcceptedByCloudflare($value)) {
            $fail('Please complete the security check and try again.');
        }
    }

    /**
     * Ask Cloudflare whether the token is valid. An unreachable Cloudflare counts as a failure.
     */
    private function isAcceptedByCloudflare(string $token): bool
    {
        try {
            $response = Http::asForm()->connectTimeout(3)->timeout(5)->post(self::VERIFY_URL, [
                'secret' => config('services.turnstile.secret_key'),
                'response' => $token,
            ]);
        } catch (ConnectionException $exception) {
            report($exception);

            return false;
        }

        return $response->json('success') === true;
    }
}
