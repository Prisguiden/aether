<?php

namespace Aether;

use Dotenv\Dotenv;
use Illuminate\Config\Repository;
use Dotenv\Exception\InvalidPathException;

class Config extends Repository
{
    /**
     * Boolean flag to check if the configuration values were loaded from the
     * pre-compiled file.
     *
     * @var bool
     */
    protected $loadedFromCompiled = false;
    private $configPath = null;

    /**
     * Create a new AetherAppConfig instance. This will automatically load the
     * configuration from the path specified.
     *
     * @param  string $projectRoot  Trailing slash is allowed.
     */
    public function __construct(string $projectRoot)
    {
        $projectRoot = rtrim($projectRoot, '/');
        $this->configPath  = $projectRoot.'/config';

        parent::__construct($this->loadConfig($projectRoot));
    }

    public function reload()
    {
        $this->loadedFromCompiled = false;
        $this->items = $this->loadConfigData();
    }

    /**
     * Determine if the configuration was loaded from the compiled file.
     *
     * @return bool
     */
    public function wasLoadedFromCompiled()
    {
        return $this->loadedFromCompiled;
    }

    /**
     * Save the entire configuration array to a file.
     *
     * @param  string  $file
     * @return void
     */
    public function saveToFile($file)
    {
        $data = '<?php return '.var_export($this->all(), true).';';

        file_put_contents($file, $data);
    }

    /**
     * Load the configuration.
     *
     * @param  string $projectRoot
     * @return array  All config items.
     */
    private function loadConfig(string $projectRoot): array
    {
        // First, we need to load the .env file.
        $appEnv = env('APP_ENV', 'development');
        if ($appEnv !== 'development') {
            $envFile = '.env.' . $appEnv;
        } else {
            $envFile = '.env';
        }
        $this->installDotenv($projectRoot, $envFile);

        return $this->loadConfigData();
    }

    private function loadConfigData()
    {
        $appEnv = env('APP_ENV', 'development');
        // If a `compiled.php` file exists, we'll use that.
        if ($appEnv === 'production') {
            if ($config = $this->loadConfigDataFromCache()) {
                return $config;
            }
        }

        // Otherwise, we'll need to load the configuration files from the
        // `config` folder in our project.
        $config = $this->loadConfigDataFromFiles();

        return $config;
    }

    private function loadConfigDataFromCache()
    {
        if (file_exists($compiled = $this->configPath.'/compiled.php')) {
            $this->loadedFromCompiled = true;

            return require $compiled;
        }

        return null;
    }

    private function loadConfigDataFromFiles()
    {
        $config = [];

        foreach ($this->getSortedConfigFiles($this->configPath) as $path) {
            $parts = explode('.', basename($path, '.php'), 2);

            if (count($parts) === 2) {
                list($configName, $matchEnv) = $parts;
            } else {
                list($configName, $matchEnv) = [$parts[0], null];
            }

            // If the config file is *not* targeting an environment, go ahead
            // and load it.
            if (! $matchEnv) {
                $config[$configName] = $this->requireFile(
                    "{$this->configPath}/{$configName}.php"
                );
            }
            // Otherwise, we'll check if the target environment matches the
            // actual environment before merging it in.
            elseif ($matchEnv === env('APP_ENV')) {
                $config[$configName] = array_replace_recursive(
                    $config[$configName] ?? [],
                    $this->requireFile("{$this->configPath}/{$configName}.{$matchEnv}.php")
                );
            }
        }

        return $config;
    }

    /**
     * Get the list of PHP files in the config directory, sorting the results
     * by length, making sure that any environment specific files are always
     * loaded last.
     *
     * Yes, it is a tiny bit nasty, but luckily this process only happens
     * during development and at deploy time.
     *
     * @return array
     */
    private function getSortedConfigFiles($configPath)
    {
        $files = glob($configPath.'/*.php');

        usort($files, function ($a, $b) {
            return strlen($a) <=> strlen($b);
        });

        return $files;
    }

    /**
     * Load the environment file using Dotenv.
     *
     * @param  string $path
     * @return void
     */
    private function installDotenv($path, $envFile)
    {
        try {
            (Dotenv::createImmutable($path, $envFile))->load();
        } catch (InvalidPathException $e) {
            // Do nothing if the .env file is not present.
        }
    }

    /**
     * Require a file and return its value.
     *
     * We do this in a seperate function so the code in the required file is
     * executed in a new function scope.
     *
     * @param  string  $path
     * @return mixed
     */
    private function requireFile($path)
    {
        return require $path;
    }
}
