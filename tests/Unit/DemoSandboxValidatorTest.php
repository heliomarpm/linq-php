<?php

declare(strict_types=1);

namespace Tests\Unit;

use HeliomarPM\LinqPHP\Demo\CodeSandboxValidator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

require_once __DIR__ . '/../../demo/sandbox_validator.php';

final class DemoSandboxValidatorTest extends TestCase
{
    public function testAllowsSafeLinqExpressions(): void
    {
        $safeCodes = [
            'LinqPHP::from($vendas)->where(["status", "=", "concluido"])->toArray();',
            '$result = LinqPHP::from($produtos)->select(["id" => "codigo", "nome"])->toObject();',
            'LinqPHP::from($vendas)->groupBy(["status"], ["sum" => ["valor"]])->toArray();',
            'LinqPHP::from($vendas)->join($produtos, ["produto_id" => "id_produto"])->toArray();',
            '$taxa = round(number_format(100.5, 2), 1);',
            'sprintf("Total: %d", count($vendas));',
            'in_array("concluido", ["aberto", "concluido"], true);',
        ];

        foreach ($safeCodes as $code) {
            CodeSandboxValidator::assertSafe($code);
            $this->addToAssertionCount(1);
        }
    }

    public function testBlocksSystemExecutionCommands(): void
    {
        $dangerousCommands = [
            'exec("whoami");',
            'shell_exec("cat /etc/passwd");',
            'system("ls -la");',
            'passthru("id");',
            'proc_open("uptime", [], $pipes);',
            'popen("date", "r");',
        ];

        foreach ($dangerousCommands as $cmd) {
            try {
                CodeSandboxValidator::assertSafe($cmd);
                $this->fail("Esperava bloqueio de segurança para: {$cmd}");
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('bloqueada por razões de segurança', $e->getMessage());
            }
        }
    }

    public function testBlocksBacktickExecution(): void
    {
        $code = '$out = `ls -la`;';
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Operador de execução de sistema (backtick `) não é permitido');
        CodeSandboxValidator::assertSafe($code);
    }

    public function testBlocksFileSystemOperations(): void
    {
        $fileOperations = [
            'file_get_contents("/etc/hosts");',
            'file_put_contents("/tmp/exploit.txt", "evil");',
            'unlink("src/LinqPHP.php");',
            'rmdir("demo");',
            'fopen("demo/index.php", "r");',
            'readfile(".env");',
            'scandir("/");',
        ];

        foreach ($fileOperations as $op) {
            try {
                CodeSandboxValidator::assertSafe($op);
                $this->fail("Esperava bloqueio de segurança para: {$op}");
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('bloqueada por razões de segurança', $e->getMessage());
            }
        }
    }

    public function testBlocksEnvironmentInspection(): void
    {
        $envOperations = [
            'getenv("DATABASE_URL");',
            'putenv("FOO=BAR");',
            'phpinfo();',
            'ini_set("display_errors", "1");',
        ];

        foreach ($envOperations as $op) {
            try {
                CodeSandboxValidator::assertSafe($op);
                $this->fail("Esperava bloqueio de segurança para: {$op}");
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('bloqueada por razões de segurança', $e->getMessage());
            }
        }
    }

    public function testBlocksDynamicEvaluationsAndInclusions(): void
    {
        $dynamicOperations = [
            'eval("echo 1;");',
            'assert(true);',
            'include "secret.php";',
            'require_once "secret.php";',
        ];

        foreach ($dynamicOperations as $op) {
            try {
                CodeSandboxValidator::assertSafe($op);
                $this->fail("Esperava bloqueio de segurança para: {$op}");
            } catch (RuntimeException $e) {
                $this->assertMatchesRegularExpression('/(bloqueada por razões de segurança|não é permitida na sandbox)/', $e->getMessage());
            }
        }
    }

    public function testBlocksSuperglobalsAccess(): void
    {
        $superglobals = [
            'return $_SERVER;',
            'return $_ENV["SECRET"];',
            'return $_COOKIE;',
            'return $_SESSION;',
            'return $_FILES;',
        ];

        foreach ($superglobals as $sg) {
            try {
                CodeSandboxValidator::assertSafe($sg);
                $this->fail("Esperava bloqueio de superglobal para: {$sg}");
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('variável superglobal', $e->getMessage());
            }
        }
    }

    public function testBlocksReflectionClasses(): void
    {
        $reflections = [
            'new ReflectionClass("PDO");',
            'new ReflectionFunction("phpinfo");',
        ];

        foreach ($reflections as $ref) {
            try {
                CodeSandboxValidator::assertSafe($ref);
                $this->fail("Esperava bloqueio de reflexão para: {$ref}");
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('bloqueada por razões de segurança', $e->getMessage());
            }
        }
    }

    public function testBlocksPhpClosingTagAndInlineHtml(): void
    {
        $inlineHtml = '?> <h1>Injected HTML</h1> <?php';
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Fechamento de tag PHP ou saída HTML direta não é permitida');
        CodeSandboxValidator::assertSafe($inlineHtml);
    }
}
