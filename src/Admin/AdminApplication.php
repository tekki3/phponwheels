<?php

declare(strict_types=1);

namespace PhpOnWheels\Admin;

use Closure;
use PhpOnWheels\Admin\Controllers\Generate\AuthPageController;
use PhpOnWheels\Admin\Controllers\Generate\AuthSubmitController;
use PhpOnWheels\Admin\Controllers\ClearStorageController;
use PhpOnWheels\Admin\Controllers\GcSessionsController;
use PhpOnWheels\Admin\Controllers\Generate\ClassPageController;
use PhpOnWheels\Admin\Controllers\Generate\ClassSubmitController;
use PhpOnWheels\Admin\Controllers\Generate\ControllerPageController;
use PhpOnWheels\Admin\Controllers\Generate\ControllerSubmitController;
use PhpOnWheels\Admin\Controllers\Generate\FormPageController;
use PhpOnWheels\Admin\Controllers\Generate\FormSubmitController;
use PhpOnWheels\Admin\Controllers\Generate\IndexController as GenerateIndexController;
use PhpOnWheels\Admin\Controllers\Generate\MiddlewarePageController;
use PhpOnWheels\Admin\Controllers\Generate\MiddlewareSubmitController;
use PhpOnWheels\Admin\Controllers\Generate\MigrationPageController;
use PhpOnWheels\Admin\Controllers\Generate\MigrationSubmitController;
use PhpOnWheels\Admin\Controllers\Generate\ModelPageController;
use PhpOnWheels\Admin\Controllers\Generate\ModelSubmitController;
use PhpOnWheels\Admin\Controllers\Generate\SeedPageController;
use PhpOnWheels\Admin\Controllers\Generate\SeedSubmitController;
use PhpOnWheels\Admin\Controllers\GenerateKeyController;
use PhpOnWheels\Admin\Controllers\MailController;
use PhpOnWheels\Admin\Controllers\MigrationsController;
use PhpOnWheels\Admin\Controllers\OverviewController;
use PhpOnWheels\Admin\Controllers\RunPendingMailController;
use PhpOnWheels\Admin\Controllers\RunSeedersController;
use PhpOnWheels\Admin\Controllers\ResendFailedMailController;
use PhpOnWheels\Admin\Controllers\ResendMailController;
use PhpOnWheels\Admin\Controllers\RollbackMigrationsController;
use PhpOnWheels\Admin\Controllers\RoutesController;
use PhpOnWheels\Admin\Controllers\RunMigrationsController;
use PhpOnWheels\Admin\Controllers\SeedersController;
use PhpOnWheels\Admin\Controllers\SendTestMailController;
use PhpOnWheels\Admin\Controllers\SessionsController;
use PhpOnWheels\Admin\Controllers\StorageController;
use PhpOnWheels\Admin\Controllers\ToolsController;
use PhpOnWheels\Config\Environment;
use PhpOnWheels\Database\Connection;
use PhpOnWheels\Database\DatabaseSettings;
use PhpOnWheels\Database\Migrator;
use PhpOnWheels\Database\TransactionGuard;
use PhpOnWheels\Kernel\Application;
use PhpOnWheels\Kernel\Blueprint;
use PhpOnWheels\Log\Level;
use PhpOnWheels\Log\LogRequests;
use PhpOnWheels\Log\Logger;
use PhpOnWheels\Log\LoggerFactory;
use PhpOnWheels\Mail\Mailer;
use PhpOnWheels\Mail\MailerFactory;
use PhpOnWheels\Mail\MailLogRepository;
use PhpOnWheels\Mail\PersistingMailer;
use PhpOnWheels\Middleware\ServeStaticFiles;
use PhpOnWheels\Routing\Route;
use PhpOnWheels\Routing\RouteCollection;
use PhpOnWheels\Security\CsrfMiddleware;
use PhpOnWheels\Session\FileSessionStore;
use PhpOnWheels\Session\SessionMiddleware;
use PhpOnWheels\View\TemplateEngine;
use RuntimeException;

