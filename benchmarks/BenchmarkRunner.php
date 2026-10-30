<?php

namespace Benchmarks;

class BenchmarkRunner
{
    private const NAME_WIDTH = 45;
    private const COL_WIDTH = 20;


    // 1. Defina as constantes de cor no topo da sua classe
    private const COLOR_RESET = "\e[0m";
    private const COLOR_BOLD = "\e[1m";
    private const COLOR_CYAN = "\e[36m";
    private const COLOR_GREEN = "\e[32m";
    private const COLOR_YELLOW = "\e[33m";
    private const COLOR_RED = "\e[31m";
    private const COLOR_DARK_GRAY = "\e[90m";
    private const BG_ZEBRA = "\e[48;5;236m";

    private array $githubAnnotations = [];

    public function run(array $suites, array $dataSizes): void
    {
        // Captura a versão do PHP
        $phpVersion = PHP_VERSION;

        // Verifica se a extensão do Xdebug está carregada
        $xdebugStatus = extension_loaded('xdebug') ? '✔' : '❌';

        // O OPcache no terminal depende de uma diretiva específica do CLI
        $opcacheLoaded = extension_loaded('Zend OPcache');
        $opcacheCliEnabled = filter_var(ini_get('opcache.enable_cli'), FILTER_VALIDATE_BOOLEAN);
        $opcacheStatus = ($opcacheLoaded && $opcacheCliEnabled) ? '✔' : '❌';

        // Imprime a linha formatada
        echo "Running benchmarks...\n";
        echo "with PHP version {$phpVersion}, xdebug {$xdebugStatus}, opcache {$opcacheStatus}\n\n";

        $this->printHeader($dataSizes);

        foreach ($suites as $index => $suite) {
            $this->runBenchmark($suite, $dataSizes, $index % 2 === 1);
        }

        $this->printGithubAnnotations();

        echo PHP_EOL;
    }

    private function runBenchmark(BenchmarkSuite $suite, array $dataSizes, bool $isEven = false): void
    {
        $results = [];
        $totalWidth = self::NAME_WIDTH + count($dataSizes) * (self::COL_WIDTH + 2);
        $canFit = $this->canFitFullRow($totalWidth);

        if ($canFit) {
            // Em terminais que acomodam toda a largura, inicializa com status pendente (...)
            $this->printRow($suite->name(), $results, $dataSizes, $isEven, false);
        }

        foreach ($dataSizes as $dataSize) {
            if (!$canFit) {
                // Em telas menores, exibe indicador compacto de progresso que nunca quebra a tela
                $this->printProgress($suite->name(), $dataSize);
            }

            $data = Data\DataFactory::generate($dataSize);

            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);

            $suite->run($data);

            $results[$dataSize] = [
                'time' => microtime(true) - $startTime,
                'memory' => (memory_get_peak_usage(true) - $startMemory) / 1024 / 1024,
            ];

            if ($canFit) {
                // Reimprime a mesma linha na tela larga atualizando a coluna
                $this->printRow($suite->name(), $results, $dataSizes, $isEven, false);
            }
        }

        if (!$canFit) {
            // Limpa o indicador de progresso compacto
            echo "\r\e[2K";
        }

