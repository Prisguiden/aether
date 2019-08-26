<?php

namespace Aether\Providers;

use Aether\Localization;

class LocalizationProvider extends Provider
{
    public function boot()
    {
        $this->aether->instance('localization', new Localization(
            config('locale'),
            $this->aether['projectRoot'] . 'locale'
        ));
    }
}
