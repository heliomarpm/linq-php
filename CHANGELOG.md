# 📦 Changelog

Todas as alterações notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado no [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/) e este projeto adere ao [Semantic Versioning](https://semver.org/lang/pt-BR/).


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
