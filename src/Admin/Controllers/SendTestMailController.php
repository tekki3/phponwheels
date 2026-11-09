<?php

declare(strict_types=1);

namespace PhpOnWheels\Admin\Controllers;

use InvalidArgumentException;
use PhpOnWheels\Config\Environment;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Http\Status;
use PhpOnWheels\Mail\MailFailed;
use PhpOnWheels\Mail\Message;
use PhpOnWheels\Mail\PersistingMailer;
use PhpOnWheels\Routing\Handler;
use PhpOnWheels\Security\Csrf;
use PhpOnWheels\Session\Session;
use PhpOnWheels\View\TemplateEngine;

/**
 * The same as `pow mail:test`: one message through the configured driver,
 * or into mail_log as pending when MAIL_QUEUE is enabled.
 */
final class SendTestMailController implements Handler
{
    public function __construct(
        private readonly TemplateEngine $view,
        private readonly Environment $env,
        private readonly PersistingMailer $mailer,
        private readonly Session $session,
    ) {
    }

    public function handle(ServerRequest $request): Response
    {
        $to = trim($request->form('to') ?? '');
        $from = trim($request->form('from') ?? '') ?: ($this->env->raw('MAIL_FROM_ADDRESS') ?? '');
        $driver = $this->env->string('MAIL_DRIVER', 'array');
        $queued = $this->mailer->queuesMessages();

        $base = [
            'title' => 'Tools',
            'subtitle' => 'pow key:generate and pow mail:test, without leaving the browser.',
            'currentPath' => '/tools',
            'csrfToken' => Csrf::token($this->session),
            'mailDriver' => $driver,
            'defaultFrom' => $this->env->raw('MAIL_FROM_ADDRESS') ?? '',
            'generatedKey' => null,
            'mailTo' => $to,
            'mailFrom' => $request->form('from') ?? '',
        ];

        if ($from === '') {
            return $this->view->respond('tools', [
                ...$base,
                'mailResult' => null,
                'error' => 'No sender configured. Enter one, or set MAIL_FROM_ADDRESS in .env.',
            ], Status::UnprocessableEntity);
        }

        try {
            $message = Message::to($to)
                ->from($from)
                ->subject('phponwheels test message')
                ->text($queued
                    ? sprintf(
                        "Queued from the admin UI at %s, through the %s driver.\n\n"
                        . "Run `pow mail:send-pending` to deliver this message.",
                        gmdate('c'),
                        $driver,
                    )
                    : sprintf(
                        "Sent from the admin UI at %s, through the %s driver.\n\n"
                        . "If this arrived, MAIL_DRIVER=%s is delivering mail correctly.",
                        gmdate('c'),
                        $driver,
                        $driver,
                    ));
        } catch (InvalidArgumentException $e) {
            return $this->view->respond('tools', [
                ...$base,
                'mailResult' => null,
                'error' => $e->getMessage(),
            ], Status::UnprocessableEntity);
        }

        try {
            $this->mailer->send($message);
        } catch (MailFailed $e) {
            return $this->view->respond('tools', [
                ...$base,
                'mailResult' => null,
                'error' => sprintf('Send failed (driver: %s): %s', $driver, $e->getMessage()),
            ], Status::UnprocessableEntity);
        }

        $result = $queued
            ? sprintf('Queued for delivery to %s. Run pow mail:send-pending to deliver it.', $to)
            : (in_array($driver, ['array', 'memory', 'null'], true)
                ? sprintf('Accepted by the "%s" driver — nothing left this machine.', $driver)
                : sprintf('Sent to %s via %s.', $to, $driver));

        return $this->view->respond('tools', [
            ...$base,
            'mailResult' => $result,
            'error' => null,
        ]);
    }
}
