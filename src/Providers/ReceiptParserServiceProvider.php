<?php

namespace Theod\CloudVisionClient\Providers;

use Illuminate\Support\ServiceProvider;
use Theod\CloudVisionClient\Processor\Contracts\ReceiptParserProcessorInterface;
use Theod\CloudVisionClient\Processor\ReceiptParserProcessor;

final class ReceiptParserServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/receipt-parser.php' => config_path('receipt-parser.php'),
        ], 'receipt-parser-config');

//        if ($this->app->runningInConsole()) {
//            $this->commands(
//                commands: [
//                    DataTransferObjectMakeCommand::class,
//                ],
//            );
//        }
    }

    public function register(){
        $this->app->register(CloudVisionServiceProvider::class);
        $this->app->bind(ReceiptParserProcessorInterface::class, ReceiptParserProcessor::class);
    }
}