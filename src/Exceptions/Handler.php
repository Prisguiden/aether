<?php

namespace Aether\Exceptions;

use Throwable;
use Aether\Aether;
use Sentry;
use Whoops\Run as Whoops;
use Whoops\Handler\PrettyPageHandler;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Debug\Exception\FatalThrowableError;

class Handler implements ExceptionHandler
{
    protected $aether;

    protected $context = [];

    /**
     * If supported by the reporting mechanism, this should contain a unique ID
     * that can be linked to the thrown exception.
     *
     * @var string
     */
    protected $lastReportedId;

    public function __construct(Aether $aether)
    {
        $this->aether = $aether;
    }

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $e
     * @param  array  $context = []
     * @return void
     */
    public function report(Throwable $e)
    {
        if ($this->aether->isProduction()) {
            $this->reportInProduction($e);
        } else {
            $this->reportInDevelopment($e);
        }

        // todo: figure out if we should also send to regular logs in prod
    }

    public function shouldReport(Throwable $e)
    {
        return true;
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request  (Note: Not yet in use)
     * @param  \Exception  $e
     * @return \Aether\Response\Response
     */
    public function render($request, Throwable $e)
    {
        $response = new ExceptionResponse($e, $this->getLastReportedId());

        if ($this->aether->isProduction()) {
            $response->setContent($this->responseForProduction($e));
        } else {
            $response->setContent($this->responseForDevelopment($e));
        }

        return $response;
    }

    /**
     * Render an exception to the console.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  \Exception  $e
     * @return void
     */
    public function renderForConsole($output, Throwable $e)
    {
        if (!$this->aether->isProduction()) {
            $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        }

        (new ConsoleApplication)->renderThrowable($e, $output);
    }

    /**
     * Add global context that will be added to all reported exceptions.
     *
     * @param  array  $context
     * @return void
     */
    public function addContext(array $context)
    {
        $this->context = array_merge($this->context, $context);
    }

    /**
     * Return a unique ID corresponding to the previously caught exception.
     *
     * @return string
     */
    protected function getLastReportedId()
    {
        return $this->lastReportedId;
    }

    protected function responseForProduction(Throwable $e)
    {
        $id = $this->getLastReportedId();

        return "<h1>Noe gikk galt</h1>" . ($id ? "<p>ID: <code>{$id}</code></p>" : "");
    }

    protected function responseForDevelopment(Throwable $e)
    {
        return tap(new Whoops, function ($whoops) {
            $whoops->pushHandler($this->getWhoopsHandler());

            $whoops->writeToOutput(false);

            $whoops->allowQuit(false);
        })->handleException($e);
    }

    protected function getWhoopsHandler()
    {
        return tap(new PrettyPageHandler, function ($handler) {
            $handler->handleUnconditionally(true);
            if ($mask = config('app.mask.env')) {
                foreach ($mask as $m) {
                    $handler->blacklist('_ENV', $m);
                    $handler->blacklist('_SERVER', $m);
                }
            }
        });
    }

    protected function reportInDevelopment(Throwable $e)
    {
        // todo: write to some log file?
    }

    protected function reportInProduction(Throwable $e)
    {
        Sentry\configureScope(function (Sentry\State\Scope $scope) use ($e): void {
            if ($e instanceof FatalThrowableError) {
                $scope->setLevel(Sentry\Severity::fatal());
            }
            $this->lastReportedId = Sentry\captureException($e);
        });
    }

    protected function getContext()
    {
        return $this->context;
    }
}
