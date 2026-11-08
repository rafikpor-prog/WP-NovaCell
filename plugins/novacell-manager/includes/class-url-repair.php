<?php
/** Safe permalink repair via WordPress core; no raw .htaccess overwrite. */
namespace NovaCell\Manager;
defined('ABSPATH') || exit;
final class URL_Repair {
    private const KEY='nc_url_repair_070';
    public static function boot(): void {
        add_action('admin_menu',array(self::class,'menu'));
        add_action('admin_post_nc_repair_permalinks',array(self::class,'action'));
        add_action('admin_init',array(self::class,'upgrade'),60);
    }
    public static function menu(): void {
        add_submenu_page('novacell-manager','Naprawa linków i 404','Naprawa linków 404',
            'manage_options','novacell-repair-urls',array(self::class,'render'));
    }
    public static function upgrade(): void {
        if(!current_user_can('manage_options') || get_option(self::KEY,false))return;
        self::repair();
    }
    public static function repair(): void {
        global $wp_rewrite;
        if(function_exists('nc_theme_register_rewrites')) { \nc_theme_register_rewrites(); }
        // A hard rewrite flush asks WordPress itself to write its .htaccess markers
        // where supported and writable; it does not clobber other custom rules.
        flush_rewrite_rules(true);
        $probe = wp_safe_remote_get( home_url('/chce-zadbac-o-zdrowie/'), array(
            'timeout'=>6, 'redirection'=>0, 'sslverify'=>true,
            'headers'=>array('Cache-Control'=>'no-cache'),
        ) );
        $http = is_wp_error($probe) ? 0 : (int)wp_remote_retrieve_response_code($probe);
        $body = is_wp_error($probe) ? '' : (string)wp_remote_retrieve_body($probe);
        $ok = (200 === $http && (str_contains($body,'Chcę zadbać o zdrowie') || str_contains($body,'Preventive health')));
        // Activate canonical paths only when the hosting confirmed a real HTML response.
        if($ok)update_option('nc_route_mode','pretty',false);
        update_option('nc_rewrite_probe',array('http'=>$http,'ok'=>$ok,'at'=>gmdate('c')) ,false);
        update_option(self::KEY,gmdate('c'),false);
    }
    public static function action(): void {
        if(!current_user_can('manage_options'))wp_die('Brak uprawnień.');
        check_admin_referer('nc_repair_permalinks');
        self::repair();
        wp_safe_redirect(admin_url('admin.php?page=novacell-repair-urls&done=1'));exit;
    }
    public static function render(): void {
        if(!current_user_can('manage_options'))return;
        global $wp_rewrite;
        $home=home_url('/');
        $pretty=(bool)get_option('permalink_structure','');
        $test_routes=array(
            '/chce-zadbac-o-zdrowie/', '/z-czym-pomagamy/',
            '/metody/', '/wyglad-i-estetyka/', '/chce-zadbac-o-zdrowie/diagnostyka-i-plan/',
        );
        echo '<div class="wrap"><h1>Naprawa przyjaznych adresów NOVACELL</h1>';
        echo '<p>Wersja 0.7.0 naprawia reguły WordPress, które wcześniej były odświeżane tylko w bazie danych. Ta funkcja korzysta z oficjalnego mechanizmu WordPress i nie zastępuje ręcznie całego pliku .htaccess.</p>';
        echo '<p><strong>WordPress permalink structure:</strong> '.esc_html($pretty?'Włączona':'Wyłączona (należy wybrać „Nazwa wpisu”)').'</p>';
        echo '<p><strong>Ostatnia automatyczna naprawa:</strong> '.esc_html((string)get_option(self::KEY,'Nie wykonano')).'</p>';
        $probe=(array)get_option('nc_rewrite_probe',array());
        echo '<p role="status"><strong>Test przyjaznej trasy:</strong> '.( !empty($probe['ok']) ? 'HTTP 200, treść potwierdzona. Włączono przyjazne URL.' : 'Nie potwierdzono poprawnego routingu (HTTP '.esc_html((string)($probe['http']??'brak')).'). Zostawiono bezpieczne linki parametrowe.' ).'</p>';
        echo '<form action="'.esc_url(admin_url('admin-post.php')).'" method="post">';wp_nonce_field('nc_repair_permalinks');
        echo '<input type="hidden" name="action" value="nc_repair_permalinks"><button type="submit" class="button button-primary">Zapisz ponownie reguły WordPress</button></form>';
        echo '<h2>Sprawdź dokładne adresy publiczne</h2><ul>';
        foreach($test_routes as $path){echo '<li><a target="_blank" rel="noopener" href="'.esc_url(home_url($path)).'">'.esc_html($path).'</a></li>';}
        echo '</ul><p><a href="'.esc_url(admin_url('options-permalink.php')).'">Otwórz Ustawienia → Bezpośrednie odnośniki</a></p>';
        echo '<h2>Jeżeli serwer nadal wyświetla Not Found</h2>';
        echo '<p>Sprawdź adres ratunkowy <a href="'.esc_url(add_query_arg(array('nc_view'=>'prevention-index','nc_lang'=>'pl'),$home)).'" target="_blank" rel="noopener">?nc_view=prevention-index</a>. Jeżeli adres parametrowy działa, a przyjazny nie, serwer nie przekazuje żądań do index.php. Na hostingu należy sprawdzić konfigurację Apache lub Nginx, katalog strony i reguły rewrite. Kod PHP nie może wykonać przekierowania zanim serwer go uruchomi.</p></div>';
    }
}
