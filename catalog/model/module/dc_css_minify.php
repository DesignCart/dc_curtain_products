<?php

namespace Opencart\Catalog\Model\Extension\DcCurtainProducts\Module;

class DcCssMinify extends \Opencart\System\Engine\Model {

    /**
     * Metoda prywatna do minimalizacji kodu CSS
     * Usuwa komentarze, nowe linie, tabulatory i nadmiarowe spacje.
     * @param string $css
     * @return string
     */
    private function dcSMinifyCss(string $css) {

        // Usuwanie komentarzy /*...*/
        $css = preg_replace('!/\*.*?\*/!s', '', $css);
        
        // Usunięcie pustych linii
        $css = preg_replace('/\n\s*\n/', "\n", $css); 
        
        // Usunięcie powrotu karetki, nowych linii i tabulatorów
        $css = str_replace(["\r", "\n", "\t"], '', $css); 
        
        // Zredukowanie wielu spacji do jednej
        $css = preg_replace('/\s+/', ' ', $css); 
        
        // Usunięcie spacji wokół separatorów CSS ({};:,)
        $css = preg_replace('/\s*([{};:,])\s*/', '$1', $css); 
        
        // Usuwanie zbędnego średnika przed zamykającym nawiasem klamrowym (;}) -> }
        $css = preg_replace('/;}/', '}', $css); 

        return trim($css);
    }

    /**
     * Publiczna metoda do generowania, buforowania i zwracania linku do zminimalizowanego CSS.
     * @param array $files_css Lista ścieżek do plików CSS do połączenia
     * @param string $cache_dir Ścieżka do katalogu cache (np. 'catalog/view/theme/default/cache/')
     * @param string $cache_uri URI do katalogu cache (np. 'catalog/view/theme/default/cache/')
     * @return string Pełny URL do zbuforowanego pliku CSS
     */
    public function dc_get_minified_css(array $files_css, array $pathes) {

        if(isset($pathes['cache_directory'])){
                $cache_dir = $pathes['cache_directory'];
            }else{
                return;
            }

            if(isset($pathes['cache_uri_path'])){
                $cache_uri = $pathes['cache_uri_path'];
            }else{
                return;
            }

            if(isset($pathes['minified_css'])){
                $file_name = $pathes['minified_css'];
            }else{
                return;
            }

        // Konfiguracja nazwy pliku cache
        $dc_file_css_name = $file_name;
        $cache_file =  DIR_OPENCART. $cache_dir . $dc_file_css_name;

        // 1. Sprawdzenie, czy plik bufora istnieje i czy wymaga regeneracji
        $regenerate = true;

        if (file_exists($cache_file)) {
            $cache_mtime = filemtime($cache_file);
            $regenerate  = false;

            foreach ($files_css as $file) {
                // Jeśli plik jest zewnętrzny (CDN), pomijamy sprawdzanie modyfikacji
                if (preg_match('#^https?://#', $file)) {
                    continue;
                }
                
                $full_path = DIR_OPENCART . $file;
                
                // Jeśli któryś z oryginalnych plików jest nowszy niż plik cache, regenerujemy
                if (file_exists($full_path) && filemtime($full_path) > $cache_mtime) {
                    $regenerate = true;
                    break;
                }
            }
        }

        // 2. Generowanie i zapis bufora
        if ($regenerate) {
            $content = '';

            foreach ($files_css as $file) {
                if (preg_match('#^https?://#', $file)) {
                    // Pobieranie CDN
                    $css = @file_get_contents($file);
                } else {
                    // Pobieranie lokalne
                    $full_path = DIR_OPENCART . $file;
                    $css = file_exists($full_path) ? @file_get_contents($full_path) : false;
                }

                if ($css !== false) {
                    $content .= "\n/* FILE: " . $file . " */\n" . $css;
                }
            }

            // Minimalizacja kodu
            $content = $this->dcSMinifyCss($content);

            // Upewnij się, że katalog cache istnieje
            if (!is_dir(DIR_OPENCART . $cache_dir)) {
                mkdir(DIR_OPENCART . $cache_dir, 0777, true);
            }

            @file_put_contents($cache_file, $content);
        }

        // 3. Zwracanie publicznego URI do pliku cache
        return HTTP_SERVER . $cache_uri . $dc_file_css_name;
    }
}