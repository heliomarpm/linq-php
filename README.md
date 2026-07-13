<div id="top" align="center">
<h1>
  <img src="./logo.png" alt="LinqPHP" width="228" />

  <a href="https://navto.me/heliomarpm" target="_blank"><img src="https://navto.me/assets/navigatetome-brand.png" width="32"/></a>
  [![DeepScan grade][url-deepscan-badge]][url-deepscan]
  [![CodeFactor][url-codefactor-badge]][url-codefactor]

  [![Packagist Downloads][url-packagist-downloads-badge]][url-packagist]
  [![Packagist Version][url-packagist-badge]][url-packagist]

</h1>

<div class="badges">

  [![GitHub Sponsors][url-github-sponsors-badge]][url-github-sponsors]
  [![PayPal][url-paypal-badge]][url-paypal]
  [![Ko-fi][url-kofi-badge]][url-kofi]
  [![Liberapay][url-liberapay-badge]][url-liberapay]
  
</div>
</div>

# LinqPHP

Uma biblioteca leve, de alta performance e sem dependências externas para manipulação de coleções de dados (`arrays`) em PHP, inspirada na sintaxe do LINQ (Language Integrated Query).

O LinqPHP permite filtrar, agrupar, ordenar e unir arrays multidimensionais de forma fluida e elegante, sendo ideal para processamento de relatórios, formatação de dados para APIs e refatoração de sistemas legados.

## Requisitos

- PHP 8.1 ou superior.

## Instalação

Você pode instalar o pacote via Composer:

```bash
composer require heliomarpm/linqphp
```

## Como usar

A classe LinqPHP utiliza uma interface fluida (method chaining). Todas as operações retornam a própria instância (exceto os métodos de saída `toArray` e `toObject`), permitindo encadear múltiplas transformações.

### Inicialização

```php
require 'vendor/autoload.php';

use HeliomarPM\LinqPHP\LinqPHP;

$data = [
  ['id' => 1, 'name' => 'John', 'age' => 25, 'status' => 'active', 'city' => 'London'],
  ['id' => 2, 'name' => 'Jane', 'age' => 30, 'status' => 'pending', 'city' => 'New York'],
  ['id' => 3, 'name' => 'John', 'age' => 35, 'status' => 'inactive', 'city' => 'Paris'],
];

$linq = LinqPHP::from($data);

```

### `where()` - Filtragem de Dados

Aceita arrays de condições simples ou funções (closures) para filtros complexos.

```php
// Filtro simples
$result = LinqPHP::from($data)
    ->where(['age', '>', 25])
    ->toArray();

// Múltiplos filtros e operadores (startswith, endswith, contains, in)
$result = LinqPHP::from($data)
    ->where([
        ['age', '>=', 25],
        ['status', 'in', ['active', 'pending']],
        ['name', 'startswith', 'J']
    ])
    ->toArray();

// Usando Closures (funções anônimas)
$result = LinqPHP::from($data)
    ->where(fn($item) => $item['id'] % 2 !== 0)
    ->toArray();
```

### `select()` e `distinct()` - Projeção de Dados

Selecione apenas as colunas desejadas. Use `strict: true` para garantir que os dados existam.

```php
// Seleciona colunas específicas
$result = LinqPHP::from($data)
    ->select(['name', 'status'])
    ->toArray();

// Retorna apenas os registros únicos
$result = LinqPHP::from($data)
    ->select(['name'])
    ->distinct()
    ->toArray();
```

### `join()` - União Relacional

Faça joins entre arrays como se estivesse em um banco de dados.

```php
$users = [
    ['id' => 1, 'name' => 'John', 'course_id' => 10],
    ['id' => 2, 'name' => 'Jane', 'course_id' => 20],
];

$courses = [
    ['id_course' => 10, 'course_name' => 'PHP 8'],
    ['id_course' => 20, 'course_name' => 'Clean Code'],
];

$result = LinqPHP::from($users)
    ->join($courses, 'INNER', ['course_id' => 'id_course'])
    ->toArray();
```

### `groupBy()` - Agrupamento e Agregação

Agrupe dados e realize cálculos automáticos (sum, max, min, count, avg).

```php
$vendas = [
    ['vendedor' => 'João', 'valor' => 100],
    ['vendedor' => 'Maria', 'valor' => 200],
    ['vendedor' => 'João', 'valor' => 150],
];

// Agrupa por vendedor e soma os valores
$result = LinqPHP::from($vendas)
    ->groupBy(['vendedor'], ['sum' => ['valor']])
    ->toArray();
```

### `orderBy()` e `orderByKey()` - Ordenação

```php
// Ordena por uma chave simples
$result = LinqPHP::from($data)
    ->orderByKey('age', 'desc')
    ->toArray();

// Ordenação múltipla
$result = LinqPHP::from($data)
    ->orderBy(['name' => 'asc', 'age' => 'desc'])
    ->toArray();
```

### Extração de Resultados (`toArray` e `toObject`)

