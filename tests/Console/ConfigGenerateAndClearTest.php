<?php

namespace Tests\Console;

use Tests\TestCase;
use Aether\Console\Kernel;

class ConfigGenerateAndClearTest extends TestCase
{
    protected $console;

    protected function setUp()
    {
        parent::setUp();

        $this->console = $this->aether->make(Kernel::class);
    }

    public function testThatItFuckingWorks()
    {
        $autogenerated = $this->aether['projectRoot'].'config/autogenerated.config.xml';
        $compiled = $this->aether['projectRoot'].'config/compiled.php';

        $this->assertEquals(0, $this->console->call('config:generate'));

        $this->assertFileExists($autogenerated);
        $this->assertFileExists($compiled);

        $this->assertEquals(0, $this->console->call('config:clear'));

        $this->assertFileNotExists($autogenerated);
        $this->assertFileNotExists($compiled);
    }
}
