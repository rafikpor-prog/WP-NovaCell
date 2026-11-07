<?php
/** Mock-based smoke test; not a substitute for WordPress integration tests. */
declare(strict_types=1);
error_reporting(E_ALL);
define('ABSPATH','/tmp/wp/');
define('NC_THEME_PATH', dirname(__DIR__).'/themes/novacell');
$GLOBALS['hooks']=[];$GLOBALS['filters']=[];$GLOBALS['opt']=[];$GLOBALS['meta']=[];$GLOBALS['registry']=[];$GLOBALS['posts']=[];$GLOBALS['nextid']=100;
class WP_Post { public string $post_status='draft';public function __construct(public int $ID,public string $post_type,public string $post_title='',public string $post_excerpt='',public string $post_content='',public string $post_name=''){} }
class WP_Error{}
function plugin_dir_path($p){return dirname($p).'/';}
function get_template_directory(){return dirname(__DIR__).'/themes/novacell';}
function add_action(...$args){$GLOBALS['hooks'][]=$args[0];$GLOBALS['filters'][$args[0]][]=$args[1]??null;}
function add_filter(...$args){$GLOBALS['hooks'][]=$args[0];$GLOBALS['filters'][$args[0]][]=$args[1]??null;}
function register_activation_hook($p,$cb){$GLOBALS['activation']=$cb;}
function register_deactivation_hook($p,$cb){$GLOBALS['deactivation']=$cb;}
function get_bloginfo($x){return '6.8';}
function wp_die($x){throw new RuntimeException($x);}
function esc_html__($x,$d){return $x;}
function esc_html($x){return htmlspecialchars((string)$x,ENT_QUOTES,'UTF-8');}
function esc_attr($x){return esc_html($x);}
function esc_url($x){return esc_html($x);}
function esc_url_raw($x){return (string)$x;}
function wp_kses_post($x){return (string)$x;}
function wpautop($x){return '<p>'.$x.'</p>';}
function wp_strip_all_tags($x,$br=false){return strip_tags((string)$x);}
function absint($x){return abs((int)$x);}
function sanitize_key($x){return preg_replace('/[^a-z0-9_\-]/','',strtolower((string)$x));}
function sanitize_title($x){return sanitize_key(str_replace(' ','-',(string)$x));}
function sanitize_text_field($x){return strip_tags((string)$x);}
function sanitize_textarea_field($x){return strip_tags((string)$x);}
function wp_unslash($x){return $x;}
function is_wp_error($x){return $x instanceof WP_Error;}
function update_post_meta($id,$key,$value){$GLOBALS['meta'][$id][$key]=$value;return true;}
function metadata_exists($kind,$id,$key){return array_key_exists($key,$GLOBALS['meta'][$id]??[]);}
function get_post_meta($id,$key,$single=true){return $GLOBALS['meta'][$id][$key]??($single?'':[]);}
function get_option($key,$default=false){return $GLOBALS['opt'][$key]??$default;}
function update_option($key,$value,$autoload=null){$GLOBALS['opt'][$key]=$value;return true;}
function add_option($key,$value,$deprecated='',$autoload=null){if(array_key_exists($key,$GLOBALS['opt']))return false;return update_option($key,$value);}
function get_role($role){return new class{public function add_cap($cap){} };}
function register_post_type($type,$opts){$GLOBALS['registry'][$type]=$opts;}
function register_taxonomy(...$x){}
function register_post_meta(...$x){}
function flush_rewrite_rules(){}
function current_user_can($c){return true;}
function wp_is_post_revision($id){return false;}

