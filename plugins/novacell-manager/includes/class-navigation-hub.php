<?php
/**
 * NOVACELL Manager: locate every source and edit it from its public route.
 * @package NovaCellManager
 */
namespace NovaCell\Manager;
defined( 'ABSPATH' ) || exit;

final class Navigation_Hub {
    public static function boot(): void {
        add_action( 'admin_menu', array( self::class, 'menu' ), 10 );
        add_action( 'admin_bar_menu', array( self::class, 'bar' ), 95 );
        add_action( 'admin_notices', array( self::class, 'breadcrumbs' ), 2 );
        add_action( 'edit_form_top', array( self::class, 'post_breadcrumbs' ), 2 );
        add_action( 'admin_enqueue_scripts', array( self::class, 'assets' ) );
        add_action( 'admin_post_nc_save_archives', array( self::class, 'save_archives' ) );

        add_action( 'wp_footer', static function (): void {
            if ( !is_user_logged_in() || !current_user_can('edit_pages') || is_admin_bar_showing() ) { return; }
            $route = Navigation_Hub::current_route();
            $target = ( ! $route && is_singular() && current_user_can('edit_post',get_queried_object_id()) )
                ? array('url'=>get_edit_post_link(get_queried_object_id(),'raw'))
                : Navigation_Hub::edit_target($route);
            echo '<a class="nc-admin-edit-fallback" href="'.esc_url($target['url']).'">Edytuj tę stronę</a>';
        }, 99 );

    }

    public static function menu(): void {
        add_submenu_page( 'novacell-manager', 'Znajdź i edytuj treść', 'Znajdź treść',
            'manage_options', 'novacell-content-map', array( self::class, 'map' ) );
        add_submenu_page( 'novacell-manager', 'Katalogi problemów i metod', 'Strony katalogów',
            'manage_options', 'novacell-archives', array( self::class, 'archive_editor' ) );
    }

    public static function assets(): void {
        if ( ! is_admin() ) { return; }
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ( $screen && ( strpos((string)$screen->id,'novacell') !== false || $screen->base === 'post' ) ) {
            wp_enqueue_style( 'nc-navigation-hub', plugins_url( 'assets/navigation.css', NC_MANAGER_FILE ), array(), NC_MANAGER_VERSION );
            wp_enqueue_script( 'nc-navigation-hub', plugins_url( 'assets/navigation.js', NC_MANAGER_FILE ), array(), NC_MANAGER_VERSION, true );
        }
    }

    public static function is_known_route( string $slug ): bool {
        return isset( self::route_map()[$slug] );
    }

    public static function route_map(): array {
        $routes = array();
        if ( function_exists( 'nc_theme_routes' ) ) {
            $routes = (array) \nc_theme_routes();
        }
        if ( ! $routes ) {
            foreach ( (array)get_option('nc_full_route_index',array()) as $key=>$row ) {
                $routes['ncfull-' . sanitize_key( (string) $key )] = array(
                    'pl'=>(string)($row['pl'] ?? ''), 'en'=>(string)($row['en'] ?? '')
                );
            }
        }
        return $routes;
    }

    private static function current_route(): string {
        $key = function_exists('nc_theme_route_key') ? (string) \nc_theme_route_key() : '';
        if ( $key ) { return sanitize_key($key); }
        // Fallback only for routes explicitly registered by the theme.
        if ( function_exists('nc_theme_resolve_path') && function_exists('nc_theme_requested_path') ) {
            $match = \nc_theme_resolve_path(\nc_theme_requested_path());
            return sanitize_key((string)($match[0] ?? ''));
        }
        return '';
    }

