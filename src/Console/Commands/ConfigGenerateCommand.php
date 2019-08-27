<?php

namespace Aether\Console\Commands;

use Aether\Aether;
use Aether\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class ConfigGenerateCommand extends Command
{
    protected $signature = 'config:generate';

    protected $description = 'Generate compiled configuration files.';

    public function handle()
    {
        $this->call('config:clear');

        $this->writeCompiledConfig($this->aether);
        $this->info('Wrote config/compiled.php.');
        try {
            $this->writeAetherConfig($this->aether);
            $this->info('Wrote config/autogenerated.config.xml.');
        } catch (FileNotFoundException $e) {
            $this->info('Skipped config/autogenerated.config.xml, no config/aether.config.xml.');
        }


        $this->info("Environment: {$this->aether['config']['app.env']}");
    }

    protected function writeCompiledConfig($aether)
    {
        $aether['config']->saveToFile(
            "{$aether['projectRoot']}config/compiled.php"
        );
    }

    protected function writeAetherConfig($aether)
    {
        $aether['aetherConfig']->saveToFile(
            "{$aether['projectRoot']}config/autogenerated.config.xml"
        );
    }
}
