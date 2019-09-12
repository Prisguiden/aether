<?php

namespace Aether\Providers;

use Raven_Client;

class SentryProvider extends Provider
{
    public function register()
    {
        $this->aether->singleton('sentry.client', function ($aether) {
            return $this->getClient($aether);
        });
    }

    protected function getClient($aether)
    {
        $projectRoot = rtrim($aether['projectRoot'], '/');

        return \Sentry\init([
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