function wp_get_nav_menu_object($name){return false;}
function wp_create_nav_menu($name){return 2;}
function wp_get_nav_menu_items($id,$args=[]){return [];}
function wp_update_nav_menu_item($id,$item,$args){return 2;}
function current_theme_supports($name){return false;}
function get_registered_nav_menus(){return [];}
function get_theme_mod($name,$default=[]){return $default;}
function set_theme_mod($name,$value){}
function wp_get_attachment_image_src(){return false;}
function home_url($path=''){return 'https://staging.example.test'.$path;}
function add_query_arg($args,$url){return $url.(str_contains($url,'?')?'&':'?').http_build_query($args);}
function post_type_exists($type){return isset($GLOBALS['registry'][$type]);}
function wp_insert_post($data,$wp_error=false){$id=++$GLOBALS['nextid'];$p=new WP_Post($id,$data['post_type'],$data['post_title'],$data['post_excerpt']??'',$data['post_content']??'',$data['post_name']??'');$p->post_status=$data['post_status']??'draft';$GLOBALS['posts'][$id]=$p;return $id;}
function wp_update_post($update,$wp_error=false){$id=(int)$update['ID'];if(!isset($GLOBALS['posts'][$id]))return new WP_Error;foreach(['post_title','post_status','post_excerpt','post_content'] as $k)if(isset($update[$k]))$GLOBALS['posts'][$id]->$k=$update[$k];return $id;}
function get_post_status($id){return $GLOBALS['posts'][$id]->post_status??false;}
function get_post_field($field,$id){return $GLOBALS['posts'][$id]->$field??'';}
function delete_post_meta($id,$key){unset($GLOBALS['meta'][$id][$key]);}
function wp_slash($x){return $x;}
function did_action($x){return 0;}
function get_post($id){return $GLOBALS['posts'][$id]??null;}
function get_post_type($id){return $GLOBALS['posts'][$id]->post_type??'post';}
function get_posts($opts){
 $matched=[];foreach($GLOBALS['posts'] as $id=>$post){
  if($post->post_type!==($opts['post_type']??''))continue;
  if(isset($opts['meta_key']) && ($GLOBALS['meta'][$id][$opts['meta_key']]??null)!==($opts['meta_value']??null))continue;
  if(isset($opts['meta_query']) && isset($opts['meta_query'][0]['key']) && !array_key_exists($opts['meta_query'][0]['key'],$GLOBALS['meta'][$id]??[]))continue;
  $matched[]=($opts['fields']??'')==='ids'?$id:$post;
 }
 $limit=(int)($opts['posts_per_page']??$opts['numberposts']??-1);
 return $limit<0?$matched:array_slice($matched,0,$limit);
}

function is_admin(){return false;}
function get_query_var($name,$default=''){return $GLOBALS['query'][$name]??$default;}
function untrailingslashit($x){return rtrim((string)$x,'/');}
function wp_parse_url($x,$component=-1){return parse_url((string)$x,$component);}
function is_front_page(){return true;}
function is_home(){return true;}
function has_post_thumbnail($post){return false;}
function get_the_post_thumbnail(...$x){return '';}
function status_header($s){}
function add_rewrite_rule(...$x){}
function wp_safe_redirect($x){}
function wp_verify_nonce(...$x){return true;}
function gmdate_dummy(){}
function get_template_directory_uri(){return 'https://staging.example.test/wp-content/themes/novacell';}


