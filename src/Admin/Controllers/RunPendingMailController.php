<?php

declare(strict_types=1);

namespace PhpOnWheels\Admin\Controllers;

use PhpOnWheels\Database\QueryFailed;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Mail\MailFailed;
use PhpOnWheels\Mail\MailStatus;
use PhpOnWheels\Mail\PersistingMailer;
use PhpOnWheels\Routing\Handler;
use PhpOnWheels\Session\Session;

final class RunPendingMailController implements Handler
{
    public function __construct(
        private readonly PersistingMailer $mailer,
        private readonly Session $session,
    ) {
    }

    public function handle(ServerRequest $request): Response
    {
        try {
            $pending = $this->mailer->history(MailStatus::Pending, 50);
        } catch (QueryFailed) {
            $this->session->flash('admin.error', 'The mail_log table does not exist yet. Run pending migrations.');

            return Response::redirect('/mail');
        }

        $sent = 0;
        $failed = 0;

        foreach ($pending as $entry) {
            try {
                $this->mailer->deliverPending($entry->id);
                $sent++;
            } catch (MailFailed) {
                $failed++;
            }
        }

        $this->session->flash('admin.notice', sprintf('%d sent, %d failed.', $sent, $failed));

        return Response::redirect('/mail');
    }
}
