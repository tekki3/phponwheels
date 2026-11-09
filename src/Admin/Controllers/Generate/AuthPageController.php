<?php

declare(strict_types=1);

namespace PhpOnWheels\Admin\Controllers\Generate;

use PhpOnWheels\Console\AuthMaker;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;
use PhpOnWheels\Security\Csrf;
use PhpOnWheels\Session\Session;
use PhpOnWheels\View\TemplateEngine;

final class AuthPageController implements Handler
{
    public function __construct(private readonly TemplateEngine $view, private readonly Session $session)
    {
    }

    public function handle(ServerRequest $request): Response
    {
        return $this->view->respond('generate/auth', [
            'title' => 'Generate authentication', 'subtitle' => 'Login, verified registration, MFA and user management.',
            'currentPath' => '/generate/auth', 'csrfToken' => Csrf::token($this->session), 'files' => null, 'error' => null,
            'wiring' => AuthMaker::WIRING,
        ]);
    }
}
