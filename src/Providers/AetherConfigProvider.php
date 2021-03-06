<?php

namespace Aether\Providers;

use Aether\AetherConfig;

class AetherConfigProvider extends Provider
{
    public function register()
    {
        $this->aether->singleton('aetherConfig', function ($aether) {
            $projectRoot = $aether['projectRoot'];

            if (! file_exists($configPath = $projectRoot.'config/autogenerated.config.xml')) {
                $configPath = $projectRoot.'config/aether.config.xml';
            }

            $aetherConfig = new AetherConfig($configPath);

            if ($aether->bound('parsedUrl')) {
                $aetherConfig->matchUrl($aether['parsedUrl']);
            }

            return $aetherConfig;
        });
    }
}
