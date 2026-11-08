<?php
/** Public-repository checks: no private clinic source data required. */
declare(strict_types=1);
$root = dirname(__DIR__);
$files = [
  'plugins/novacell-manager/includes/class-navigation-hub.php' => [
    'Edytuj tę stronę', 'post_breadcrumbs', 'novacell-content-map', 'novacell-archives', 'nc_save_archives'
  ],
  'plugins/novacell-manager/includes/class-url-repair.php' => [
    'flush_rewrite_rules', 'wp_safe_remote_get', 'chce-zadbac-o-zdrowie', 'nc_route_mode'
  ],
  'plugins/novacell-manager/includes/class-english-editorial.php' => [
    'nc_en_title', 'nc_en_body', 'nc_en_excerpt', '_nc_en_backup_20261108'
  ],
  'themes/novacell/inc/render.php' => [
    'nc_theme_display_archive', 'breadcrumbs'
  ],
  'themes/novacell/inc/faq.php' => [
    '<details', '<summary'
  ],
];
foreach ($files as $relative => $checks) {
  $body = file_get_contents($root . '/' . $relative);
  if ($body === false) { throw new RuntimeException('Missing '. $relative); }
  foreach ($checks as $check) {
    if (strpos($body, $check) === false) {
      throw new RuntimeException('Missing '. $check . ' in ' . $relative);
    }
  }
}
// Confidential source exclusion is asserted separately using git ls-files in CI.
echo "PASS Stage 7 public checks: editing, breadcrumbs, archives, EN, FAQ.\n";
