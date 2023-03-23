<?php

namespace Theod\CloudVisionClient\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Theod\CloudVisionClient\Services\CloudVisionService;

class CloudVisionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CloudVisionService::class, function (Application $app) {
            $client = Http::withHeaders(['Content-Type' => 'application/json'])
                ->withUrlParameters(['key' => config('services.cloud_vision.api_key')])
            ->baseUrl('https://vision.googleapis.com/v1/');
//            ])->withToken(config('services.example.token'));

            return new CloudVisionService($client);
        });
    }
}