<?php
/**
 * Controller Module DC Curtain Products Class
 *
 * @version 1.1
 * * @author Design Cart <info@designcart.pl>
 */

namespace Opencart\Catalog\Controller\Extension\DcCurtainProducts\Module;

class DcCurtainProducts extends \Opencart\System\Engine\Controller {

    public $cache_directory   = 'extension/dc_curtain_products/cache/';
    public $cache_uri_path    = 'extension/dc_curtain_products/cache/';
    public $minified_css      = 'dc-curtain-products.min.css';
    public $minified_js       = 'dc-curtain-products.min.js';
    public $minified_css_path = '';
    public $minified_js_path  = '';
    
    public function index(array $setting): string {

        $this->minified_css_path = $this->cache_uri_path.$this->minified_css;
        $this->minified_js_path  = $this->cache_uri_path.$this->minified_js;

        $view = '';
        $language_id = (int)$this->config->get('config_language_id');

        // Sprawdzamy, czy ustawienia modułu istnieją dla bieżącego języka
        if (!empty($setting)) {
            
            $this->load->language('extension/dc_curtain_products/module/dc_curtain_products');
            
            // Ładujemy niezbędne modele
            $this->load->model('catalog/product');
            $this->load->model('tool/image');
            
            // NOWE: Ładujemy nasz własny model do obsługi filtrowania:
            $this->load->model('extension/dc_curtain_products/module/dc_curtain_products');
            $this->load->model('extension/dc_curtain_products/module/dc_css_minify'); 
            $this->load->model('extension/dc_curtain_products/module/dc_js_minify');   

            $data['products'] = [];
            
            // 1. Definicja danych filtrujących (używamy limitu z ustawień)
            $filter_data = [
                'start' => 0,
                'limit' => !empty($setting['limit']) ? (int)$setting['limit'] : 5
            ];

            // Dodanie filtra kategorii, jeśli wybrano inną niż "Wszystkie" (0)
            if (!empty($setting['category_id'])) {
                $filter_data['filter_category_id'] = (int)$setting['category_id'];
            }

            $language_code = $this->config->get('config_language');

            $data['cart']         = 'index.php?route=common/cart.info&language='.$language_code;
            $data['cart_add']     = 'index.php?route=checkout/cart.add&language='.$language_code;
            $data['wishlist_add'] = 'index.php?route=account/wishlist.add&language='.$language_code;
            $data['compare_add']  = 'index.php?route=product/compare.add&language='.$language_code;

            $language_id = (int)$this->config->get('config_language_id');
            $data['mod_title']       = $setting['module_description'][$language_id]['title'] ?? '';
            $data['mod_description'] = isset($setting['module_description'][$language_id]['description']) ? html_entity_decode($setting['module_description'][$language_id]['description'], ENT_QUOTES, 'UTF-8') : '';

            

            if(!empty($setting['image_width'])){
                $image_width = (int)$setting['image_width'];
            }else{
                $image_width = 500;
            }

            if(!empty($setting['image_height'])){
                $image_height = (int)$setting['image_height'];
            }else{
                $image_height = 720;
            }

            $data['attr_ID'] = $setting['attr_ID'];
            $data['module_type'] = $setting['module_type'];
            
            $product_results = [];
            
            // 2. Pobieranie listy produktów za pomocą naszego modelu
            switch ($setting['module_type']) {
                case 'latest':
                    // ZMIANA: Używamy naszego modelu do obsługi filtrowania po kategorii
                    $product_results = $this->model_extension_dc_curtain_products_module_dc_curtain_products->getProductsByFilter('latest', $filter_data);
                    break;
                    
                case 'bestsellers':
                    // ZMIANA: Używamy naszego modelu do obsługi bestsellerów z kategorią
                    $product_results = $this->model_extension_dc_curtain_products_module_dc_curtain_products->getBestsellersByFilter($filter_data);
                    break;
                    
                case 'sales':
                    // ZMIANA: Używamy naszego modelu do obsługi promocji z kategorią
                    $product_results = $this->model_extension_dc_curtain_products_module_dc_curtain_products->getProductsByFilter('sales', $filter_data);
                    break;

                default:
                    $product_results = [];
                    break;
            }

            // 3. Przetwarzanie i formatowanie danych produktów
            if ($product_results) {
                
                $option_id_to_show = !empty($setting['option_group_id']) ? (int)$setting['option_group_id'] : 0;

                foreach ($product_results as $result) {
                    
                    // a) Obrazek
                    if ($result['image']) {
                        $image = $this->model_tool_image->resize(html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8'), $image_width, $image_height);
                    } else {
                        $image = $this->model_tool_image->resize('placeholder.png', $width, $height);
                    }

                    // b) Ceny
                    if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                        $price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    } else {
                        $price = false;
                    }

                    if ((float)$result['special']) {
                        $special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    } else {
                        $special = false;
                    }
                    
                    if ($this->config->get('config_tax')) {
                        $tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
                    } else {
                        $tax = false;
                    }
                    
                    // c) Opcje do wyświetlenia (poprawiono błąd Proxy, używając metody z własnego Modelu)
                    $display_options = [];
                    if ($option_id_to_show) {
                        
                        // Przekazujemy DWA wymagane argumenty. Model zwróci gotową, czystą listę opcji.
                        $display_options = $this->model_extension_dc_curtain_products_module_dc_curtain_products->getProductOptions(
                            $result['product_id'], 
                            $option_id_to_show
                        );
                        
                    }

                    // d) Kompilacja danych miniatury (zgodnie ze wzorcem product/thumb)
                    $product_data = [
                        'product_id'        => $result['product_id'],
                        'thumb'             => $image,
                        'name'              => $result['name'],
                        'description'       => oc_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('config_product_description_length')) . '..',
                        'price'             => $price,
                        'special'           => $special,
                        'tax'               => $tax,
                        'minimum'           => $result['minimum'] > 0 ? $result['minimum'] : 1,
                        'rating'            => $result['rating'],
                        'display_options'   => $display_options, 
                        'href'              => $this->url->link('product/product', 'product_id=' . $result['product_id'])
                    ];

                    $data['products'][] = $product_data;
                }
            }
            
            require DIR_OPENCART.'/extension/dc_curtain_products/includes/minifies.php';
            
            $view = $this->load->view('extension/dc_curtain_products/module/dc_curtain_products', $data);
        }

        return $view;
    }
}