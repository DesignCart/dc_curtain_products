<?php

// Sprawdzenie, czy klucz sesji dla modułu istnieje
if (!isset($this->session->data['dc_curtain_products'])) {
	$this->session->data['dc_curtain_products'] = array();
}

$module_css_files = array(
	'extension/dc_curtain_products/catalog/view/assets/css/component.css',
	'extension/dc_curtain_products/catalog/view/assets/css/dc_curtain_products.css'
);

$module_js_files = array(
	'extension/dc_curtain_products/catalog/view/assets/js/snap.svg-min.js',
	'extension/dc_curtain_products/catalog/view/assets/js/hovers.js'
);

$reolad             = false; 
$run_css            = false;
$run_js             = false;

if(file_exists($this->minified_css_path)){
    $minified_css_time = filemtime($this->minified_css_path);

    foreach($module_css_files as $file){
        if(filemtime($file) > $minified_css_time){
            $run_css = true;
            break;
        }
    }
}else{
    $run_css = true;
}

if(file_exists($this->minified_js_path)){
    $minified_js_time = filemtime($this->minified_js_path);

    foreach($module_js_files as $file){
        if(filemtime($file) > $minified_js_time){
            $run_js = true;
            break;
        }
    }
}else{
    $run_js = true;
}

$pathes = array(
    'cache_directory' => $this->cache_directory,
    'cache_uri_path'  => $this->cache_uri_path,
    'minified_css'    => $this->minified_css,
    'minified_js'     => $this->minified_js
);

if(!empty($run_css)){
    $this->model_extension_dc_curtain_products_module_dc_css_minify->dc_get_minified_css(
		$module_css_files,
		$pathes
	);

    $reolad = true;
}

if(!empty($run_js)){
    $this->model_extension_dc_curtain_products_module_dc_js_minify->dc_get_minified_js(
		$module_js_files,
		$pathes
	);

    $reolad = true;
}

if (!isset($this->session->data['dc_curtain_products']['loaded_assets']) OR $reolad) {

	// Definicja ścieżek CSS
	$this->session->data['dc_curtain_products']['css'] = array(
		'main' => $this->minified_css_path
	);

	// Definicja ścieżek JS
	$this->session->data['dc_curtain_products']['js'] = array(
		'main' => $this->minified_js_path
	);
	
	// Ustawienie flagi, że zasoby zostały załadowane
	$this->session->data['dc_curtain_products']['loaded_assets'] = true;
}

// Ustawienie flagi 'display' na TRUE jest kluczowe, 
// aby móc później (np. w pliku header.twig) włączyć generowanie linków do plików.
// Sprawdzenie, czy klucz 'display' w ogóle istnieje
if (!isset($this->session->data['dc_curtain_products']['display'])) {
	$this->session->data['dc_curtain_products']['display'] = true;
} else {
	// Jeśli już istnieje, po prostu ustawiamy na true, 
	// aby upewnić się, że nagłówek wygeneruje linki do zasobów.
	$this->session->data['dc_curtain_products']['display'] = true;
}

// Przekazanie danych sesji do widoku modułu (jeśli są potrzebne w module.twig)
$data['session'] = $this->session->data['dc_curtain_products'];