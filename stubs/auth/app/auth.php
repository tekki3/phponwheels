<?php

declare(strict_types=1);

use App\AuthKit\Accounts;
use App\AuthKit\AuthEmails;
use App\AuthKit\Registration;
use PhpOnWheels\Api\RateLimiter;
use PhpOnWheels\Api\Tokens;
use PhpOnWheels\Crypto\Key;
use PhpOnWheels\Auth\Authenticator;
use PhpOnWheels\Auth\EmailTokens;
use PhpOnWheels\Auth\Identity;
use PhpOnWheels\Auth\LoginThrottle;
use PhpOnWheels\Auth\Mfa;
use PhpOnWheels\Auth\Oidc\CurlOidcHttp;
use PhpOnWheels\Auth\Oidc\OidcHttp;
use PhpOnWheels\Auth\Oidc\OidcProviders;
use PhpOnWheels\Auth\PasswordHasher;
use PhpOnWheels\Auth\SecurityState;
use PhpOnWheels\Auth\UserProvider;
use PhpOnWheels\Config\Environment;
use PhpOnWheels\Container\RequestScope;
use PhpOnWheels\Container\Container;
use PhpOnWheels\Crypto\CryptoFactory;
use PhpOnWheels\Crypto\Encrypter;
use PhpOnWheels\Database\Connection;
use PhpOnWheels\Kernel\Blueprint;
use PhpOnWheels\Log\Logger;
use PhpOnWheels\Mail\MailerFactory;
use PhpOnWheels\Session\Session;

return static function (Blueprint $app, Environment $env, string $root): void {
    $app->container->singleton(PasswordHasher::class, static fn (): PasswordHasher => new PasswordHasher());
    $app->container->singleton(SecurityState::class, static fn (Container $c): SecurityState => new SecurityState($c->get(Connection::class)));
    $app->container->singleton(Accounts::class, static fn (Container $c): Accounts => new Accounts(
        $c->get(Connection::class), $c->get(SecurityState::class),
    ));
    $app->container->singleton(UserProvider::class, static fn (Container $c): UserProvider => $c->get(Accounts::class));
    $app->container->singleton(RateLimiter::class, static fn (Container $c): RateLimiter => new RateLimiter($c->get(Connection::class)));
    $app->container->singleton(Tokens::class, static fn (Container $c): Tokens => new Tokens(
        $c->get(Connection::class), $c->get(UserProvider::class), $c->get(SecurityState::class),
        static fn (): Key => Key::fromString($env->required('APP_KEY')),
        $env->string('API_JWT_ISSUER', $env->string('APP_URL', 'http://localhost:8080')),
        $env->string('API_JWT_AUDIENCE', 'phponwheels-api'),
    ));
    $app->container->singleton(LoginThrottle::class, static fn (Container $c): LoginThrottle => new LoginThrottle(
        $c->get(Connection::class), $env->int('LOGIN_MAX_ATTEMPTS', 5), $env->int('LOGIN_WINDOW_SECONDS', 900),
    ));
    $app->container->singleton(Mfa::class, static fn (Container $c): Mfa => new Mfa(
        $c->get(Connection::class), $c->get(SecurityState::class),
        static fn (): Encrypter => CryptoFactory::encrypterFromEnvironment($env),
    ));
    $app->container->singleton(OidcProviders::class, static fn (): OidcProviders => OidcProviders::fromEnvironment($env));
    $app->container->singleton(OidcHttp::class, static fn (): OidcHttp => new CurlOidcHttp());
    $app->container->scoped(Authenticator::class, static fn (RequestScope $scope): Authenticator => new Authenticator(
        $scope->get(Session::class), $scope->get(UserProvider::class), $scope->get(PasswordHasher::class),
        static fn (Identity $user): string => hash('sha256', $user->passwordHash() . ':' . $scope->get(SecurityState::class)->version($user->authIdentifier())),
    ));
    $app->container->scoped(AuthEmails::class, static fn (RequestScope $scope): AuthEmails => new AuthEmails(
        $scope->get(EmailTokens::class), $scope->get(UserProvider::class), $scope->get(LoginThrottle::class),
        MailerFactory::fromEnvironment($env), $env, $scope->get(Logger::class),
    ));
    $app->container->scoped(Registration::class, static fn (RequestScope $scope): Registration => new Registration(
        $scope->get(Connection::class), $scope->get(PasswordHasher::class), $scope->get(LoginThrottle::class),
        MailerFactory::fromEnvironment($env), $env, $scope->get(Logger::class),
    ));
    $app->loadRoutes($root . '/app/auth-routes.php');
};
