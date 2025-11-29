<?php
/**
 * Controller Module DC Curtain Products
 *
 * @version 1.0
 * 
 * @author Design Cart <info@designcart.pl>
 */

namespace Opencart\Admin\Controller\Extension\DcCurtainProducts\Module;
class DcCurtainProducts extends \Opencart\System\Engine\Controller {
    private $error = array();

    public function index(): void {
        $x = version_compare(VERSION, '4.0.2.0', '>=') ? '.' : '|';

        $this->load->language('extension/dc_curtain_products/module/dc_curtain_products');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->document->addScript(HTTP_SERVER . 'view/javascript/ckeditor/ckeditor.js');
        $this->document->addScript(HTTP_SERVER . 'view/javascript/ckeditor/adapters/jquery.js');

        $this->load->model('setting/module');
        $this->load->model('setting/extension');
        $this->load->model('catalog/category');
        $this->load->model('catalog/option');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            if (!isset($this->request->get['module_id'])) {
                $this->model_setting_module->addModule('dc_curtain_products.dc_curtain_products', $this->request->post);
                $module_id = $this->db->getLastId();

                $module_settings = $this->model_setting_module->getModule($module_id);
                $module_settings['module_id'] = $module_id;

                $this->model_setting_module->editModule($module_id, $module_settings);
            } else {
                $post = $this->request->post;
                $post['module_id'] = $this->request->get['module_id'];
                $this->model_setting_module->editModule($this->request->get['module_id'], $post);
            }

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module'));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['name'])) {
            $data['error_name'] = $this->error['name'];
        } else {
            $data['error_name'] = '';
        }

        if (isset($this->error['module_type'])) {
            $data['error_module_type'] = $this->error['module_type'];
        } else {
            $data['error_module_type'] = '';
        }

        if (isset($this->error['form'])) {
            $data['error_form'] = $this->error['form'];
        } else {
            $data['error_form'] = array();
        }

        $url = '';

        if (isset($this->request->get['module_id'])) {
            $url .= '&module_id=' . $this->request->get['module_id'];
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/dc_curtain_products/module/dc_curtain_products', 'user_token=' . $this->session->data['user_token'] . $url)
        );

        $data['action'] = $this->url->link('extension/dc_curtain_products/module/dc_curtain_products' . $x . 'save', 'user_token=' . $this->session->data['user_token'] . $url);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

        if (isset($this->request->get['module_id'])) {
            $module_info = $this->model_setting_module->getModule($this->request->get['module_id']);
        }

        if (isset($this->request->post['name'])) {
            $data['name'] = $this->request->post['name'];
        } elseif (!empty($module_info)) {
            $data['name'] = $module_info['name'];
        } else {
            $data['name'] = '';
        }

        if (isset($this->request->post['module_type'])) {
            $data['module_type'] = $this->request->post['module_type'];
        } elseif (!empty($module_info)) {
            $data['module_type'] = $module_info['module_type'];
        } else {
            $data['module_type'] = 'latest'; 
        }

        if (isset($this->request->post['attr_ID'])) {
            $data['attr_ID'] = $this->request->post['attr_ID'];
        } elseif (!empty($module_info)) {
            $data['attr_ID'] = $module_info['attr_ID'];
        } else {
            $data['attr_ID'] = '';
        }

        if (isset($this->request->post['category_id'])) {
            $data['category_id'] = $this->request->post['category_id'];
        } elseif (!empty($module_info)) {
            $data['category_id'] = $module_info['category_id'];
        } else {
            $data['category_id'] = 0; 
        }

        if (isset($this->request->post['option_group_id'])) {
            $data['option_group_id'] = $this->request->post['option_group_id'];
        } elseif (!empty($module_info)) {
            $data['option_group_id'] = $module_info['option_group_id'];
        } else {
            $data['option_group_id'] = 0; 
        }

        if (isset($this->request->post['limit'])) {
            $data['limit'] = $this->request->post['limit'];
        } elseif (!empty($module_info['limit'])) {
            $data['limit'] = $module_info['limit'];
        } else {
            $data['limit'] = 5;
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($module_info)) {
            $data['status'] = $module_info['status'];
        } else {
            $data['status'] = '';
        }

        if (isset($this->request->post['module_description'])) {
            $data['module_description'] = $this->request->post['module_description'];
        } elseif (!empty($module_info)) {
            $data['module_description'] = $module_info['module_description'];
        } else {
            $data['module_description'] = array();
        }

        if (isset($this->request->post['module_product'])) {
            $data['module_products'] = $this->request->post['module_product'];
        } elseif ($this->config->get('module_NAZWA_MODULU_product')) {
            $data['module_products'] = $this->config->get('module_NAZWA_MODULU_product');
        } else {
            $data['module_products'] = array();
        }

