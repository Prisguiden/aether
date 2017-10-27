<?php // vim:set ts=4 sw=4 et:

class AetherConfigTest extends PHPUnit_Framework_TestCase
{
    private function getConfig()
    {
        return new AetherConfig(__DIR__.'/fixtures/aether.config.xml');
    }

    public function testEnvironment()
    {
        $this->assertTrue(class_exists('AetherConfig'));
    }

    private function getLoadedConfig($url)
    {
        $aetherUrl = new AetherUrlParser;
        $aetherUrl->parse($url);

        $conf = $this->getConfig();
        $conf->matchUrl($aetherUrl);

        return $conf;
    }

    private function getOptionsForUrl($url)
    {
        $config = $this->getLoadedConfig($url);
        return $config->getOptions();
    }

    public function testConfigReadDefault()
    {
        $conf = $this->getLoadedConfig('http://raw.no/unittest');
        $opts = $conf->getOptions();
        $this->assertEquals('Generic', $conf->getSection());
        $this->assertEquals('yes', $opts['def']);
    }

    public function testConfigReadDefaultBase()
    {
        $conf = $this->getLoadedConfig('http://raw.no/fluff');
        $opts = $conf->getOptions();

        $this->assertEquals($conf->getSection(), 'Generic');
        $this->assertEquals($opts['foobar'], 'yes');
    }

    public function testConfigAssembleOptionsCorrectly()
    {
        $conf = $this->getLoadedConfig('http://raw.no/unittest/foo');

        $modules = $conf->getModules();
        $this->assertArrayHasKey('HelloWorld', $modules, 'Module must exist');

        $module = $modules['HelloWorld'];
        $this->assertEquals('foobar', $module['options']['foo'], 'Module\'s local options must be correct');
    }

    public function testConfigFindParentDefault()
    {
        $firstOptions = $this->getOptionsForUrl('http://raw.no/thisshouldgive404');
        $this->assertEquals('yes', $firstOptions['foobar']);

        $secondOptions = $this->getOptionsForUrl('http://raw.no/unittest/heisann00');
        $this->assertEquals('yes', $secondOptions['def']);
    }

    public function testConfigFallbackToRootWhenOneMatchEmpty()
    {
        $opts = $this->getOptionsForUrl('http://raw.no/empty/fluff');
        $this->assertEquals('yes', $opts['foobar']);
    }

    public function testConfigFallbackToRootDefault()
    {
        $opts = $this->getOptionsForUrl('http://raw.no/bar/foo/bar');

        $this->assertEquals('yes', $opts['foobar']);
    }

    public function testConfigFallbackToDefaultSite()
    {
        $opts = $this->getOptionsForUrl('http://foo.no/unittest');
        $this->assertEquals('fallback-site', $opts['sitename']);
    }

    public function testMatchWithPlusInItWorks()
    {
        $opts = $this->getOptionsForUrl('http://raw.no/unittest/foo/a+b');
        $this->assertEquals('yes', $opts['plusm']);
    }

    public function testMatchWithMinusInItWorks()
    {
        $category = 'hifi-produkter';
        $conf = $this->getLoadedConfig("http://raw.no/unittest/{$category}");
        $this->assertTrue($conf->hasUrlVar('catName'));
        $this->assertEquals($category, $conf->getUrlVariable('catName'));
    }

    public function testConfigReset()
    {
        $conf = $this->getLoadedConfig("http://raw.no/unittest/goodtimes");
        $conf->resetRuleConfig();

        $this->assertEmpty($conf->getOptions());
        $this->assertNull($conf->getSection());
    }

    public function testTriggeredFallbackToDefaultRule()
    {
        $conf = $this->getLoadedConfig("http://raw.no/unittest/goodtimes/nay");
        $conf->reloadConfigFromDefaultRule();

        $this->assertEquals('NotFoundSection', $conf->getSection());
    }

    public function testBooleanTypeCasting()
    {
        $opts = $this->getOptionsForUrl('http://foobar.com/bool-casting');

        $this->assertTrue($opts['shouldBeTrue']);
        $this->assertFalse($opts['shouldBeFalse']);

        $this->assertSame($opts['shouldBeTrueString'], 'true');
        $this->assertSame($opts['shouldBeFalseString'], 'false');
    }

    public function testBooleanTypeCastingInModules()
    {
        $conf = $this->getLoadedConfig('http://foobar.com/bool-casting');
        $module = current($conf->getModules());
        $opts = $module['options'];

        $this->assertTrue($opts['fisk']);
        $this->assertFalse($opts['ananas']);
    }
}
