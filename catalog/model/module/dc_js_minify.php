<?php
    // W pliku: /extension/dc_curtain_products/catalog/model/module/dc_js_minify.php

    namespace Opencart\Catalog\Model\Extension\DcCurtainProducts\Module;

    // Użycie klasy Model z globalnej przestrzeni nazw
    class DcJsMinify extends \Opencart\System\Engine\Model {

        // Użyj tej metody, jeśli JShrink nie jest ładowany automatycznie przez Autoloader OC
        // Wymaga, aby plik Minifier.php był dostępny w systemie, np. w system/library/jshrink/Minifier.php
        private function loadJShrink() {
            // Używamy globalnego \DIR_OPENCART, aby znaleźć plik

            $file = \DIR_OPENCART . 'extension/dc_curtain_products/vendor/JShrink/Minifier.php';
            
            if (is_file($file)) {
                // Bezpośrednie włączenie pliku, aby klasa była dostępna
                require_once($file);
                return true;
            }
            return false;
        }

        /**
         * Publiczna metoda do generowania, buforowania i zwracania linku do zminimalizowanego JS.
         * @param array $files_js Lista ścieżek do plików JS do połączenia (względem DIR_OPENCART)
         * @param string $cache_dir Ścieżka do katalogu cache (względem DIR_OPENCART)
         * @param string $cache_uri URI do katalogu cache (względem HTTP_SERVER)
         * @return string Pełny URL do zbuforowanego pliku JS
         */
        public function dc_get_minified_js(array $files_js, array $pathes) {

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

            if(isset($pathes['minified_js'])){
                $file_name = $pathes['minified_js'];
            }else{
                return;
            }

            // Załadowanie JShrink
            if (!class_exists('\JShrink\Minifier')) {
                $this->loadJShrink();
            }
            
            // Sprawdzanie, czy ładowanie się powiodło
            if (!class_exists('\JShrink\Minifier')) {
                // Zwraca błąd, jeśli biblioteka nie jest dostępna
                //return ''; 
            }

            // --- Stałe do konstrukcji ścieżek (Global Scope) ---
            $root_dir = \DIR_OPENCART; // Ścieżka fizyczna do katalogu głównego
            $http_server = \HTTP_SERVER; // URL do katalogu głównego

            // Konfiguracja nazw plików
            $dc_file_js_name = $file_name;
            $cache_file = $root_dir . $cache_dir . $dc_file_js_name;
            
            // 1. Sprawdzenie bufora i konieczności regeneracji (Logika taka sama jak w CSS)
            $regenerate = true;

            if (is_file($cache_file)) {
                $cache_mtime = filemtime($cache_file);
                $regenerate  = false;

                foreach ($files_js as $file) {
                    if (preg_match('#^https?://#', $file)) {
                        continue;
                    }
                    $full_path = $root_dir . $file;
                    
                    if (is_file($full_path) && filemtime($full_path) > $cache_mtime) {
                        $regenerate = true;
                        break;
                    }
                }
            }

            // 2. Generowanie i zapis bufora
            if ($regenerate) {
                $content = '';

                foreach ($files_js as $file) {
                    if (preg_match('#^https?://#', $file)) {
                        $js = @file_get_contents($file); // Pobieranie CDN
                    } else {
                        $full_path = $root_dir . $file;
                        $js = is_file($full_path) ? @file_get_contents($full_path) : false; // Pobieranie lokalne
                    }

                    if ($js !== false) {
                        $content .= "\n/* FILE: " . $file . " */\n" . $js;
                    }
                }

                // Minimalizacja kodu za pomocą JShrink
                // UWAGA: Używamy klasy z globalnej przestrzeni nazw, która została załadowana!
                $content = \JShrink\Minifier::minify($content); 

                // Upewnij się, że katalog cache istnieje
                if (!is_dir($root_dir . $cache_dir)) {
                    mkdir($root_dir . $cache_dir, 0777, true);
                }

                @file_put_contents($cache_file, $content);
            }

            // 3. Zwracanie publicznego URI
            return $http_server . $cache_uri . $dc_file_js_name;
        }
    }