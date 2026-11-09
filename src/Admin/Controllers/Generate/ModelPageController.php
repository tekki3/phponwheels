<?php

declare(strict_types=1);

namespace PhpOnWheels\Admin\Controllers\Generate;

use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;
use PhpOnWheels\Security\Csrf;
use PhpOnWheels\Session\Session;
use PhpOnWheels\View\TemplateEngine;

final class ModelPageController implements Handler
{
    public function __construct(
        private readonly TemplateEngine $view,
        private readonly Session $session,
    ) {
    }

    public function handle(ServerRequest $request): Response
    {
        return $this->view->respond('generate/model', [
            'title' => 'Generate a model',
            'subtitle' => 'Writes a typed Model subclass, the same as pow make:model.',
            'currentPath' => '/generate/model',
            'csrfToken' => Csrf::token($this->session),
            'old' => ['name' => '', 'table' => '', 'fields' => ''],
            'result' => null,
            'error' => null,
        ]);
    }
}