    public static function edit_target( string $key ): array {
        $key = sanitize_key( $key );
        $index = (array)get_option( 'nc_full_route_index', array() );
        if ( str_starts_with( $key, 'ncfull-' ) ) {
            $source = substr($key, 7);
            $id = absint($index[$source]['post_id'] ?? 0);
            if ($id && current_user_can('edit_post', $id)) {
                return array('url'=>get_edit_post_link($id,'raw'), 'label'=>'Edytuj tekst i pola PL/EN', 'type'=>'record', 'id'=>$id);
            }
        }
        if ( 'home' === $key || '' === $key ) {
            return array('url'=>admin_url('admin.php?page=novacell-home-builder'), 'label'=>'Edytuj stronę główną', 'type'=>'home');
        }
        if ( in_array($key,array('help-index','prevention-index','aesthetics-index','methods-index'),true) ) {
            return array('url'=>add_query_arg(array('nc_archive'=>$key),admin_url('admin.php?page=novacell-archives')),
                'label'=>'Edytuj stronę katalogu', 'type'=>'archive');
        }
        foreach ((array)get_option('nc_route_pages',array()) as $item) {
            if (sanitize_key((string)($item['id'] ?? '')) === $key) {
                return array('url'=>add_query_arg(array('nc_page'=>$key),admin_url('admin.php?page=novacell-pages')),
                    'label'=>'Edytuj podstronę PL/EN', 'type'=>'page');
            }
        }
        // Some earlier routes point directly to the source records.
        $legacy = array('menopause'=>'topic-1','diagnostics'=>'topic-8','fatigue'=>'topic-7',
            'jakub-rzepka'=>'profile-jakub-rzepka','katarzyna-moson-rzepka'=>'profile-katarzyna-moson-rzepka',
            'przemyslaw-szalanski'=>'profile-przemyslaw-szalanski');
        if (isset($legacy[$key])) { return self::edit_target('ncfull-' . $legacy[$key]); }
        return array('url'=>admin_url('admin.php?page=novacell-content-map'), 'label'=>'Znajdź źródło treści', 'type'=>'map');
    }

    public static function bar( $bar ): void {
        if (is_admin() || !is_user_logged_in() || !current_user_can('edit_pages')) { return; }
        $key = self::current_route();
        if ( ! $key && is_singular() && current_user_can('edit_post',get_queried_object_id()) ) {
            $target=array('url'=>get_edit_post_link(get_queried_object_id(),'raw'), 'label'=>'Edytuj w WordPress');
        } else {
            $target = self::edit_target($key);
        }
        $bar->add_node( array(
            'id'=>'nc-edit-this-page', 'title'=>'Edytuj tę stronę',
            'href'=>esc_url($target['url']),
            'meta'=>array('title'=>'Otwórz właściwy edytor treści NOVACELL'),
        ) );
        $bar->add_node( array(
            'id'=>'nc-find-content', 'parent'=>'nc-edit-this-page',
            'title'=>'Znajdź wszystkie treści',
            'href'=>admin_url('admin.php?page=novacell-content-map'),
        ) );
        if ('home' === $key) {
            foreach ( (array)get_option('nc_home_sections',array()) as $section ) {
                if ( !is_array($section) || empty($section['id']) || empty($section['enabled']) ) {continue;}
                $id = sanitize_key((string)$section['id']);
                $bar->add_node(array('id'=>'nc-edit-section-'.$id,'parent'=>'nc-edit-this-page',
                    'title'=>'Sekcja: '.sanitize_text_field((string)($section['pl']['title'] ?? $id)),
                    'href'=>add_query_arg(array('nc_section'=>$id),admin_url('admin.php?page=novacell-home-builder'))));
            }
        }
    }

