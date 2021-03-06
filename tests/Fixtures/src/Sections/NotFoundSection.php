<?php

namespace Tests\Fixtures\Sections;

use Aether\Response\Text;
use Aether\Sections\Section;

class NotFoundSection extends Section
{
    public function response()
    {
        http_response_code(404);

        return new Text('404 Eg fant han ikkje', 'text/html');
    }
}
