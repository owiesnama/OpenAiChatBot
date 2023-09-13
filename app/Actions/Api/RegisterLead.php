<?php

namespace App\Actions\Api;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RegisterLead
{
    use Dispatchable;

    public function __construct(
        public $name,
        public $email,
        public $phone,
    ) {}


    public function handle()
    {
        $urlWithKey = config('services.otas.endpoint') . '?api_key=' . config('services.otas.api_key');
        $name = Str::of($this->name)->squish();
        $nameParts = explode(' ', $name);
        $lastName = array_pop($nameParts);
        $firstName = implode(' ', $nameParts);

        $response = Http::withHeaders(['verify' => false])->post($urlWithKey, [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $this->phone,
            'email' => $this->email,
        ])->json();

        info($response);
    }
}
