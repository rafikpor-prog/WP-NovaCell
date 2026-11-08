<?php
/** English-language clinical editorial importer with reversible changes. */
namespace NovaCell\Manager;
defined('ABSPATH') || exit;
final class English_Editorial {
    private const VERSION = 'en-clinical-editorial-2026-11-08-v1';
    public static function boot(): void {
        add_action('admin_menu',array(self::class,'menu'));
        add_action('admin_init',array(self::class,'maybe_upgrade'),75);
        add_action('admin_post_nc_import_english',array(self::class,'action'));
    }
    public static function menu(): void {
        add_submenu_page('novacell-manager','Angielskie opisy medyczne','Treści angielskie',
            'manage_options','novacell-en-editorial',array(self::class,'screen'));
    }
    private static function data(): array {
        $p=NC_MANAGER_PATH.'data/english-content.php';
        if(!is_readable($p))return array();
        $d=require $p;
        return is_array($d) && 1===(int)($d['schema']??0) ? $d : array();
    }
    public static function maybe_upgrade(): void {
        if(!current_user_can('manage_options'))return;
        if(self::VERSION !== (string)get_option('nc_en_editorial_version','')) {
            self::import();
        }
    }
    /** Only replace existing legacy values when they still match the original seed. */
    private static function legacy_seed_value(int $post_id,string $meta,string $existing): bool {
        $seed_key=(string)get_post_meta($post_id,'_nc_seed_key',true);
        if(!$seed_key || ''===trim($existing))return false;
        $seed=Starter_Import::data();
        foreach((array)($seed['records']??array()) as $row){
            $expected_key='novacell-2026:'.sanitize_key((string)($row['kind']??'')).':'.sanitize_title((string)($row['slug']??''));
            if($expected_key!==$seed_key)continue;
            $expected='';
            if('nc_en_title'===$meta)$expected=(string)($row['en_title']??'');
            elseif('nc_en_excerpt'===$meta)$expected=(string)($row['en_lead']??'');
            elseif('nc_en_body'===$meta)$expected=(string)($row['en_body']??$row['en_lead']??'');
            elseif(str_starts_with($meta,'nc_en_')) {
                $field=substr($meta,6);
                $expected=(string)($row['en_sections'][$field]??'');
            }
            $plain=static function($x): string {
                return trim(preg_replace('/\s+/u',' ',html_entity_decode(wp_strip_all_tags((string)$x),ENT_QUOTES,'UTF-8')));
            };
            return ''!==$plain($expected) && $plain($existing)===$plain($expected);
        }
        return false;
    }
    public static function import(bool $force=false): array {
        if(!current_user_can('manage_options'))return array('error'=>'Brak uprawnień.');
        $source=self::data();
        if(!$source)return array('error'=>'Brak chronionej paczki angielskich tekstów.');
        $result=array('updated'=>0,'preserved'=>0,'missing'=>array(),'fields'=>0,'errors'=>array());
        foreach((array)$source['records'] as $key=>$entry) {
            if(!is_array($entry))continue;
            $key=sanitize_key((string)$key);
            $find=get_posts(array('post_type'=>array_merge(array_keys(Content_Types::types()),array('post','page')),'post_status'=>'any','posts_per_page'=>1,'fields'=>'ids',
                'meta_query'=>array(array('key'=>'_nc_full_source_key','value'=>$key,'compare'=>'='))));
            if(!$find) { $result['missing'][]=$key; continue; }
            $id=absint($find[0]);
            if(!current_user_can('edit_post',$id))continue;
            if(!metadata_exists('post',$id,'_nc_en_backup_20261108')) {
                $old=array('title'=>get_post_meta($id,'nc_en_title',true),
                    'lead'=>get_post_meta($id,'nc_en_excerpt',true),'body'=>get_post_meta($id,'nc_en_body',true),'fields'=>array());
                foreach(array_keys((array)($entry['fields']??array())) as $f) {
                    $old['fields'][$f]=get_post_meta($id,'nc_en_'.sanitize_key($f),true);
                }
                update_post_meta($id,'_nc_en_backup_20261108',$old);
            }
            $changes=0;
            $proposed=array('nc_en_title'=>(string)($entry['title']??''),
                'nc_en_excerpt'=>(string)($entry['lead']??''),'nc_en_body'=>(string)($entry['body']??''));
            foreach((array)($entry['fields']??array()) as $f=>$value) {
                $proposed['nc_en_'.sanitize_key((string)$f)]=(string)$value;
            }
            $auto=(array)get_post_meta($id,'_nc_en_auto_hashes',true);
            foreach($proposed as $meta=>$value) {
                if(''===trim($value))continue;
                $existing=(string)get_post_meta($id,$meta,true);
                $was_generated=isset($auto[$meta]) && hash_equals((string)$auto[$meta],hash('sha256',$existing));
                if(!$force && ''!==trim($existing) && !$was_generated && !self::legacy_seed_value($id,$meta,$existing)) { ++$result['preserved']; continue; }
                $clean= 'nc_en_title'===$meta ? sanitize_text_field($value)
                    : ('nc_en_excerpt'===$meta ? sanitize_textarea_field($value) : wp_kses_post($value));
                if($clean!==$existing) {
                    update_post_meta($id,$meta,$clean);
                    $changes++;++$result['fields'];
                }
                $auto[$meta]=hash('sha256',$clean);
            }
            update_post_meta($id,'_nc_en_auto_hashes',$auto);
            update_post_meta($id,'_nc_en_editorial_revision',self::VERSION);
            update_post_meta($id,'_nc_en_editorial_status','requires_medical_review');
            if(!empty($entry['legacy_sources'])){
                update_post_meta($id,'_nc_en_source_references',array_map('esc_url_raw',(array)$entry['legacy_sources']));
            }
            // Publishing editorial drafts is a separate concept from their approval.
            if('publish'!==get_post_status($id)) {
                wp_update_post(array('ID'=>$id,'post_status'=>'publish'));
            }
            if($changes)++$result['updated'];
        }
        update_option('nc_en_editorial_version',self::VERSION,false);
        update_option('nc_en_editorial_report',$result,false);
        return $result;
    }
    public static function action(): void {
        if(!current_user_can('manage_options'))wp_die('Brak uprawnień.');
        check_admin_referer('nc_import_english');
        $force=isset($_POST['overwrite_manual']) && '1'===$_POST['overwrite_manual'];
        self::import($force);
        wp_safe_redirect(admin_url('admin.php?page=novacell-en-editorial&updated=1'));exit;
    }
    public static function screen(): void {
        if(!current_user_can('manage_options'))return;
        $data=self::data();$report=(array)get_option('nc_en_editorial_report',array());
        echo '<div class="wrap"><h1>Angielskie teksty medyczne NOVACELL</h1>';
        echo '<p>Opracowano dwujęzyczne pola WYSIWYG na podstawie zatwierdzonego kierunku treści z dokumentacji PL i wybranych starszych artykułów EN. Dawna witryna zawierała także nieudokumentowane twierdzenia o skuteczności, których nie przepisano. Wszystkie nowe tłumaczenia wymagają weryfikacji medycznej i językowej.</p>';
        echo '<p><strong>Rekordów z przygotowanymi polami EN:</strong> '.esc_html((string)count((array)($data['records']??array()))).
            ' (plus istniejąca pełna strona pacjentów zagranicznych z dokumentu źródłowego).</p>';
        echo '<p><strong>Zmodyfikowane rekordy:</strong> '.esc_html((string)($report['updated']??0)).
            '; <strong>nowe pola:</strong> '.esc_html((string)($report['fields']??0)).
            '; <strong>pola pozostawione bez nadpisania:</strong> '.esc_html((string)($report['preserved']??0)).'.</p>';
        if(!empty($report['missing']))echo '<p role="status">Nie znaleziono rekordów: '.esc_html(implode(', ',array_slice((array)$report['missing'],0,20))).'</p>';
        echo '<p><a class="button" href="'.esc_url(admin_url('admin.php?page=novacell-content-map')).'">Znajdź i edytuj teksty PL/EN</a></p>';
        echo '<form action="'.esc_url(admin_url('admin-post.php')).'" method="post">';wp_nonce_field('nc_import_english');
        echo '<input type="hidden" name="action" value="nc_import_english"><p><label><input type="checkbox" name="overwrite_manual" value="1"> Nadpisz także ręcznie zmienione angielskie pola (kopie poprzednich wartości pozostają w bazie)</label></p>';
        echo '<button class="button button-primary" type="submit">Uzupełnij brakujące angielskie pola</button></form>';
        echo '<p>Nie wprowadzono nowych kwot ani deklaracji pełnego zatwierdzenia klinicznego. Opublikowany rekord WordPress nadal może mieć status „Wymaga recenzji”.</p></div>';
    }
}
