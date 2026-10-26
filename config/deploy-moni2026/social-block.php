<?php

// A "social" custom blokk ikonjai a moni2026 SVG-kre. Futtatás: drush php:script ../config/deploy-moni2026/social-block.php
$blocks = \Drupal::entityTypeManager()->getStorage('block_content')
  ->loadByProperties(['uuid' => '66b3336d-9a36-4d22-a287-702f769e8d09']);
$block = reset($blocks);
if (!$block) {
  throw new \RuntimeException('A social blokk nem található.');
}
$block->set('body', [
  'value' => '<a href="https://www.facebook.com/memoartfoto"><img src="/themes/custom/moni2026/images/icon-facebook.svg" alt="Facebook" width="30" height="30"></a><a href="https://www.instagram.com/memoartfoto"><img src="/themes/custom/moni2026/images/icon-instagram.svg" alt="Instagram" width="30" height="30"></a>',
  'format' => 'full_html',
]);
$block->setNewRevision(TRUE);
$block->save();
echo "social blokk frissítve\n";