        // Imprime a linha definitiva completa com quebra de linha atômica
        $this->printRow($suite->name(), $results, $dataSizes, $isEven, true);
    }

    private function canFitFullRow(int $totalWidth): bool
    {
        if (getenv('GITHUB_ACTIONS') === 'true' || !stream_isatty(STDOUT)) {
            return false;
        }

        return $this->getTerminalWidth() >= $totalWidth;
    }

    private function getTerminalWidth(): int
    {
        $columns = getenv('COLUMNS');
        if ($columns !== false && is_numeric($columns)) {
            return (int)$columns;
        }

        if (DIRECTORY_SEPARATOR !== '\\') {
            $cols = @exec('tput cols 2>/dev/null');
            if (is_numeric($cols) && (int)$cols > 0) {
                return (int)$cols;
            }
        } else {
            $mode = @shell_exec('mode con');
            if ($mode && preg_match('/(?:Columns|Colunas):\s*(\d+)/i', $mode, $matches)) {
                return (int)$matches[1];
            }
        }

        return 80;
    }

    private function printProgress(string $name, int $dataSize): void
    {
        if (getenv('GITHUB_ACTIONS') === 'true' || !stream_isatty(STDOUT)) {
            return;
        }

        $sizeFormatted = number_format($dataSize, 0, '', '.');
        $msg = sprintf('  ⏳ Executando: %s (%s registros)...', $name, $sizeFormatted);
        $msg = mb_strimwidth($msg, 0, 75, '...');

        echo "\r\e[2K" . self::COLOR_CYAN . $msg . self::COLOR_RESET;
        flush();
    }

    // 2. Método auxiliar para decidir a cor baseado na velocidade
    private function getColorForTime(float $seconds): string
    {
        if ($seconds < 0.6) {
            return self::COLOR_GREEN;
        }
        if ($seconds < 1.1) {
            return self::COLOR_YELLOW;
        }
        return self::COLOR_RED;
    }

    // 3. Suas funções refatoradas
    private function printHeader(array $dataSizes): void
    {
        // Formata o texto primeiro, depois aplica a cor
        $title = sprintf('%-' . self::NAME_WIDTH . 's', 'Total de linhas do dataset ->');
        echo self::COLOR_CYAN . self::COLOR_BOLD . $title . self::COLOR_RESET;

        foreach ($dataSizes as $size) {
            $colText = sprintf('%-' . self::COL_WIDTH . 's', number_format($size, 0, '', '.'));
            // Mantém a barra vertical em cinza e o texto em ciano
            echo self::COLOR_DARK_GRAY . '| ' . self::COLOR_RESET . self::COLOR_CYAN . $colText . self::COLOR_RESET;
        }

        echo PHP_EOL;

        // Calcula o tamanho da linha de separação e pinta de cinza escuro
        $separatorLength = self::NAME_WIDTH + count($dataSizes) * (self::COL_WIDTH + 2);
        echo self::COLOR_DARK_GRAY . str_repeat('=', $separatorLength) . self::COLOR_RESET . PHP_EOL;
    }

    private function printRow(string $name, array $results, array $dataSizes, bool $isEven = false, bool $isFinal = true): void
    {
        $bg = $isEven ? self::BG_ZEBRA : '';

        // Limpa a linha inteira e volta o cursor para a coluna 0
        echo "\r\e[2K";

        // Nome do teste em negrito
        $nameText = sprintf('%-' . self::NAME_WIDTH . 's', $name);
        echo $bg . self::COLOR_BOLD . $nameText . self::COLOR_RESET;

        foreach ($dataSizes as $size) {
            if (!isset($results[$size])) {
                $emptyText = sprintf('%-' . self::COL_WIDTH . 's', '...');

                echo $bg . self::COLOR_DARK_GRAY . '| ' . self::COLOR_RESET . $bg . self::COLOR_DARK_GRAY . $emptyText . self::COLOR_RESET;
                continue;
            }

            $time = $results[$size]['time'];
            $memory = $results[$size]['memory'];

            // Formata os dados no tamanho exato
            // Subtraímos 1 do COL_WIDTH porque o formato nativo com "MB" já ocupa bastante espaço
            $resultStr = sprintf('%6.4fs / %5.2f MB ', $time, $memory);

            // Se a string final for menor que a largura da coluna, preenche com espaços extras para alinhar
            $resultStr = str_pad($resultStr, self::COL_WIDTH, ' ');

            $color = $this->getColorForTime($time);

            // Imprime a barra em cinza e o texto no formato "Semáforo", mantendo o fundo zebrado se ativo
            echo $bg . self::COLOR_DARK_GRAY . '| ' . self::COLOR_RESET . $bg . $color . $resultStr . self::COLOR_RESET;

            if ($time > 1.5) {
                // Monta a string no formato exato que o GitHub exige sem duplicidades
                $this->githubAnnotations["{$name}-{$size}"] = "::warning title=Gargalo de Performance (🚨 {$name})::O método '{$name}' processando {$size} registros demorou {$time} segundos, o que está acima do limite aceitável de 1.5s.";
            }
        }

        echo self::COLOR_RESET;

        if ($isFinal) {
            echo PHP_EOL;
        }

        flush(); // Empurra a saída para o terminal
    }

    private function printGithubAnnotations(): void
    {
        // Verifica se o script está rodando dentro do GitHub Actions
        if (getenv('GITHUB_ACTIONS') === 'true') {
            if ($this->githubAnnotations !== []) {
                echo "\n\n🔔 Emitindo Annotations para o GitHub Actions...\n";
                foreach ($this->githubAnnotations as $annotation) {
                    echo $annotation . PHP_EOL;
                }
            } else {
                // Se tudo foi rápido, emite um Notice de sucesso!
                echo "\n\n::notice title=Performance Excelente 🚀::Todos os benchmarks rodaram abaixo de 1.5 segundos!\n";
            }
        }
    }
}
