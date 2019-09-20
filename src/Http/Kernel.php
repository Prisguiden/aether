<?php

namespace Aether\Http;

use Error;
use Exception;
use Throwable;
use Aether\Aether;
use Aether\UrlParser;
use Aether\Response\ResponseFactory;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Symfony\Component\ErrorHandler\Exception\FatalThrowableError;

class Kernel
{
    protected $aether;

    public function __construct(Aether $aether)
    {
        $this->aether = $aether;
    }

    public function bootstrap()
    {
        $this->aether->registerProviders([
            \Aether\Providers\AetherConfigProvider::class
        ]);
    }

    public function handle(UrlParser $parsedUrl)
    {
        try {
            $this->aether->instance('parsedUrl', $parsedUrl);

            $this->bootstrap();

            $this->aether->initiateSection();

            return $this->aether->call([ResponseFactory::createFromGlobals(), 'getResponse']);
        } catch (Throwable $e) {
            if ($e instanceof Error) {
                print_r($e->getMessage());
                echo 'yeboi';
                $e = new FatalThrowableError($e);
            }

            $this->reportException($e);

            return $this->renderException($e);
        }
    }

    protected function reportException(Exception $e)
    {
        $this->aether->make(ExceptionHandler::class)->report($e);
    }

    protected function renderException(Exception $e)
    {
        return $this->aether->make(ExceptionHandler::class)->render(null, $e);
    }
}
