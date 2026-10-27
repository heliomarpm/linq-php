<?php

declare(strict_types=1);

namespace HeliomarPM\LinqPHP\Demo;

use RuntimeException;

final class CodeSandboxValidator
{
    private const BLOCKED_VARIABLES = [
        '$_ENV',
        '$_SERVER',
        '$_SESSION',
        '$_COOKIE',
        '$_FILES',
        '$_REQUEST',
    ];

    private const BLOCKED_FUNCTIONS = [
        // Execução de comandos do sistema operacional
        'exec', 'shell_exec', 'system', 'passthru', 'proc_open', 'proc_close',
        'proc_terminate', 'proc_get_status', 'popen', 'pclose', 'pcntl_exec',
        'pcntl_fork',

        // Acesso e manipulação do sistema de arquivos
        'file_get_contents', 'file_put_contents', 'readfile', 'file', 'fopen',
        'unlink', 'rmdir', 'mkdir', 'rename', 'copy', 'touch', 'chmod', 'chown',
        'scandir', 'glob', 'symlink', 'link', 'tempnam', 'tmpfile', 'readlink',

        // Ambiente e runtime do PHP
        'getenv', 'putenv', 'apache_getenv', 'apache_setenv', 'phpinfo',
        'ini_set', 'ini_get', 'ini_restore', 'ini_alter', 'get_cfg_var',
        'dl', 'extension_loaded',

        // Rede e sockets
        'curl_init', 'curl_exec', 'fsockopen', 'pfsockopen', 'socket_create',
        'stream_socket_client',

        // Avaliação dinâmica e inclusão de arquivos
        'eval', 'assert', 'create_function', 'include', 'include_once',
        'require', 'require_once',

        // Reflexão perigosa
        'reflectionclass', 'reflectionfunction', 'reflectionmethod', 'reflectionobject',
    ];

    /**
     * Valida o código PHP via análise léxica (token_get_all) antes de qualquer execução dinâmica.
     *
     * @throws RuntimeException Caso identifique tokens, funções, superglobais ou construções perigosas.
     */
    public static function assertSafe(string $code): void
    {
        // 1. Bloquear operador backtick de execução shell: `comando`
        if (str_contains($code, '`')) {
            throw new RuntimeException('Operador de execução de sistema (backtick `) não é permitido na sandbox.');
        }

        $tokens = token_get_all("<?php\n" . $code);
        $blockedFuncs = array_flip(self::BLOCKED_FUNCTIONS);
        $blockedVars = array_flip(self::BLOCKED_VARIABLES);

        foreach ($tokens as $token) {
            if (!is_array($token)) {
                continue;
            }

            [$tokenId, $tokenText] = $token;

            // Bloquear fechamento de tag PHP para evitar injeção de HTML / XSS
            if ($tokenId === T_CLOSE_TAG || $tokenId === T_INLINE_HTML) {
                if (trim($tokenText) !== '') {
                    throw new RuntimeException('Fechamento de tag PHP ou saída HTML direta não é permitida na sandbox.');
                }
            }

            // Bloquear instruções de include, require e eval embutido
            if (in_array($tokenId, [T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE, T_EVAL], true)) {
                throw new RuntimeException("Instrução dinâmica '{$tokenText}' não é permitida na sandbox.");
            }

            // Bloquear superglobais sensíveis ($_SERVER, $_ENV, etc.)
            if ($tokenId === T_VARIABLE) {
                if (isset($blockedVars[$tokenText])) {
                    throw new RuntimeException("Acesso à variável superglobal '{$tokenText}' não é permitido na sandbox.");
                }
            }

            // Bloquear chamadas ou menções a funções e classes do sistema
            if ($tokenId === T_STRING) {
                $identifierLower = strtolower($tokenText);
                if (isset($blockedFuncs[$identifierLower])) {
                    throw new RuntimeException("A chamada/referência a '{$tokenText}' foi bloqueada por razões de segurança.");
                }
            }
        }
    }
}
