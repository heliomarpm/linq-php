# Operacoes e CI/CD: LinqPHP

> Gerado por -discovery | Commit base: `b7da27a` | Atualizado em: 2026-10-13 | Idioma: pt-BR

<!-- discovery:start:ci -->
## Integracao Continua (CI)
O pipeline e gerenciado pelo GitHub Actions no arquivo `.github/workflows/ci.yml`:
- **Gatilhos**: executa em eventos de `push` e `pull_request` nas branches `main` e `master`.
- **Ambiente de Execucao**: `ubuntu-latest`.
- **Etapas do Job**:
  1. Checkout do repositorio (`actions/checkout@v7`).
  2. Configuracao do runtime PHP (`shivammathur/setup-php@v2`) com Composer v2.
  3. Validacao estrita do arquivo de definicao do Composer (`composer validate --strict`).
  4. Instalacao de dependencias (`composer install --prefer-dist --no-progress --no-interaction`).
  5. Execucao da suite de testes PHPUnit (`composer test`).
  6. Execucao de benchmarks customizados (`composer bench`).
  7. Validacao de performance com PHPBench (`vendor/bin/phpbench run --report=aggregate`).
<!-- discovery:end:ci -->

<!-- discovery:start:ambientes -->
## Ambientes e Variaveis
- **Variaveis de Ambiente**: nao ha requisitos de configuracao via `.env` para a execucao da biblioteca ou da suite de testes.
- **Requisitos de Sistema**: PHP CLI >= 8.1 com extensoes basicas de memoria e microtempo ativas.
<!-- discovery:end:ambientes -->

<!-- discovery:start:publicacao -->
## Publicacao e Distribuicao
- **Distribuicao**: gerenciada pelo Packagist no namespace `heliomarpm/linq-php`.
- **Exportacao Limpa**: arquivos de configuracao de teste e diretorios `tests/` e `benchmarks/` sao isolados da distribuicao final via Composer.
<!-- discovery:end:publicacao -->
