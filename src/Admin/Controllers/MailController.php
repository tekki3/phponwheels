<?php

declare(strict_types=1);

namespace PhpOnWheels\Admin\Controllers;

use PhpOnWheels\Database\QueryFailed;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Mail\MailLogRepository;
use PhpOnWheels\Mail\MailStatus;
use PhpOnWheels\Routing\Handler;
use PhpOnWheels\Security\Csrf;
use PhpOnWheels\Session\Session;
use PhpOnWheels\View\TemplateEngine;

final class MailController implements Handler
{
    public function __construct(
        private readonly TemplateEngine $view,
        private readonly MailLogRepository $mail,
        private readonly Session $session,
    ) {
    }

    public function handle(ServerRequest $request): Response
    {
        $status = MailStatus::tryFrom($request->uri->queryParam('status') ?? '');

        try {
            $entries = $this->mail->list($status, limit: 100);
            $pendingCount = $this->mail->count(MailStatus::Pending);
            $available = true;
        } catch (QueryFailed) {
            $entries = [];
            $pendingCount = 0;
            $available = false;
        }

        return $this->view->respond('mail', [
            'title' => 'Mail',
            'subtitle' => 'Every message sent through this project, logged in mail_log — resend '
                . 'anything that failed.',
            'currentPath' => '/mail',
            'csrfToken' => Csrf::token($this->session),
            'entries' => $entries,
            'pendingCount' => $pendingCount,
            'status' => $status,
            'available' => $available,
            'flash' => $this->session->takeFlash('admin.notice'),
            'error' => $this->session->takeFlash('admin.error'),
        ]);
    }
}
