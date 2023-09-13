<?php

namespace App\Actions\Api;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SendToZapier
{
    use Dispatchable;

    public function __construct(
        public $name,
        public $email,
        public $phone,
    ) {}


    public function handle()
    {

        $response = Http::withHeaders(['verify' => false])->post(config('services.zapier.endpoint'), [
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
        ])->json();

        info($response);
    }
}
