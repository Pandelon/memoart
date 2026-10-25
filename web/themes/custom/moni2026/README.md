# moni2026 – Me.Mo Foto 2026 theme

Pure CSS téma: a `bootstrap5` base theme csak a markup miatt marad, a Bootstrap
CSS-t és JS-t a `moni2026_css_alter()` / `moni2026_js_alter()` kiszedi.
Nincs SCSS és nincs build lépés.

- `css/base.css`: tokenek (`--moni-*`), reset, tipográfia
- `css/layout.css`: container, grid, a template-ekben használt utility osztályok
- `css/components.css`: navbar, dropdown, tabs, pager, alerts, forms
- `css/drupal.css`: a bootstrap5 `style.css` Drupal-specifikus része Bootstrap-változók nélkül (a `css/components/*` marad)
- `css/theme.css`: a memo SCSS 1:1 portja
- A navbar collapse checkbox + label megoldással működik. A dropdown
  `:hover` / `:focus-within` alapon nyílik: a `<nolink>` szülő menüpont
  (Galériák) a `menu--main.html.twig`-ben fókuszálható `<span>`, így
  JS nélkül sem tölti újra az oldalt, és mobilon tapintásra nyílik.
- A bootstrap5 `css/components/*` (Drupal-komponens CSS) megmarad, csak a
  Bootstrap framework (`css/style.css`, `dist/`) kerül ki.

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
