<?php

declare(strict_types=1);

namespace App\Controllers;

use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;
use PhpOnWheels\View\TemplateEngine;

/**
 * Reflects a path segment back into the page.
 *
 * Deliberately the shape of a reflected-XSS bug: try
 * `/hello/<script>alert(1)</script>` and read the source. The template escapes
 * it because `{{ }}` is the default, not because this controller remembered to.
 */
final class HelloController implements Handler
{
    public function __construct(
        private readonly TemplateEngine $view,
    ) {
    }

    public function handle(ServerRequest $request): Response
    {
        $name = $request->attribute('name') ?? 'world';

        return $this->view->respond('hello', [
            'title' => 'Hello',
            // Lets the layout mark the active navigation item.
            'currentPath' => '/hello',
            'name' => $name,
        ]);
    }
}
