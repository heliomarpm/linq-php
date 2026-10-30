# 📦 Changelog

Todas as alterações notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado no [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/) e este projeto adere ao [Semantic Versioning](https://semver.org/lang/pt-BR/).


# [2.3.0](https://github.com/heliomarpm/linq-php/compare/v2.2.0...v2.3.0) (2026-10-30)


### Bug Fixes

* **bench:** 🐛 evitar quebra e repetição de linha em telas menores ([c85797b](https://github.com/heliomarpm/linq-php/commit/c85797b9f2a96e330f2c087412ea85d5388cf809))
* **core:** 🐛 corrigir colisão e desordenação em groupBy multi-chave ([d31b925](https://github.com/heliomarpm/linq-php/commit/d31b925c7a40b762188f54108646d3ecdfe57896))
* **core:** 🐛 isolar enum JoinType em arquivo proprio para PSR-4 ([7185627](https://github.com/heliomarpm/linq-php/commit/7185627d4c655c6909f78657c0052881b778de4f))
* **demo:** 🐛 adicionar fallback nativo do DbPHP para deploy serverless ([f7f50a6](https://github.com/heliomarpm/linq-php/commit/f7f50a6e4d972907968c19509569172c04323da0))
* **demo:** 🐛 alinhar scripts nativos do benchmark garantindo paridade total ([cadfa9b](https://github.com/heliomarpm/linq-php/commit/cadfa9b87e6f710a7cdc911f0c681698909093de))
* **demo:** 🔒 blindar sandbox web contra RCE e chamadas perigosas ([3a4ffe7](https://github.com/heliomarpm/linq-php/commit/3a4ffe749fa4a456c5432112c8036c5e76917af8))


### Features

* **collection:** ✨ adicionar métodos `last()` e `lastOrDefault()` ([d62ee69](https://github.com/heliomarpm/linq-php/commit/d62ee6970f2e9bc02cb890cd7c17ddadaeca0d8a))
* **core:** ✨ adicionar métodos `any()` e `all()` com curto-circuito ([8de7a1e](https://github.com/heliomarpm/linq-php/commit/8de7a1ec6b93675c789661ff039642e93cd9ad5a))
* **core:** ✨ suportar comparacao case-sensitive e utf-8 em where ([f1d0cb8](https://github.com/heliomarpm/linq-php/commit/f1d0cb82f6385cae535fb19b0329d00f8358d160))
* **core:** ✨ suportar operadores relacionais e strict in em where ([daf1cc1](https://github.com/heliomarpm/linq-php/commit/daf1cc17914c3ef7fa893945841eb2788da8a671))
* **demo:** ✨ adicionar favicon vetorial e rota estatica ([2140922](https://github.com/heliomarpm/linq-php/commit/2140922e344ff75e1921b560b7c5296f43452b40))
* **distinct:** ✨ adicionar keySelector string e callable eliminando serialize ([e922ee6](https://github.com/heliomarpm/linq-php/commit/e922ee60eea70eae9ff5f50eb3b3dac3f54f8fa2))
* **linq:** ✨ adicionar método first() com UnderflowException ([637fea2](https://github.com/heliomarpm/linq-php/commit/637fea2c400497210048c11a17702dde0ea30675))
* **linq:** ✨ adicionar método pluck para projeção de coluna ([fa1d745](https://github.com/heliomarpm/linq-php/commit/fa1d74528943584f69a252d62b843497dffa5949))
* **paginacao:** ✨ adicionar método skip para paginação fluente ([c0302ae](https://github.com/heliomarpm/linq-php/commit/c0302ae25c03b7d93d1e53333fcbd9e2a2a1575f))


### Performance Improvements

* **core:** ⚡ acumular médias escalares em groupBy com memória O(1) ([a69cfda](https://github.com/heliomarpm/linq-php/commit/a69cfda821cecce2efaa70486f19af1a2ef8cb8e))
* **core:** ⚡ implementar fast-path de chave simples em groupBy ([d5883d7](https://github.com/heliomarpm/linq-php/commit/d5883d76220455fd67fd8b4f399e1c228126a7a0))
* **core:** ⚡ implementar fast-path de esquema idêntico em unionAll ([229d3d9](https://github.com/heliomarpm/linq-php/commit/229d3d90faa13700301d0109c0540f64a92d62cd))
* **core:** ⚡ otimizar alocação de memória no select ([86c75f3](https://github.com/heliomarpm/linq-php/commit/86c75f32e150fbc33cedf3605ea2828efc876a9f))
* **core:** ⚡ precomputar moldes nulos em juncoes externas ([2ba7a72](https://github.com/heliomarpm/linq-php/commit/2ba7a724bd5ccc2d1fba6ea4c4ddde96c1ef0c71))
* **where:** ⚡ otimizar operador in com lookup O(1) e documentar operadores ([8b2fe37](https://github.com/heliomarpm/linq-php/commit/8b2fe3705d42d33c73cc6ad844c0d5fddf42d9ff))

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
