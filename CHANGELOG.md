# 📦 Changelog

Todas as alterações notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado no [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/) e este projeto adere ao [Semantic Versioning](https://semver.org/lang/pt-BR/).


# [2.2.0](https://github.com/heliomarpm/linq-php/compare/v2.1.0...v2.2.0) (2026-10-25)


### Features

* **core:** ✨ implementar métodos de agregação escalar rápida ([8b807e4](https://github.com/heliomarpm/linq-php/commit/8b807e42aa9287c59547f4173d1246f23722f179))
* **demo:** ⚡ adicionar painel de benchmark comparativo ao vivo ([774e850](https://github.com/heliomarpm/linq-php/commit/774e850e4b8879de6eb37c10333981e870a9649b))
* **demo:** ✨ implementar reatividade com vue 3 e endpoint json ([db8384e](https://github.com/heliomarpm/linq-php/commit/db8384e316b70cd3a9be0298545c41c3d533edb1))
* **demo:** 📦 adicionar ferramentas de exportação (CSV, JSON, Array PHP) ([d03b6d2](https://github.com/heliomarpm/linq-php/commit/d03b6d2e4074e24177c9766deeafef4416675217))


### Performance Improvements

* **core:** ⚡ otimizar loops internos de where, groupBy e join ([f518040](https://github.com/heliomarpm/linq-php/commit/f5180408fdeab4c4ebbd853e0dc62364183200d4))

# [2.1.0](https://github.com/heliomarpm/linq-php/compare/v2.0.1...v2.1.0) (2026-10-25)


### Features

* **core:** ✨ adicionar método terminal firstOrDefault ([4cbd306](https://github.com/heliomarpm/linq-php/commit/4cbd3063e8fc8eb8cf1fcb81c443898fa4f5062e))
* **join:** ✨ adicionar métodos auxiliares para operações de join ([f8605a1](https://github.com/heliomarpm/linq-php/commit/f8605a1a590ec0bfd96242709a4ddeb033ab5eac))
* **sort:** ✨ aprimorar ordenação em orderByKey e orderBy ([663d07c](https://github.com/heliomarpm/linq-php/commit/663d07c3ee96501c3097823fbe4aee70cec1703f))
* **sort:** ✨ suportar closures e funções nas operações de ordenação ([b45ffca](https://github.com/heliomarpm/linq-php/commit/b45ffcaac876de5eb7dfce9834775c26e63b4af0))

## [2.0.1](https://github.com/heliomarpm/linq-php/compare/v2.0.0...v2.0.1) (2026-10-15)


### Bug Fixes

* ⬆️ remover versão do composer.json ([18db83c](https://github.com/heliomarpm/linq-php/commit/18db83c114737297f45ba3692146785cdf36747f))

# [2.0.0](https://github.com/heliomarpm/linq-php/compare/v1.0.0...v2.0.0) (2026-10-15)


### Bug Fixes

* **linq:** 🐛 corrigir obrigatoriedade de campos relacionados ([b7da27a](https://github.com/heliomarpm/linq-php/commit/b7da27a94f6f15432e27f62943b6142edb1f4833))


### Code Refactoring

* **core:** ♻️ otimizar processamento e padronizar groupBy ([26fe9ae](https://github.com/heliomarpm/linq-php/commit/26fe9aee27ec020a7d5a255352323ae3117b3690))


### Features

* **demo:** ✨ aprimorar demo interativa e adicionar deploy Vercel ([e243335](https://github.com/heliomarpm/linq-php/commit/e243335622f184237d03e5b8582f79e27bd29dbc))
* **dev:** ✨ integrar PHPStan e Pint para qualidade de código ([bd9c3d5](https://github.com/heliomarpm/linq-php/commit/bd9c3d5c7e7f39b9a25106ce1b5c02e23c8dbbf4))
* **select:** ✨ adicionar suporte a aliases e otimizar `select` ([d0e4673](https://github.com/heliomarpm/linq-php/commit/d0e4673f3959d881c94a1ddee9c945af8fc51816))


### BREAKING CHANGES

* **core:** O método `toObject()` agora inclui `memoryUsed` e `data` no retorno.
