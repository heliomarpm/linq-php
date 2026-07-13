<?php

namespace Benchmarks\Data;

class DataFactory
{
  public static function generate(int $size): array
  {
    $products = [];
    $sales = [];

    $statusOptions = ['concluido', 'pendente', 'cancelado'];

    // 1. Gera tabela de Produtos (10% do tamanho total para simular um catálogo realista)
    $qtdProdutos = max(1, (int) ($size / 10));
    for ($i = 0; $i < $qtdProdutos; $i++) {
      $products[] = [
        'id' => $i,
        'name' => 'Produto ' . $i,
        'category' => 'Categoria ' . rand(1, 5),
        'cost' => rand(10, 1000)
      ];
    }

    // 2. Gera tabela principal de Vendas e tabela para Distinct
    for ($i = 0; $i < $size; $i++) {
      $produto_id = rand(0, $qtdProdutos - 1);
      $status = $statusOptions[array_rand($statusOptions)];

      $sales[] = [
        'id' => $i,
        'product_id' => $produto_id,
        'value' => rand(10, 1000) / 10,
        'status' => $status
      ];
    }

    return [$sales, $products];
  }

  public static function generateJoinData(int $size): array
  {
    $students = [];
    $courses = [];

    $courseCount = max(10, (int) ($size / 100));

    for ($i = 1; $i <= $courseCount; $i++) {
      $courses[] = [
        'id' => $i,
        'title' => "Course {$i}",
        'category' => 'cat_' . ($i % 5),
      ];
    }

    for ($i = 1; $i <= $size; $i++) {
      $students[] = [
        'id' => $i,
        'name' => "Student {$i}",
        'course_id' => rand(1, $courseCount + 3), // gera órfãos
        'score' => rand(0, 100),
      ];
    }

    return [$students, $courses];
  }

}
