<?php

declare(strict_types=1);

namespace PhpOnWheels\Admin\Controllers\Generate;

use InvalidArgumentException;
use PhpOnWheels\Admin\ProjectPaths;
use PhpOnWheels\Console\ModelMaker;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Http\Status;
use PhpOnWheels\Routing\Handler;
use PhpOnWheels\Security\Csrf;
use PhpOnWheels\Session\Session;
use PhpOnWheels\View\TemplateEngine;
use RuntimeException;

final class ModelSubmitController implements Handler
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
        $table = trim($request->form('table') ?? '');
        $fields = trim($request->form('fields') ?? '');
        $force = $request->form('force') === '1';
        $old = ['name' => $name, 'table' => $table, 'fields' => $fields];

        try {
            $result = (new ModelMaker($this->paths->root))->create(
                $name,
                table: $table === '' ? null : $table,
                fields: $fields,
                force: $force,
            );
        } catch (InvalidArgumentException | RuntimeException $e) {
            return $this->view->respond('generate/model', [
                'title' => 'Generate a model',
                'subtitle' => 'Writes a typed Model subclass, the same as pow make:model.',
                'currentPath' => '/generate/model',
                'csrfToken' => Csrf::token($this->session),
                'old' => $old,
                'result' => null,
                'error' => $e->getMessage(),
            ], Status::UnprocessableEntity);
        }

        return $this->view->respond('generate/model', [
            'title' => 'Generate a model',
            'subtitle' => 'Writes a typed Model subclass, the same as pow make:model.',
            'currentPath' => '/generate/model',
            'csrfToken' => Csrf::token($this->session),
            'old' => ['name' => '', 'table' => $table, 'fields' => $fields],
            'result' => $result,
            'error' => null,
        ]);
    }
}
