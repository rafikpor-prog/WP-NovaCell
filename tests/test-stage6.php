<?php
/** Isolated smoke test, not a full WordPress or server test. */
declare(strict_types=1);
require __DIR__ . '/test-stage5.php';
function plugin_dir_url($path){return 'https://staging.example.test/wp-content/plugins/novacell-visual-editor/';}
function plugin_basename($path){return basename(dirname($path)) . '/' . basename($path);}
function admin_url($path=''){return 'https://staging.example.test/wp-admin/' . ltrim((string)$path,'/');}
function wp_count_posts($posttype){return (object) ['publish'=>count(get_posts(['post_type'=>$posttype]))];}

require dirname(__DIR__) . '/plugins/novacell-visual-editor/novacell-visual-editor.php';
$id=nc_visual_ensure_canvas();
if($id<=0 || 'page'!==get_post_type($id) || 'draft'!==get_post_status($id)) throw new RuntimeException('Visual canvas should be a private draft page');
if(nc_visual_ensure_canvas()!==$id) throw new RuntimeException('Visual canvas was duplicated');
if(!str_contains(nc_visual_edit_url(),'/wp-admin/post.php?post='.$id.'&action=edit')) throw new RuntimeException('Visual editor direct link invalid');
ob_start();\NovaCell\Manager\Admin::dashboard();$html=ob_get_clean();
foreach(['novacell-pages','novacell-home-builder','novacell-starter','novacell-preview-settings','novacell-diagnostics','novacell-visual-editor','novacell-readiness'] as $slug){
 if(!str_contains($html, 'admin.php?page='.$slug))throw new RuntimeException('Missing admin menu link: '.$slug);
}
if(count((array)get_option('nc_full_route_index',[]))<40)throw new RuntimeException('Routes lost during update');
$idx=(array)get_option('nc_full_route_index',[]);
$menopause=(int)$idx['topic-1']['post_id'];
if(!nc_theme_route_published('ncfull-topic-1'))throw new RuntimeException('Published record should have public route');
$GLOBALS['posts'][$menopause]->post_status='draft';
if(nc_theme_route_published('ncfull-topic-1'))throw new RuntimeException('Draft record should be blocked');
$GLOBALS['posts'][$menopause]->post_status='publish';
if(nc_theme_resolve_path('/novacell-pages/'))throw new RuntimeException('An admin slug must not be treated as a frontend page');
$summary=\NovaCell\Manager\Readiness::summary();
if($summary['routes']<40 || $summary['published']<40)throw new RuntimeException('Readiness summary incorrect');
if(!str_contains(file_get_contents(dirname(__DIR__).'/plugins/novacell-visual-editor/assets/editor.js'),'PluginSidebarMoreMenuItem'))throw new RuntimeException('Visual editor menu access missing');
echo "PASS stage6: admin URLs, direct Visual Editor, deduped draft canvas, route gates, readiness.\n";