/**
 * Boots the admin dashboard — a second, self-contained application reading and
 * operating on the same project, never wired into `app/routes.php`.
 *
 * That separation is deliberate, not an oversight. Merging these routes into
 * the real route table would mean every deployed application ships a page that
 * can run migrations, resend mail and wipe the template cache unless a
 * developer remembers to strip it back out. Keeping it a second application,
 * started only by `pow ui`, means the capability exists exactly when someone
 * chose to run it and never otherwise.
 *
 * There is deliberately no login here. What stands in for it: `pow ui`
 * binds to `127.0.0.1` by default, the same as `pow serve`, and the CLI
 * warns loudly if told to bind anywhere else. Treat this the way you would a
 * database console left open on your own machine — no different, no less
 * dangerous, and not something to put behind a public host or port forward.
 */
final class AdminApplication
{
    public static function boot(string $root): Application
    {
        $env = Environment::load($root . '/.env');
        // Same rule the real application follows: the real environment wins
        // over .env, so `pow ui --debug` (which sets APP_DEBUG in the
        // process environment) behaves identically to APP_DEBUG=true in .env.
        $debug = $env->bool('APP_DEBUG', false);
        $storage = $root . '/storage';

        foreach (["$storage/sessions", "$storage/cache/admin-views"] as $directory) {
            if (!is_dir($directory) && !mkdir($directory, 0o750, true) && !is_dir($directory)) {
                throw new RuntimeException(sprintf('Cannot create storage directory "%s".', $directory));
            }
        }

        // The same database the project itself uses — administering a second,
        // disconnected copy would show stale numbers and resend mail nobody
        // could find afterwards.
        $database = Connection::connect(DatabaseSettings::fromEnvironment($env, $root));
        $logger = LoggerFactory::fromEnvironment($env, $root);

        $templates = new TemplateEngine(
            __DIR__ . '/templates',
            $storage . '/cache/admin-views',
            alwaysRecompile: $debug,
            shared: ['sapi' => PHP_SAPI, 'phpVersion' => PHP_VERSION],
        );

        // Honor the same deferred-delivery setting as the project application.
        $mailer = new PersistingMailer(
            MailerFactory::fromEnvironment($env),
            new MailLogRepository($database),
            $env->bool('MAIL_QUEUE', false),
        );

        return Application::boot(
            static function (Blueprint $app) use ($root, $storage, $database, $logger, $templates, $mailer, $env, $debug): void {
                $app->container->singleton(ProjectPaths::class, static fn (): ProjectPaths => new ProjectPaths($root));
                $app->container->singleton(Environment::class, static fn (): Environment => $env);
                $app->container->singleton(Connection::class, static fn (): Connection => $database);
                $app->container->singleton(Logger::class, static fn (): Logger => $logger);
                $app->container->singleton(TemplateEngine::class, static fn (): TemplateEngine => $templates);
                $app->container->singleton(
                    Migrator::class,
                    static fn (): Migrator => new Migrator($database, $root . '/database/migrations'),
                );
                $app->container->singleton(Mailer::class, static fn (): Mailer => $mailer);
                $app->container->singleton(PersistingMailer::class, static fn (): PersistingMailer => $mailer);

                // A distinct cookie name, not the project's SESSION_COOKIE: cookies
                // are scoped by host, not port, so `pow serve` and `pow ui`
                // running side by side on 127.0.0.1 would otherwise silently
                // share — and overwrite — one another's session cookie.
                $app->middleware(
                    new LogRequests($logger),
                    new ServeStaticFiles(__DIR__ . '/assets', maxAgeSeconds: $debug ? 0 : 3600, prefix: '/assets'),
                    new SessionMiddleware(
                        new FileSessionStore($storage . '/sessions'),
                        cookieName: 'phponwheels_admin_session',
                        lifetimeSeconds: $env->int('SESSION_LIFETIME', 7200),
                    ),
                    new CsrfMiddleware(),
                    new TransactionGuard(static function (string $message) use ($logger): void {
                        $logger->log(Level::Error, $message);
                    }),
                );

                $routes = $app->routes;

                $routes->get('/', OverviewController::class, 'admin.overview');

                $routes->get('/migrations', MigrationsController::class, 'admin.migrations');
                $routes->post('/migrations/run', RunMigrationsController::class, 'admin.migrations.run');
                $routes->post('/migrations/rollback', RollbackMigrationsController::class, 'admin.migrations.rollback');

                $routes->get('/mail', MailController::class, 'admin.mail');
                $routes->post('/mail/{id:\d+}/resend', ResendMailController::class, 'admin.mail.resend');
                $routes->post('/mail/resend-failed', ResendFailedMailController::class, 'admin.mail.resend-failed');
                $routes->post('/mail/send-pending', RunPendingMailController::class, 'admin.mail.send-pending');

                $routes->get('/routes', RoutesController::class, 'admin.routes');

                $routes->get('/seeders', SeedersController::class, 'admin.seeders');
                $routes->post('/seeders/run', RunSeedersController::class, 'admin.seeders.run');

                $routes->get('/sessions', SessionsController::class, 'admin.sessions');
                $routes->post('/sessions/gc', GcSessionsController::class, 'admin.sessions.gc');

                $routes->get('/storage', StorageController::class, 'admin.storage');
                $routes->post('/storage/clear', ClearStorageController::class, 'admin.storage.clear');

                $routes->get('/generate', GenerateIndexController::class, 'admin.generate');
                $routes->get('/generate/class', ClassPageController::class, 'admin.generate.class');
                $routes->get('/generate/auth', AuthPageController::class, 'admin.generate.auth');
                $routes->post('/generate/auth', AuthSubmitController::class, 'admin.generate.auth.submit');
                $routes->post('/generate/class', ClassSubmitController::class, 'admin.generate.class.submit');
                $routes->get('/generate/controller', ControllerPageController::class, 'admin.generate.controller');
                $routes->post('/generate/controller', ControllerSubmitController::class, 'admin.generate.controller.submit');
                $routes->get('/generate/form', FormPageController::class, 'admin.generate.form');
                $routes->post('/generate/form', FormSubmitController::class, 'admin.generate.form.submit');
                $routes->get('/generate/middleware', MiddlewarePageController::class, 'admin.generate.middleware');
                $routes->post('/generate/middleware', MiddlewareSubmitController::class, 'admin.generate.middleware.submit');
                $routes->get('/generate/migration', MigrationPageController::class, 'admin.generate.migration');
                $routes->post('/generate/migration', MigrationSubmitController::class, 'admin.generate.migration.submit');
                $routes->get('/generate/model', ModelPageController::class, 'admin.generate.model');
                $routes->post('/generate/model', ModelSubmitController::class, 'admin.generate.model.submit');
                $routes->get('/generate/seed', SeedPageController::class, 'admin.generate.seed');
                $routes->post('/generate/seed', SeedSubmitController::class, 'admin.generate.seed.submit');

                $routes->get('/tools', ToolsController::class, 'admin.tools');
                $routes->post('/tools/key', GenerateKeyController::class, 'admin.tools.key');
                $routes->post('/tools/mail-test', SendTestMailController::class, 'admin.tools.mail-test');
            },
            debug: $debug,
        );
    }

    /**
     * Reads the project's real route table without ever serving it — a fresh
     * RouteCollection is compiled from app/routes.php and thrown away, so the
     * admin app's own router never risks colliding with it.
     *
     * @return list<Route>
     */
    public static function projectRoutes(ProjectPaths $paths, bool $debug): array
    {
        $path = $paths->routesFile();

        if (!is_file($path)) {
            return [];
        }

        /** @var mixed $define */
        $define = require $path;

        if (!$define instanceof Closure) {
            return [];
        }

        $routes = new RouteCollection();
        $define($routes, $debug);
        \PhpOnWheels\Api\RouteFiles::register($routes, dirname($path) . '/api');

        return $routes->compile()->routes();
    }
}
