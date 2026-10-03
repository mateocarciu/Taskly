<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class HtmlSanitizerProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HtmlSanitizer::class, function () {
            $config = (new HtmlSanitizerConfig)
                ->allowSafeElements()
                ->allowRelativeLinks()
                ->allowLinkSchemes(['http', 'https', 'mailto'])
                ->allowAttribute('class', '*')
                ->allowAttribute('style', '*')
                ->allowAttribute('data-id', '*')
                ->allowAttribute('data-action', '*')
                ->allowAttribute('data-target', '*')
                ->allowAttribute('data-task', '*')
                ->allowAttribute('data-comment', '*')
                ->allowAttribute('id', '*')
                ->allowAttribute('title', '*')
                ->allowAttribute('alt', '*')
                ->allowAttribute('width', '*')
                ->allowAttribute('height', '*')
                ->allowAttribute('src', ['img'])
                ->allowAttribute('target', ['a'])
                ->allowAttribute('rel', ['a']);

            return new HtmlSanitizer($config);
        });

        $this->app->alias(HtmlSanitizer::class, 'html.sanitizer');
    }

    public function boot(): void
    {
        //
    }
}