    public static function breadcrumbs(): void {
        if (!current_user_can('edit_pages')) { return; }
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash((string)$_GET['page'])) : '';
        $labels = array(
            'novacell-manager'=>'Pulpit','novacell-home-builder'=>'Strona główna i sekcje',
            'novacell-pages'=>'Podstrony PL/EN','novacell-starter'=>'Materiały startowe',
            'novacell-full-import'=>'Pełne treści','novacell-preview-settings'=>'Widoczność',
            'novacell-diagnostics'=>'Trasy i podgląd','novacell-readiness'=>'Testy uruchomieniowe',
            'novacell-content-map'=>'Znajdź treść','novacell-archives'=>'Strony katalogów',
            'novacell-main-menu'=>'Menu PL/EN','novacell-clean-content'=>'Czyste treści',
            'novacell-en-editorial'=>'Treści angielskie',
            'novacell-repair-urls'=>'Naprawa linków 404',
        );
        if (!isset($labels[$page])) {return;}
        echo '<nav class="nc-admin-breadcrumbs" aria-label="Ścieżka nawigacyjna w panelu NOVACELL"><ol>';
        echo '<li><a href="'.esc_url(admin_url()).'">WordPress</a></li>';
        if ($page !== 'novacell-manager') {
            echo '<li><a href="'.esc_url(admin_url('admin.php?page=novacell-manager')).'">NOVACELL Manager</a></li>';
        }
        echo '<li aria-current="page">'.esc_html($labels[$page]).'</li></ol></nav>';
    }

    public static function post_breadcrumbs( \WP_Post $post ): void {
        if (!isset(Content_Types::types()[$post->post_type]) || !current_user_can('edit_post',$post->ID)) {return;}
        $title = get_the_title($post);
        echo '<nav class="nc-admin-breadcrumbs" aria-label="Ścieżka do edytowanej treści"><ol>';
        echo '<li><a href="'.esc_url(admin_url('admin.php?page=novacell-manager')).'">NOVACELL Manager</a></li>';
        echo '<li><a href="'.esc_url(admin_url('admin.php?page=novacell-content-map')).'">Znajdź treść</a></li>';
        echo '<li><a href="'.esc_url(admin_url('edit.php?post_type='.$post->post_type)).'">'.esc_html(Content_Types::types()[$post->post_type]).'</a></li>';
        echo '<li aria-current="page">'.esc_html($title).'</li></ol></nav>';
        $route_key=(string)get_post_meta($post->ID,'_nc_full_source_key',true);
        if ($route_key && function_exists('nc_theme_link')) {
            echo '<p class="nc-editor-visit"><a class="button button-secondary" target="_blank" rel="noopener" href="'.esc_url(\nc_theme_link('ncfull-'.$route_key,'pl')).'">Zobacz na stronie PL</a> ';
            if ('' !== trim((string)get_post_meta($post->ID,'nc_en_body',true))) {
                echo '<a class="button button-secondary" target="_blank" rel="noopener" href="'.esc_url(\nc_theme_link('ncfull-'.$route_key,'en')).'">Zobacz na stronie EN</a>';
            }
            echo '</p>';
        }
    }

    public static function map(): void {
        if (!current_user_can('manage_options')) {return;}
        $routes=(array)get_option('nc_full_route_index',array());
        $base=admin_url('admin.php?page=novacell-content-map');
        $text=isset($_GET['nc_search'])?sanitize_text_field(wp_unslash((string)$_GET['nc_search'])):'';
        echo '<div class="wrap nc-content-map"><h1>Znajdź i edytuj każdą treść NOVACELL</h1>';
        echo '<p>Wyszukuj po tytule, nazwie metody, adresie strony albo numerze tematu. Otwórz dokładnie ten rekord, który zasila widok frontendowy.</p>';
        echo '<form method="get" action="'.esc_url(admin_url('admin.php')).'" role="search"><input type="hidden" name="page" value="novacell-content-map">';
        echo '<label for="nc_content_search">Szukaj treści</label> <input type="search" class="regular-text" id="nc_content_search" name="nc_search" value="'.esc_attr($text).'"> ';
        echo '<button class="button button-primary" type="submit">Szukaj</button></form>';
        echo '<div class="nc-map-shortcuts"><a class="button" href="'.esc_url(admin_url('admin.php?page=novacell-home-builder')).'">9 sekcji strony głównej</a> <a class="button" href="'.esc_url(admin_url('admin.php?page=novacell-archives')).'">Strony katalogów</a> <a class="button" href="'.esc_url(admin_url('admin.php?page=novacell-pages')).'">Podstrony PL/EN</a></div>';
        echo '<table class="widefat striped"><caption class="screen-reader-text">Adres strony, tytuł, język angielski i edycja treści</caption><thead><tr><th scope="col">Tytuł i typ</th><th scope="col">Adres PL</th><th scope="col">Wersja EN</th><th scope="col">Edycja</th></tr></thead><tbody>';
        $count=0;
        foreach($routes as $source=>$row){
            $id=absint($row['post_id']??0);$post=$id?get_post($id):null;
            if(!$post)continue;
            $title=(string)$post->post_title;$pl=(string)($row['pl']??'');$en=(string)($row['en']??'');
            if($text && false===stripos($title,$text) && false===stripos($pl,$text) && false===stripos($source,$text) && false===stripos($en,$text))continue;
            ++$count;
            $edit=get_edit_post_link($id,'raw');
            $en_ready=''!==trim(wp_strip_all_tags((string)get_post_meta($id,'nc_en_body',true)));
            echo '<tr><th scope="row">'.esc_html($title).'<br><small>'.esc_html($post->post_type).' · '.esc_html((string)$source).'</small></th>';
            echo '<td><a href="'.esc_url(function_exists('nc_theme_link')?\nc_theme_link('ncfull-'.$source,'pl'):home_url($pl)).'" target="_blank" rel="noopener">'.esc_html($pl).'</a></td>';
            echo '<td>'.($en_ready?'<span class="nc-status">Opracowano, do recenzji</span>':'Brak tłumaczenia').'<br><small>'.esc_html($en).'</small></td>';
            echo '<td><a class="button" href="'.esc_url($edit).'">Edytuj PL/EN</a></td></tr>';
        }
        // Services, packages and reusable FAQ do not have their own public route,
        // but are still editable records used by medical pathways and price lists.
        $routed_ids=array();
        foreach($routes as $row){ if(!empty($row['post_id']))$routed_ids[]=absint($row['post_id']); }
        foreach(array('nc_service','nc_package','nc_problem','nc_faq') as $type){
            if(!isset(Content_Types::types()[$type]))continue;
            $other=get_posts(array('post_type'=>$type,'post_status'=>'any','posts_per_page'=>250,'orderby'=>'title','order'=>'ASC'));
            foreach($other as $post){
                if(in_array($post->ID,$routed_ids,true))continue;
                $title=(string)$post->post_title;
                if($text && false===stripos($title,$text) && false===stripos($type,$text))continue;
                $edit=get_edit_post_link($post->ID,'raw');if(!$edit)continue;
                ++$count;
                echo '<tr><th scope="row">'.esc_html($title).'<br><small>'.esc_html($type).'</small></th><td>Wykorzystywana w innych sekcjach</td>';
                echo '<td>'.(''!==trim((string)get_post_meta($post->ID,'nc_en_title',true))?'Tytuł EN dostępny':'Do opracowania').'</td>';
                echo '<td><a class="button" href="'.esc_url($edit).'">Edytuj rekord</a></td></tr>';
            }
        }
        if(!$count)echo '<tr><td colspan="4">Nie znaleziono pasujących rekordów.</td></tr>';
        echo '</tbody></table><p>Pozycji: '.esc_html((string)$count).'. Zmiany w rekordzie aktualizują jego adresy i powiązania. Zmiana języka EN nie wymaga tworzenia nowego wpisu.</p></div>';
    }

    public static function archive_defaults(): array {
        return array(
            'help-index'=>array('pl'=>array('title'=>'Z czym możemy Ci pomóc?','lead'=>'Wybierz temat, który odpowiada Twoim objawom lub pytaniom o zdrowie.','body'=>'<p>Każda ścieżka rozpoczyna się od konsultacji lekarskiej i indywidualnej kwalifikacji.</p>'),
                                'en'=>array('title'=>'How can we help?','lead'=>'Choose a topic that reflects your symptoms or health concerns.','body'=>'<p>Care begins with a clinical assessment and an individual discussion of appropriate options.</p>')),
            'prevention-index'=>array('pl'=>array('title'=>'Chcę zadbać o zdrowie','lead'=>'Profilaktyka, diagnostyka i indywidualny plan dalszego postępowania.','body'=>'<p>Omawiamy historię zdrowia, czynniki ryzyka i badania, które mogą być pomocne w podejmowaniu decyzji.</p>'),
                                    'en'=>array('title'=>'Preventive health','lead'=>'Health assessment, prevention and a personalised care plan.','body'=>'<p>We review your health history, risk factors and investigations that may support clinical decisions.</p>')),
            'aesthetics-index'=>array('pl'=>array('title'=>'Wygląd i estetyka','lead'=>'Konsultacje dotyczące skóry, sylwetki i leczenia chirurgicznego.','body'=>'<p>Przed zabiegiem omawiamy wskazania, możliwe korzyści, ograniczenia i ryzyko.</p>'),
                                   'en'=>array('title'=>'Aesthetics and appearance','lead'=>'Consultations about skin, body contouring and surgical options.','body'=>'<p>Before a procedure, your clinician explains suitability, expected limitations, alternatives and risks.</p>')),
            'methods-index'=>array('pl'=>array('title'=>'Metody leczenia A–Z','lead'=>'Poznaj sposoby postępowania, ich zastosowania i ograniczenia.','body'=>'<p>Nie wszystkie metody są standardowym leczeniem. Odpowiedni status i poziom dostępnych dowodów wyjaśniamy w opisach.</p>'),
                                'en'=>array('title'=>'Treatment methods A–Z','lead'=>'Information about procedures, indications and limitations.','body'=>'<p>Not every intervention is an established treatment. Each description explains clinical suitability and the level of supporting evidence.</p>')),
        );
    }

    public static function archives(): array {
        $saved=(array)get_option('nc_archive_pages',array());
        return array_replace_recursive(self::archive_defaults(),$saved);
    }

    public static function archive_editor(): void {
        if(!current_user_can('manage_options'))return;
        $all=self::archives();$focus=isset($_GET['nc_archive'])?sanitize_key(wp_unslash((string)$_GET['nc_archive'])):'';
        echo '<div class="wrap nc-content-map"><h1>Strony katalogów PL/EN</h1><p>Edytuj nagłówek, wprowadzenie i opis nad kartami na stronie działu. Same karty są powiązane z opublikowanymi rekordami medycznymi.</p>';
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
        wp_nonce_field('nc_save_archives');echo '<input type="hidden" name="action" value="nc_save_archives">';
        foreach($all as $key=>$langs){
            echo '<section class="nc-archive-editor" id="nc-archive-'.esc_attr($key).'"><h2>'.esc_html($langs['pl']['title']??$key).'</h2>';
            foreach(array('pl'=>'Język Polski','en'=>'Język Angielski') as $lang=>$label){
                echo '<fieldset class="nc-archive-language"><legend>'.esc_html($label).'</legend>';
                foreach(array('title'=>'Nagłówek strony','lead'=>'Wprowadzenie') as $f=>$name){
                    echo '<p><label for="nc_archive_'.esc_attr($key.'_'.$lang.'_'.$f).'">'.esc_html($name).'</label><br>';
                    echo '<input class="large-text" id="nc_archive_'.esc_attr($key.'_'.$lang.'_'.$f).'" name="archives['.esc_attr($key).']['.esc_attr($lang).']['.esc_attr($f).']" value="'.esc_attr((string)($langs[$lang][$f]??'')).'"></p>';
                }
                echo '<label for="nc_archive_'.esc_attr($key.'_'.$lang.'_body').'">Opis</label>';
                wp_editor((string)($langs[$lang]['body']??''),'nc_archive_'.$key.'_'.$lang.'_body',array(
                    'textarea_name'=>'archives['.$key.']['.$lang.'][body]','textarea_rows'=>5,'media_buttons'=>false));
                echo '</fieldset>';
            }
            echo '</section>';
        }
        echo '<p><button class="button button-primary button-hero" type="submit">Zapisz katalogi PL/EN</button></p></form></div>';
    }

    public static function save_archives(): void {
        if(!current_user_can('manage_options'))wp_die('Brak uprawnień.');
        check_admin_referer('nc_save_archives');
        $sent=isset($_POST['archives']) && is_array($_POST['archives'])?wp_unslash($_POST['archives']):array();
        $existing=self::archives();$output=array();
        foreach(self::archive_defaults() as $key=>$row){
            foreach(array('pl','en') as $lang){
                foreach(array('title','lead','body') as $f){
                    $raw=$sent[$key][$lang][$f] ?? $existing[$key][$lang][$f] ?? '';
                    if(!is_string($raw))$raw='';
                    $output[$key][$lang][$f]='body'===$f?wp_kses_post($raw):sanitize_text_field($raw);
                }
            }
        }
        update_option('nc_archive_pages',$output,false);
        wp_safe_redirect(admin_url('admin.php?page=novacell-archives&updated=1'));exit;
    }
}
