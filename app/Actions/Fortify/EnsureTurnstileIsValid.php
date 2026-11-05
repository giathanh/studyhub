<?php

namespace App\Actions\Fortify;

use App\Rules\Turnstile;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class EnsureTurnstileIsValid
{
    /**
     * Stop the login pipeline unless the request carries a valid Turnstile token.
     *
     * @param  Closure(Request): Response  $next
     *
     * @throws ValidationException
     */
    public function __invoke(Request $request, Closure $next): Response
    {
        Validator::make($request->only(Turnstile::RESPONSE_FIELD), [
            Turnstile::RESPONSE_FIELD => [new Turnstile],
        ])->validate();

        return $next($request);
    }
}
