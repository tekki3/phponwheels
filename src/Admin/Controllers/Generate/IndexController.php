<?php

declare(strict_types=1);

namespace PhpOnWheels\Admin\Controllers\Generate;

use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;
use PhpOnWheels\View\TemplateEngine;

final class IndexController implements Handler
{
    public function __construct(
        private readonly TemplateEngine $view,
    ) {
    }

    public function handle(ServerRequest $request): Response
    {
        return $this->view->respond('generate/index', [
            'title' => 'Generate',
            'subtitle' => 'One form per pow make:* command — writes real files into this project.',
            'currentPath' => '/generate',
        ]);
    }
}
