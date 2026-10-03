<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\HtmlSanitizerProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    HtmlSanitizerProvider::class,
];
