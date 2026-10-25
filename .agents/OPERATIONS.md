# Operações e CI/CD: LinqPHP

> Gerado por -discovery | Commit base: `b7da27a` | Atualizado em: 2026-10-13 | Idioma: pt-BR

<!-- discovery:start:ci -->
## Integração Continua (CI)

O pipeline e gerenciado pelo GitHub Actions no arquivo `.github/workflows/ci.yml`:

- **Gatilhos**: executa em eventos de `push` e `pull_request` nas branches `main` e `master`.
- **Ambiente de Execução**: `ubuntu-latest`.
- **Etapas do Job**:
  1. Checkout do repositorio (`actions/checkout@v7`).
  2. Configuração do runtime PHP (`shivammathur/setup-php@v2`) com Composer v2.
  3. Validação estrita do arquivo de definição do Composer (`composer validate --strict`).
  4. Instalação de dependências (`composer install --prefer-dist --no-progress --no-interaction`).
  5. Execução da suite de testes PHPUnit (`composer test`).
  6. Execução de benchmarks customizados (`composer bench`).
  7. Validação de performance com PHPBench (`vendor/bin/phpbench run --report=aggregate`).
<!-- discovery:end:ci -->

<!-- discovery:start:ambientes -->
## Ambientes e Variaveis

- **Variaveis de Ambiente**: não ha requisitos de configuração via `.env` para a execução da biblioteca ou da suite de testes.
- **Requisitos de Sistema**: PHP CLI >= 8.1 com extensões basicas de memoria e microtempo ativas.
<!-- discovery:end:ambientes -->

<!-- discovery:start:publicação -->
## Publicação e Distribuição

- **Distribuição**: gerenciada pelo Packagist no namespace `heliomarpm/linq-php`.
- **Exportação Limpa**: arquivos de configuração de teste e diretórios `tests/` e `benchmarks/` são isolados da distribuição final via Composer.
<!-- discovery:end:publicação -->