No final da cadeia de métodos, você deve chamar um método de saída para recuperar seus dados modificados.

```php
$linq = LinqPHP::from($data)->where(['age', '>', 20]);

// Retorna um array tradicional
$array =$linq->toArray();

// Retorna um stdClass contendo metadados de performance
$obj =$linq->toObject();
echo $obj->count; // Quantidade de registros
echo $obj->elapsedTime; // Tempo de execução
echo $obj->memoryUsed; // Memória gasta
print_r($obj->rows); // Os dados em si
```

## Desenvolvimento e Testes

Este pacote foi desenhado para ser leve. O código-fonte principal encontra-se na pasta `src/`.
Testes unitários e benchmarks não são exportados na instalação via Composer para economizar espaço e banda.

Para rodar os testes localmente (após clonar o repositório):

```bash
composer install
composer test
```

> [!NOTE]
> O comando `composer du -o`, significa `composer dump-autoload -o`, que é uma opção para atualizar o autoload do Composer sem precisar de reiniciar o servidor web. Isso é útil quando você está trabalhando com muitos arquivos e você não quer reiniciar o servidor web a cada mudança. Isso também pode ajudar a evitar problemas com o autoload, especialmente em ambientes de desenvolvimento.

---

## 🤝 Contribuição

Aceitamos contribuições de bom grado! Seja relatando um bug, sugerindo uma nova funcionalidade, melhorando a documentação ou enviando um *pull request*, sua ajuda é muito apreciada.

Por favor, certifique-se de ler o seguinte antes de enviar um *pull request*:

- [Código de Conduta](.github/CODE_OF_CONDUCT.md)
- [Guia de Contribuição](.github/CONTRIBUTING.md)

Agradecemos a todas as pessoas que já contribuíram para o projeto!

<a href="https://github.com/heliomarpm/linq-php/graphs/contributors" target="_blank">
  <img src="https://contrib.nn.ci/api?repo=heliomarpm/linq-php&no_bot=true" />
</a>

###### Made with [contrib.nn](https://contrib.nn.ci/?repo=heliomarpm/linq-php&no_bot=true).

Dito isso, existem várias maneiras de contribuir para este projeto, como:

⭐ Dando uma estrela (*star*) ao repositório \
🐞 Relatando bugs \
💡 Sugerindo funcionalidades \
🧾 Melhorando a documentação \
📢 Compartilhando este projeto e recomendando-o aos seus amigos

## 💵 Apoie o Projeto

Se você gosta do projeto, considere fazer uma doação ao desenvolvedor via GitHub Sponsors, Ko-fi, PayPal ou Liberapay — a escolha é sua. 😉

<div class="badges">

  [![GitHub Sponsors][url-github-sponsors-badge]][url-github-sponsors]
  [![PayPal][url-paypal-badge]][url-paypal]
  [![Ko-fi][url-kofi-badge]][url-kofi]
  [![Liberapay][url-liberapay-badge]][url-liberapay]

</div>

## 📝 Licença

Este projeto está sob a licença MIT. Sinta-se livre para usá-lo e modificá-lo.

[MIT © Heliomar P. Marques](https://github.com/heliomarpm/linqphp/blob/main/LICENSE) <a href="#top">🔝</a>

---
<!-- Sponsor badges -->


<!-- Packagist badges -->


<!-- other badges -->

[url-codefactor]: https://www.codefactor.io/repository/github/heliomarpm/linq-php
[url-codefactor-badge]: https://www.codefactor.io/repository/github/heliomarpm/linq-php/badge
[url-deepscan]: https://deepscan.io/dashboard#view=project&tid=19612&pid=32077&bid=1043083
[url-deepscan-badge]: https://deepscan.io/api/teams/19612/projects/32077/branches/1043083/badge/grade.svg
[url-github-sponsors]: https://github.com/sponsors/heliomarpm
[url-github-sponsors-badge]: https://img.shields.io/badge/GitHub%20-Sponsor-1C1E26?style=for-the-badge&labelColor=1C1E26&color=db61a2
[url-kofi]: https://ko-fi.com/heliomarpm
[url-kofi-badge]: https://img.shields.io/badge/kofi-1C1E26?style=for-the-badge&labelColor=1C1E26&color=ff5f5f
[url-liberapay]: https://liberapay.com/heliomarpm
[url-liberapay-badge]: https://img.shields.io/badge/liberapay-1C1E26?style=for-the-badge&labelColor=1C1E26&color=f6c915
[url-packagist]: https://packagist.org/packages/heliomarpm/linq-php
[url-packagist-badge]: https://img.shields.io/packagist/v/heliomarpm/linq-php
[url-packagist-downloads-badge]: https://img.shields.io/packagist/dt/heliomarpm/linq-php
[url-paypal]: https://bit.ly/paypal-sponsor-heliomarpm
[url-paypal-badge]: https://img.shields.io/badge/donate%20on-paypal-1C1E26?style=for-the-badge&labelColor=1C1E26&color=0475fe