require dirname(__DIR__).'/plugins/novacell-manager/novacell-manager.php';
\NovaCell\Manager\Content_Types::register();
\NovaCell\Manager\Metadata::register();
$before=\NovaCell\Manager\Starter_Import::seed();
if($before['created']!==10)throw new RuntimeException('Missing 10 baseline items');
$import=\NovaCell\Manager\Full_Import::import();
if($import['created']<165)throw new RuntimeException('Too few imported documents: '.json_encode($import));
$index=get_option('nc_full_route_index',[]);
if(count($index)<40)throw new RuntimeException('Too few routes: '.count($index));
$english=0; $published=0; $services=0;
foreach($GLOBALS['posts'] as $post){
 if($post->post_status==='publish')$published++;
 if($post->post_type==='nc_service')$services++;
}
if($services!==120)throw new RuntimeException('Expected 120 service rows, found '.$services);
if($published<175)throw new RuntimeException('Too few published: '.$published);
if(empty($index['topic-1']['post_id']))throw new RuntimeException('Menopause pathway missing');
$id=$index['topic-1']['post_id'];
if(strlen(get_post($id)->post_content)<5000)throw new RuntimeException('Incomplete long content');
if(get_post($id)->post_status!=='publish')throw new RuntimeException('Legacy content not published');
$again=\NovaCell\Manager\Full_Import::import();
if($again['created']!==0)throw new RuntimeException('Import is not idempotent: '.json_encode($again));
require dirname(__DIR__).'/themes/novacell/inc/routes.php';
require dirname(__DIR__).'/themes/novacell/inc/render.php';
require dirname(__DIR__).'/themes/novacell/inc/faq.php';
if(!isset(nc_theme_routes()['ncfull-topic-1']))throw new RuntimeException('Missing dynamic route');
if(!str_contains(nc_theme_link('ncfull-topic-28','en'),'nc_view=ncfull-topic-28') || !str_contains(nc_theme_link('ncfull-topic-28','en'),'nc_lang=en'))throw new RuntimeException('Safe EN price link not generated');
$GLOBALS['query']=['nc_view'=>'ncfull-topic-1','nc_lang'=>'pl'];
ob_start();nc_theme_display_full_document('topic-1','pl');$pl=ob_get_clean();
if(!str_contains($pl,'Perimenopauza') || !str_contains($pl, 'nc-faq-item'))throw new RuntimeException('Full content or FAQ not rendered');
if(!str_contains($pl, '<table>'))throw new RuntimeException('Tables absent');
$r = nc_theme_resolve_path('/z-czym-pomagamy/menopauza/');
if($r!==['ncfull-topic-1','pl'])throw new RuntimeException('PL route must resolve to full source, got '.json_encode($r));
$r = nc_theme_resolve_path('/en/help-with/menopause/');
if($r!==['ncfull-topic-1','en'])throw new RuntimeException('EN route must resolve to full source, got '.json_encode($r));
$r = nc_theme_resolve_path('/does-not-exist/');
if($r)throw new RuntimeException('Router hijacks unknown path');
$_SERVER['REQUEST_URI']='/z-czym-pomagamy/menopauza/';
$wp=(object)['request'=>'z-czym-pomagamy/menopauza','query_vars'=>['error'=>'404','pagename'=>'menopauza']];
foreach($GLOBALS['filters']['parse_request']??[] as $callback){$callback($wp);}
if(($wp->query_vars['nc_view']??'')!=='ncfull-topic-1'||isset($wp->query_vars['error']))throw new RuntimeException('Fallback router failed');
$GLOBALS['query']=['nc_view'=>'ncfull-topic-1','nc_lang'=>'pl'];
$mockQuery=new class {public bool $is_404=true; public function is_main_query(){return true;} public function get($field){return $GLOBALS['query'][$field]??'';}};
$handled=false;foreach($GLOBALS['filters']['pre_handle_404']??[] as $callback){$handled=$callback($handled,$mockQuery);}
if(!$handled||$mockQuery->is_404)throw new RuntimeException('404 suppression failed');
$template='index.php';foreach($GLOBALS['filters']['template_include']??[] as $callback){$template=$callback($template);}
if(!str_ends_with($template,'route.php'))throw new RuntimeException('Router did not include page template');
$faq=nc_theme_faq_extract(get_post($id)->post_content);
if(count($faq)<4)throw new RuntimeException('FAQ source conversion failed: '.count($faq));
if(!str_contains(nc_theme_faq_replace(get_post($id)->post_content,'pl'),'<details class="nc-faq-item">'))throw new RuntimeException('Accessible native accordion absent');
// Etap 5: publiczna widoczność bez fikcyjnej akceptacji, tryb bez .htaccess oraz wielojęzyczność.
\NovaCell\Manager\Installer::activate();
if(get_option('nc_public_preview')!=='1') throw new RuntimeException('Installer did not enable public editorial preview');
if(get_option('nc_site_approved',false)) throw new RuntimeException('Installation must not falsely approve medical content');
if(get_option('nc_route_mode')!=='safe') throw new RuntimeException('Safe URL mode not configured');
if(!nc_theme_can_preview()) throw new RuntimeException('Public editorial preview should be visible');
if(!str_starts_with(nc_theme_link('ncfull-topic-1','pl'),'https://staging.example.test/?nc_view='))throw new RuntimeException('URL still requires rewrite rules');
update_option('nc_route_mode','pretty');
update_option('permalink_structure','/%postname%/');
if(!str_contains(nc_theme_link('ncfull-topic-1','pl'),'/z-czym-pomagamy/menopauza/'))throw new RuntimeException('Pretty mode broken');
update_option('nc_route_mode','safe');
$GLOBALS['query']=['nc_view'=>'ncfull-topic-1','nc_lang'=>'pl'];
ob_start();nc_theme_display_route('ncfull-topic-1','pl');$public=ob_get_clean();
if(!str_contains($public,'Perimenopauza'))throw new RuntimeException('Public route has no content');
if(!str_contains($public,'zatwierdzenia'))throw new RuntimeException('Missing medical review disclosure');
if(!str_contains($public,'nc-faq-item'))throw new RuntimeException('Accordion not rendered');
if(!isset($GLOBALS['activation']))throw new RuntimeException('Installer hook missing');
echo 'PASS stage5: '.$published.' published records, '.$services.' price services, '.count($index).' routes, 0 duplicate reimports, complete Polish text, dynamic EN route.\n';
