# SCF Polylang i18n

Plugin local da Spotren para complementar CPTs e taxonomias criados pelo SCF
com tradução de labels/descriptions via Polylang e bases de URL traduzidas por
regras próprias do WordPress.

## Escopo

O plugin não cria CPTs nem taxonomias. O SCF continua sendo a fonte desses
objetos. Este plugin atua depois, sobre as APIs core do WordPress.

Ele faz:

- registra labels e descriptions de CPTs/taxonomias como strings do Polylang;
- traduz esses labels/descriptions em runtime com `pll__()`;
- força CPTs/taxonomias configurados a aparecerem nas configurações do Polylang;
- cria rewrite rules para bases traduzidas com prefixo de idioma, como
  `/pt/solucoes/...`;
- ajusta links gerados por WordPress para usar essas bases traduzidas.
- limpa suas options no uninstall.
- adiciona placeholders para SiteSEO: `%%cpt_description%%` e
  `%%post_type_description%%`.

Ele não faz:

- tradução automática de conteúdo;
- tradução de termos de taxonomia;
- edição visual dos slugs pelo painel do Polylang, que é recurso do Polylang Pro.
- substituição de todos os placeholders internos do SiteSEO.

## Requisitos

- WordPress 6.5+.
- PHP 8.0+.
- Polylang.
- Secure Custom Fields.

SiteSEO e opcional; a integração so roda quando o plugin esta ativo e expõe as
configuracoes esperadas.

## Publicação e versionamento

O arquivo `readme.txt` segue o formato do diretório oficial de plugins do
WordPress. O `README.md` fica como documentação operacional do projeto.

Para desenvolvimento do plugin como repositório próprio:

```bash
composer install
composer lint
```

Antes de publicar, rode tambem o Plugin Check dentro de um WordPress local:

```bash
wp plugin check scf-polylang-i18n
```

## Configuração pelo painel

A tela principal fica em:

```text
Settings > SCF Polylang i18n
```

Nessa tela voce controla:

- idiomas usados para as colunas de slug;
- quais CPTs e taxonomias entram na camada de rewrite;
- slugs por idioma;
- se CPTs devem gerar archive e/ou single com base traduzida;
- se labels/descriptions devem ser registrados como strings do Polylang;
- se o idioma padrao deve ficar sem prefixo de URL.

As configuracoes salvas no painel ficam na option
`scf_polylang_i18n_mappings`. Depois do primeiro salvamento pelo painel, essa
option vira a fonte principal dos mapeamentos editaveis.

## Configuração por arquivo

Tambem e possivel definir defaults em `config/mappings.php`. O painel sobrescreve
os mesmos itens quando houver option salva.

Exemplo:

```php
return [
    'languages' => ['pt', 'en'],
    'post_types' => [
        'solution' => [
            'slugs' => [
                'pt' => 'solucoes',
                'en' => 'solutions',
            ],
            'archive' => true,
            'single' => true,
        ],
    ],
    'taxonomies' => [
        'solution_category' => [
            'slugs' => [
                'pt' => 'categorias-de-solucoes',
                'en' => 'solution-categories',
            ],
        ],
    ],
    'auto_labels' => true,
    'unprefixed_default_language' => false,
];
```

Com `unprefixed_default_language => false`, todas as URLs geradas usam o prefixo
de idioma. Para português:

```text
/pt/solucoes/
/pt/solucoes/nome-do-post/
/pt/categorias-de-solucoes/nome-do-termo/
```

## Flush de permalinks

O plugin faz flush das rewrite rules quando é ativado ou quando o hash da
configuração muda. Se algo ficar fora de sincronia, salve novamente
`Settings > Permalinks`.

## Cuidados

As bases configuradas não devem conflitar entre si dentro do mesmo idioma. Evite,
por exemplo, usar `pt/solucoes` para um CPT e uma taxonomia ao mesmo tempo.

Por padrao, a traducao automatica de labels/descriptions ignora objetos internos
nao publicos de outros plugins. Para forcar um CPT/taxonomia especifico, marque
o item no painel.

## SiteSEO

Para archives de CPT, o SiteSEO usa a `description` do post type como fallback
quando o campo `Meta description template` fica vazio.

Este plugin tambem adiciona dois placeholders para os campos de meta description
do SiteSEO:

```text
%%cpt_description%%
%%post_type_description%%
```

Use em:

```text
SiteSEO > Titles & Metas > Archives > [CPT] > Meta description template
```

Tambem funciona nos templates de titulo e meta description de single CPT. O
placeholder e resolvido antes do SiteSEO imprimir as meta tags no `wp_head`, e o
document title recebe uma substituicao defensiva depois do SiteSEO calcular o
titulo.

Exemplo:

```text
%%cpt_description%%
```
