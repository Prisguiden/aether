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
            'default_integrations' => false,
            'integrations' => [
                new Sentry\Integration\RequestIntegration(),
                new Sentry\Integration\ModulesIntegration()
            ],
            'in_app_include' => array_map(function ($e) use ($projectRoot) {
                return $projectRoot . $e;
            }, config('app.sentry.include', ["/src"])),
            'in_app_exclude' => array_map(function ($e) use ($projectRoot) {
                return $projectRoot . $e;
            }, config('app.sentry.exclude', ["/vendor"])),
            'attach_stacktrace' => true,
            'release' => config('app.release'),
            'tags' => [
                'php_version' => phpversion(),
                'project_root' => $projectRoot
            ],
        ]);
    }
}
