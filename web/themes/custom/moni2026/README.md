# moni2026 – Me.Mo Foto 2026 theme

Önálló pure CSS téma, `base theme: false`. Nincs SCSS és nincs build lépés.
A stable9-ből a használt template-ek (`block--system-menu-block`,
`block--system-messages-block`, `select`, `feed-icon`, `views-view-field(s)`)
és a három preprocess hookja (block, views_view, item_list__search_results)
át lett véve; a CSS a core modulok saját fájljaiból jön.
A `bootstrap5` témától független: a frontend template-jei (`admin/`,
`media-library/`, `content-edit/` nélkül, azokat a Claro rendereli), a
preprocess hookjai (`moni2026.theme`), a `css/components/*`, a
`js/nav-tabs.js` és a hozzájuk tartozó `images/icons/*` át lettek véve.
A `b5_*` theme settingek maradtak, de beállító űrlap nincs hozzájuk:
`drush cset moni2026.settings b5_top_container …`.

- `css/base.css`: tokenek (`--moni-*`), reset, tipográfia
- `css/layout.css`: container, grid, a template-ekben használt utility osztályok
- `css/components.css`: navbar, dropdown, tabs, pager, alerts, forms
- `css/drupal.css`: a bootstrap5 `style.css` Drupal-specifikus része Bootstrap-változók nélkül (a `css/components/*` marad)
- `css/theme.css`: a memo SCSS 1:1 portja
- A navbar collapse checkbox + label megoldással működik. A dropdown
  `:hover` / `:focus-within` alapon nyílik: a `<nolink>` szülő menüpont
  (Galériák) a `menu--main.html.twig`-ben fókuszálható `<span>`, így
  JS nélkül sem tölti újra az oldalt, és mobilon tapintásra nyílik.
- `css/components/*`: a bootstrap5 Drupal-komponens CSS-e változatlanul.

## Ellenőrzés

A memo és a moni2026 ugyanazon a renderelt markupon összevetve (Árak,
Bemutatkozás, Portré, Ajánlatkérés, címlap; 1400 px és 390 px): pixelre
azonos, a címlapon csak a vvjs slideshow animációs fázisa tér el.

## Aktiválás (kézzel)

    ddev drush theme:install moni2026
    # memo beállításainak (logo, favicon, b5_*) átvétele:
    ddev drush php:eval '$s = \Drupal::config("memo.settings")->getRawData(); unset($s["_core"]); \Drupal::configFactory()->getEditable("moni2026.settings")->setData($s)->save();'
    ddev drush config:set system.theme default moni2026 -y
    ddev drush cr

Telepítéskor a Drupal a default téma blokkjait `moni2026_` prefixszel
átmásolja, mert a régiók megegyeznek.
