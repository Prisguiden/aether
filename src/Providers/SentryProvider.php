<?php

namespace Aether\Providers;

use Sentry;

class SentryProvider extends Provider
{
    public function register()
    {
        $projectRoot = rtrim($this->aether['projectRoot'], '/');
        Sentry\init([
            'dsn' => config('app.sentry.dsn'),
            'environment' => config('app.env'),
            'project_root' => $projectRoot,
            'prefixes' => [$projectRoot],
            'in_app_exclude' => ["{$projectRoot}/vendor"],
            'attach_stacktrace' => true,
            'release' => config('app.release'),
            'tags' => [
                'php_version' => phpversion(),
                'project_root' => $projectRoot
            ],
        ]);
    }
}