        $data['categories'] = [];
        $data['categories'][] = [
            'category_id' => 0,
            'name'        => $this->language->get('text_all_categories')
        ];
        
        $filter_data = [
            'sort'  => 'name',
            'order' => 'ASC'
        ];

        $categories = $this->model_catalog_category->getCategories($filter_data);
        
        foreach ($categories as $category) {
            $data['categories'][] = [
                'category_id' => $category['category_id'],
                'name'        => $category['name']
            ];
        }

        $data['option_groups'] = [];
        $data['option_groups'][] = [
            'option_id' => 0,
            'name'      => $this->language->get('text_select_option_group')
        ];
        
        $options = $this->model_catalog_option->getOptions($filter_data);
        
        foreach ($options as $option) {
            $data['option_groups'][] = [
                'option_id' => $option['option_id'],
                'name'      => $option['name']
            ];
        }

        $this->load->model('localisation/language');

        $data['languages']   = $this->model_localisation_language->getLanguages();
        $data['general']     = $this->load->view('extension/dc_curtain_products/module/elements/general', $data);
        $data['settings']    = $this->load->view('extension/dc_curtain_products/module/elements/settings', $data);
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/dc_curtain_products/module/dc_curtain_products', $data));
    }

    /**
     * Save Module Data.
     * 
     * @return void
     */
	public function save(): void {
		$this->load->language('extension/dc_curtain_products/module/dc_curtain_products');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/dc_curtain_products/module/dc_curtain_products')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

        if ((mb_strlen($this->request->post['name']) < 3) || (mb_strlen($this->request->post['name']) > 64)) {
            $json['error']['name'] = $this->language->get('error_name');
        }

        $allowed_types = ['latest', 'bestsellers', 'sales', 'featureds'];
        if (!isset($this->request->post['module_type']) || !in_array($this->request->post['module_type'], $allowed_types)) {
            $json['error']['module_type'] = $this->language->get('error_module_type');
        }

		if (!$json) {
			$this->load->model('setting/module');

            if (!isset($this->request->get['module_id'])) {
                $this->model_setting_module->addModule('dc_curtain_products.dc_curtain_products', $this->request->post);
                $module_id = $this->db->getLastId();

                $module_settings = $this->model_setting_module->getModule($module_id);
                $module_settings['module_id'] = $module_id;

                $this->model_setting_module->editModule($module_id, $module_settings);

                //$json['redirect'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');
            } else {
                $post = $this->request->post;
                $post['module_id'] = $this->request->get['module_id'];
                $this->model_setting_module->editModule($this->request->get['module_id'], $post);

                //$json['redirect'] = $this->url->link('extension/dc_curtain_products/module/dc_curtain_products', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id']);
            }

			$json['success'] = $this->language->get('text_success');
		} else {
            if (!isset($json['error']['warning'])) {
                $json['error']['warning'] = $this->language->get('error_warning');
            }
        }

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

    /**
     * Validate Permission and Form fiels.
     * 
     * @return bool $this->error
     */
    protected function validate(): bool {
        if (!$this->user->hasPermission('modify', 'extension/dc_curtain_products/module/dc_curtain_products')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((mb_strlen($this->request->post['name']) < 3) || (mb_strlen($this->request->post['name']) > 64)) {
            $this->error['name'] = $this->language->get('error_name');
        }

        $allowed_types = ['latest', 'bestsellers', 'sales', 'featureds'];
        if (!isset($this->request->post['module_type']) || !in_array($this->request->post['module_type'], $allowed_types)) {
            $this->error['module_type'] = $this->language->get('error_module_type');
        }

        return !$this->error;
    }

    /**
    * Install method.
    *
    * @return void
    */
    public function install(): void {
        $this->load->model('setting/setting');

        // Add settings.
        $this->model_setting_setting->editSetting('module_dc_curtain_products', array(
            'module_dc_curtain_products_captcha_ed_pc' => $this->generateRandomString(8), 
            'module_dc_curtain_products_captcha_ed_ec' => $this->generateRandomString(8)  // Secure string for encrypt/decrypt module_id settings.
        ));
    }

    /**
    * Uninstall method.
    *
    * @return void
    */
    public function uninstall(): void {
        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting('module_dc_curtain_products');
    }

    /**
    * Generate random string.
    *
    * @param int $length
    *
    * @return string $string
    */
    private function generateRandomString(int $length = 16): string {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $characters_length = strlen($characters);
        $string = '';

        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[random_int(0, $characters_length - 1)];
        }

        return $string;
    }
}