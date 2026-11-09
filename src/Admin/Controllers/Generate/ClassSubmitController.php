<?php

declare(strict_types=1);

namespace PhpOnWheels\Admin\Controllers\Generate;

use InvalidArgumentException;
use PhpOnWheels\Admin\ProjectPaths;
use PhpOnWheels\Console\ClassMaker;
use PhpOnWheels\Console\Lifetime;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Http\Status;
use PhpOnWheels\Routing\Handler;
use PhpOnWheels\Security\Csrf;
use PhpOnWheels\Session\Session;
use PhpOnWheels\View\TemplateEngine;
use RuntimeException;

/**
 * Runs `ClassMaker`, the same one `pow make:class` calls, and renders the
 * form's own template again with either the result or the error — never a
 * redirect. A generator's output is a set of paths and snippets meant to be
 * read and copied; a flash-and-redirect would show one line and lose the
 * rest the moment the page was refreshed.
 */
final class ClassSubmitController implements Handler
{
    public function __construct(
        private readonly TemplateEngine $view,
        private readonly ProjectPaths $paths,
        private readonly Session $session,
    ) {
    }

    public function handle(ServerRequest $request): Response
    {
        $name = trim($request->form('name') ?? '');
        $lifetime = Lifetime::tryFrom($request->form('lifetime') ?? '') ?? Lifetime::Autowired;
        $force = $request->form('force') === '1';

        $old = ['name' => $name, 'lifetime' => $lifetime->value];

        try {
            $result = (new ClassMaker($this->paths->root))->create($name, $lifetime, $force);
        } catch (InvalidArgumentException | RuntimeException $e) {
            return $this->view->respond('generate/class', [
                'title' => 'Generate a class',
                'subtitle' => 'Writes a plain class under App\, the same as pow make:class.',
                'currentPath' => '/generate/class',
                'csrfToken' => Csrf::token($this->session),
                'lifetimes' => Lifetime::cases(),
                'old' => $old,
                'result' => null,
                'error' => $e->getMessage(),
            ], Status::UnprocessableEntity);
        }

        return $this->view->respond('generate/class', [
            'title' => 'Generate a class',
            'subtitle' => 'Writes a plain class under App\, the same as pow make:class.',
            'currentPath' => '/generate/class',
            'csrfToken' => Csrf::token($this->session),
            'lifetimes' => Lifetime::cases(),
            // Cleared rather than kept: the class that was just written can't
            // be written again, so a repeat submission is never useful.
            'old' => ['name' => '', 'lifetime' => $lifetime->value],
            'result' => $result,
            'error' => null,
        ]);
    }
}
