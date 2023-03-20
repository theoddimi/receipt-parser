<?php

namespace Theod\ReceiptParser\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Theod\ReceiptParser\Services\CloudVisionService;

class CloudVisionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CloudVisionService::class, function (Application $app) {
            $client = Http::withOptions([
                'base_uri' => 'https://vision.googleapis.com/v1',
            ])->withHeaders(['Content-Type' => 'application/json'])->withUrlParameters(['key' => config('services.cloud_vision.api_key'),]);
//            ])->withToken(config('services.example.token'));

            return new CloudVisionService($client);
        });
    }

}