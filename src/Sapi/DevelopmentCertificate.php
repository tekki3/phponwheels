<?php

declare(strict_types=1);

namespace PhpOnWheels\Sapi;

use OpenSSLAsymmetricKey;
use OpenSSLCertificate;
use OpenSSLCertificateSigningRequest;
use RuntimeException;

final class DevelopmentCertificate
{
    public static function ensure(string $directory): string
    {
        if (!extension_loaded('openssl')) {
            throw new RuntimeException('HTTPS requires the PHP OpenSSL extension.');
        }

        if (!is_dir($directory) && !@mkdir($directory, 0o700, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Could not create certificate directory "%s".', $directory));
        }

        $path = $directory . DIRECTORY_SEPARATOR . 'localhost.pem';

        if (is_file($path) && self::isUsable($path)) {
            return $path;
        }

        $configPath = tempnam(sys_get_temp_dir(), 'phponwheels-openssl-');
        if ($configPath === false) {
            throw new RuntimeException('Could not create a temporary OpenSSL configuration file.');
        }

        try {
            $config = <<<'CONF'
[req]
prompt = no
distinguished_name = dn
req_extensions = v3_req
[dn]
CN = localhost
[v3_req]
basicConstraints = CA:FALSE
keyUsage = digitalSignature, keyEncipherment
extendedKeyUsage = serverAuth
subjectAltName = @alt_names
[alt_names]
DNS.1 = localhost
IP.1 = 127.0.0.1
IP.2 = ::1
CONF;

            if (file_put_contents($configPath, $config) === false) {
                throw new RuntimeException('Could not write the temporary OpenSSL configuration file.');
            }

            self::clearOpenSslErrors();
            $key = self::asymmetricKey(openssl_pkey_new([
                'config' => $configPath,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
                'private_key_bits' => 2048,
            ]));

            $requestKey = $key;
            $request = openssl_csr_new(
                ['commonName' => 'localhost'],
                $requestKey,
                ['config' => $configPath, 'req_extensions' => 'v3_req'],
            );

            if (!($request instanceof OpenSSLCertificateSigningRequest)) {
                throw self::generationFailed();
            }

            $certificate = openssl_csr_sign(
                $request,
                null,
                $key,
                3650,
                ['config' => $configPath, 'x509_extensions' => 'v3_req'],
            );

            if (!($certificate instanceof OpenSSLCertificate)) {
                throw self::generationFailed();
            }

            $privateKeyPem = '';
            if (
                !openssl_pkey_export($key, $privateKeyPem, null, ['config' => $configPath])
                || !is_string($privateKeyPem)
            ) {
                throw self::generationFailed();
            }

            $certificatePem = '';
            if (!openssl_x509_export($certificate, $certificatePem) || !is_string($certificatePem)) {
                throw self::generationFailed();
            }

            $temporaryCertificate = tempnam($directory, 'phponwheels-cert-');
            if ($temporaryCertificate === false) {
                throw new RuntimeException('Could not create the local HTTPS certificate file.');
            }

            try {
                if (file_put_contents($temporaryCertificate, $privateKeyPem . $certificatePem, LOCK_EX) === false) {
                    throw new RuntimeException('Could not write the local HTTPS certificate file.');
                }

                if (is_file($path) && !@unlink($path)) {
                    throw new RuntimeException(sprintf('Could not replace the certificate file "%s".', $path));
                }

                if (!@rename($temporaryCertificate, $path)) {
                    throw new RuntimeException(sprintf('Could not install the certificate file "%s".', $path));
                }
            } finally {
                if (is_file($temporaryCertificate)) {
                    @unlink($temporaryCertificate);
                }
            }

            return $path;
        } finally {
            @unlink($configPath);
        }
    }

    private static function isUsable(string $path): bool
    {
        $pem = file_get_contents($path);
        if ($pem === false) {
            return false;
        }

        $certificate = openssl_x509_read($pem);
        $privateKey = openssl_pkey_get_private($pem);

        if (
            !($certificate instanceof OpenSSLCertificate)
            || !($privateKey instanceof OpenSSLAsymmetricKey)
            || !openssl_x509_check_private_key($certificate, $privateKey)
        ) {
            return false;
        }

        $details = openssl_x509_parse($certificate);

        return is_array($details)
            && is_int($details['validTo_time_t'] ?? null)
            && $details['validTo_time_t'] > time();
    }

    private static function generationFailed(): RuntimeException
    {
        $error = null;
        while (($next = openssl_error_string()) !== false) {
            $error = $next;
        }

        return new RuntimeException('Could not generate a local HTTPS certificate' . ($error === null ? '.' : ': ' . $error));
    }

    private static function asymmetricKey(mixed $value): OpenSSLAsymmetricKey
    {
        if (!($value instanceof OpenSSLAsymmetricKey)) {
            throw self::generationFailed();
        }

        return $value;
    }

    private static function clearOpenSslErrors(): void
    {
        while (openssl_error_string() !== false) {
        }
    }
}
