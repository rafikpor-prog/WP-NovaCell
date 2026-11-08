<?php
/** Mock-based stage7 regression tests: NOT a real WordPress/hosting WCAG audit. */
declare(strict_types=1);
require __DIR__ . '/test-stage5.php';
function admin_url($p=''){return 'https://staging.example.test/wp-admin/'.ltrim((string)$p,'/');}
function get_edit_post_link($id,$context='display'){return admin_url('post.php?post='.$id.'&action=edit');}
function get_queried_object_id(){return 0;}
function is_singular(){return false;}
function get_the_title($p){return $p->post_title;}
function get_post_modified_time(...$x){return gmdate('c');}
function is_user_logged_in(){return true;}
function is_admin_bar_showing(){return true;}
function wp_trim_words($text,$num=55){return implode(" ",array_slice(preg_split("/\s+/",(string)$text),0,$num));}
$enSeed=require dirname(__DIR__).'/plugins/novacell-manager/data/english-content.php';
if(count($enSeed['records'])!==43)throw new RuntimeException('Expected 43 new English drafts, one original English document');
$updated=\NovaCell\Manager\English_Editorial::import();
if(($updated['updated']??0)!==43)throw new RuntimeException('English import incomplete: '.json_encode($updated));
$index=(array)get_option('nc_full_route_index');
$id=(int)$index['topic-1']['post_id'];
if(strlen((string)get_post_meta($id,'nc_en_body',true))<1200)throw new RuntimeException('Menopause English overview is too short');
foreach(['symptoms','causes','first_visit','diagnostics','locations','risks','preparation'] as $field){
 if(!get_post_meta($id,'nc_en_'.$field,true))throw new RuntimeException('Missing English structured field: '.$field);
}
$once=\NovaCell\Manager\English_Editorial::import();
if($once['updated']!==0)throw new RuntimeException('English importer not idempotent');
update_post_meta($id,'nc_en_body','<p>Manually edited physician wording.</p>');
$again=\NovaCell\Manager\English_Editorial::import();
if($again['updated']!==0 || !str_contains(get_post_meta($id,'nc_en_body',true),'Manually edited'))throw new RuntimeException('Manual English edit overwritten');
$force=\NovaCell\Manager\English_Editorial::import(true);
if($force['updated']<1 || str_contains(get_post_meta($id,'nc_en_body',true),'Manually edited'))throw new RuntimeException('Explicit reimport failed');
if(!get_post_meta($id,'_nc_en_backup_20261108',true))throw new RuntimeException('No backup for EN edit');
$r=nc_theme_resolve_path('/chce-zadbac-o-zdrowie/');
if($r!==array('prevention-index','pl'))throw new RuntimeException('Parent slug route still unresolved: '.json_encode($r));
$r=nc_theme_resolve_path('/en/preventive-health/');
if($r!==array('prevention-index','en'))throw new RuntimeException('English parent slug unresolved');
$edit=\NovaCell\Manager\Navigation_Hub::edit_target('ncfull-topic-1');
if(!str_contains($edit['url'],'post.php?post='.$id))throw new RuntimeException('Page-to-editor link incorrect');
$edit=\NovaCell\Manager\Navigation_Hub::edit_target('prevention-index');
if(!str_contains($edit['url'],'novacell-archives'))throw new RuntimeException('Archive-to-editor link incorrect');
$edit=\NovaCell\Manager\Navigation_Hub::edit_target('home');
if(!str_contains($edit['url'],'novacell-home-builder'))throw new RuntimeException('Home-to-editor link incorrect');
$GLOBALS['query']=['nc_view'=>'prevention-index','nc_lang'=>'en'];
ob_start();nc_theme_display_archive('prevention-index','en');$markup=ob_get_clean();
if(!str_contains($markup,'Preventive health') || !str_contains($markup,'nc-feature-card'))throw new RuntimeException('Archive renderer has no cards / EN text');
$enfaq=nc_theme_faq_extract((string)get_post_meta($id,'nc_en_body',true));
if(count($enfaq)<1)throw new RuntimeException('English FAQ not recognised');
echo "PASS stage7: 43 EN drafts, fields, manual protection, backlinks, 4 archive paths, FAQ, accessible archive rendering.\n";
