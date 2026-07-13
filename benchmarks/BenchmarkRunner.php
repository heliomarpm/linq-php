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

    foreach ($suites as $suite) {
      $this->runBenchmark($suite, $dataSizes);
    }

    echo PHP_EOL;
  }

  private function runBenchmark(BenchmarkSuite $suite, array $dataSizes): void
  {
    $results = [];

    // imprime linha inicial vazia
    $this->printRow($suite->name(), $results, $dataSizes);

    foreach ($dataSizes as $dataSize) {
      $data = \Benchmarks\Data\DataFactory::generate($dataSize);

      $startTime = microtime(true);
      $startMemory = memory_get_usage(true);

      $suite->run($data);

      $results[$dataSize] = [
        'time' => microtime(true) - $startTime,
        'memory' => (memory_get_peak_usage(true) - $startMemory) / 1024 / 1024,
      ];

      // reimprime a MESMA linha, agora com mais uma coluna preenchida
      $this->printRow($suite->name(), $results, $dataSizes);
    }

    $this->printGithubAnnotations();

    echo PHP_EOL;
  }


  // 2. Método auxiliar para decidir a cor baseado na velocidade
  private function getColorForTime(float $seconds): string
  {
    if ($seconds < 0.5)
      return self::COLOR_GREEN;
    if ($seconds < 1.4)
      return self::COLOR_YELLOW;
    return self::COLOR_RED;
  }

  // 3. Suas funções refatoradas
  private function printHeader(array $dataSizes): void
  {
    // Formata o texto primeiro, depois aplica a cor
    $title = sprintf("%-" . self::NAME_WIDTH . "s", 'Total de linhas do dataset ->');
    echo self::COLOR_CYAN . self::COLOR_BOLD . $title . self::COLOR_RESET;

    foreach ($dataSizes as $size) {
      $colText = sprintf("%-" . self::COL_WIDTH . "s", number_format($size, 0, '', '.'));
      // Mantém a barra vertical em cinza e o texto em ciano
      echo self::COLOR_DARK_GRAY . "| " . self::COLOR_RESET . self::COLOR_CYAN . $colText . self::COLOR_RESET;
    }

    echo PHP_EOL;

    // Calcula o tamanho da linha de separação e pinta de cinza escuro
    $separatorLength = self::NAME_WIDTH + count($dataSizes) * (self::COL_WIDTH + 2);
    echo self::COLOR_DARK_GRAY . str_repeat('=', $separatorLength) . self::COLOR_RESET . PHP_EOL;
  }

  private function printRow(string $name, array $results, array $dataSizes): void
  {
    //volta o cursor para a coluna 0
    echo "\r";

    // Nome do teste em negrito
    $nameText = sprintf("%-" . self::NAME_WIDTH . "s", $name);
    echo self::COLOR_BOLD . $nameText . self::COLOR_RESET;

    foreach ($dataSizes as $size) {
      if (!isset($results[$size])) {
        $emptyText = sprintf("%-" . self::COL_WIDTH . "s", '...');

        // \e[2K apaga a linha inteira no terminal; \r volta o cursor para a coluna 0
        echo "\e[2K\r";
        echo self::COLOR_DARK_GRAY . "| " . $emptyText . self::COLOR_RESET;
        continue;
      }

      $time = $results[$size]['time'];
      $memory = $results[$size]['memory'];

      // Formata os dados no tamanho exato
      // Subtraímos 1 do COL_WIDTH porque o formato nativo com "MB" já ocupa bastante espaço
      $resultStr = sprintf("%6.4fs / %5.2f MB ", $time, $memory);

      // Se a string final for menor que a largura da coluna, preenche com espaços extras para alinhar
      $resultStr = str_pad($resultStr, self::COL_WIDTH, " ");

      $color = $this->getColorForTime($time);

      // Imprime a barra em cinza e o texto no formato "Semáforo"
      echo self::COLOR_DARK_GRAY . "| " . self::COLOR_RESET . $color . $resultStr . self::COLOR_RESET;

      if ($time > 1.5) {
        // Monta a string no formato exato que o GitHub exige
        $this->githubAnnotations[] = "::warning title=Gargalo de Performance (🚨 {$name})::O método '{$name}' processando {$size} registros demorou {$time} segundos, o que está acima do limite aceitável de 1.5s.";
      }
    }

    flush(); // Empurra a saída para o terminal
  }

  private function printGithubAnnotations(): void
  {
    // Verifica se o script está rodando dentro do GitHub Actions
    if (getenv('GITHUB_ACTIONS') === 'true') {
      if (!empty($this->githubAnnotations)) {
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
