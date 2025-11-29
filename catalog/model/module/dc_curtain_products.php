<?php
/**
 * Model Module DC Curtain Products (DB Access Only)
 *
 * Pobiera wszystkie dane produktu, włączając opisy i ceny, bezpośrednio z bazy danych,
 * aby całkowicie ominąć problemy z Proxy i ładowaniem modeli.
 */

namespace Opencart\Catalog\Model\Extension\DcCurtainProducts\Module;

class DcCurtainProducts extends \Opencart\System\Engine\Model {

    /**
     * Główna funkcja pobierająca produkty w oparciu o typ i filtr kategorii.
     * Używa getProductsFromDB() jako rdzenia.
     * * @param string $type ('latest', 'sales')
     * @param array $filter_data
     * @return array
     */
    public function getProductsByFilter(string $type, array $filter_data = []): array {
        
        // Specyficzne sortowanie
        if ($type == 'latest') {
            $filter_data['sort'] = 'p.date_added';
            $filter_data['order'] = 'DESC';
        } elseif ($type == 'sales') {
            $filter_data['filter_special'] = true;
            $filter_data['sort'] = 'ps.priority';
            $filter_data['order'] = 'ASC';
        } else {
             $filter_data['sort'] = 'p.sort_order';
             $filter_data['order'] = 'ASC';
        }

        return $this->getProductsFromDB($filter_data);
    }

    /**
     * Zwraca listę bestsellerów z filtrem kategorii.
     * * @param array $filter_data
     * @return array
     */
    public function getBestsellersByFilter(array $filter_data = []): array {
        
        $customer_group_id = (int)$this->config->get('config_customer_group_id');
        $language_id = (int)$this->config->get('config_language_id');

        $sql = "SELECT tp.product_id, SUM(tp.quantity) AS total FROM `" . DB_PREFIX . "order_product` tp";
        $sql .= " LEFT JOIN `" . DB_PREFIX . "product` p ON (tp.product_id = p.product_id)";
        $sql .= " LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (p.product_id = pd.product_id)";
        
        // Łączenie dla cen specjalnych (potrzebne do pełnej informacji o produkcie)
        $sql .= " LEFT JOIN `" . DB_PREFIX . "product_discount` ps ON (p.product_id = ps.product_id AND ps.customer_group_id = '" . $customer_group_id . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())))";

        // Filtr kategorii
        if (!empty($filter_data['filter_category_id'])) {
            $sql .= " LEFT JOIN `" . DB_PREFIX . "product_to_category` p2c ON (tp.product_id = p2c.product_id)";
        }
        
        $sql .= " WHERE p.status = '1' AND p.date_available <= NOW() AND pd.language_id = '" . $language_id . "'";

        // Filtr kategorii
        if (!empty($filter_data['filter_category_id'])) {
            $sql .= " AND p2c.category_id = '" . (int)$filter_data['filter_category_id'] . "'";
        }
        
        $sql .= " GROUP BY tp.product_id ORDER BY total DESC";
        
        // Limit
        if (isset($filter_data['limit'])) {
            $sql .= " LIMIT " . (int)$filter_data['limit'];
        }

        return $this->processQueryAndGetData($sql, $language_id, $customer_group_id);
    }
    
    /**
     * Metoda pośrednicząca do pobierania wszystkich wartości opcji dla danego produktu.
     * * @param int $product_id
     * @param int $option_id_to_show
     * @return array
     */
    
    public function getProductOptions(int $product_id, int $option_id_to_show): array {
        if ($option_id_to_show == 0) {
            return [];
        }

        $language_id = (int)$this->config->get('config_language_id');
        
        $sql = "SELECT ovd.name FROM `" . DB_PREFIX . "product_option_value` pov";
        $sql .= " LEFT JOIN `" . DB_PREFIX . "option_value_description` ovd ON (pov.option_value_id = ovd.option_value_id)";
        $sql .= " LEFT JOIN `" . DB_PREFIX . "product_option` po ON (pov.product_option_id = po.product_option_id)";
        $sql .= " WHERE pov.product_id = '" . (int)$product_id . "' AND po.option_id = '" . (int)$option_id_to_show . "' AND ovd.language_id = '" . $language_id . "'";
        
        $query = $this->db->query($sql);

        $options = [];
        foreach ($query->rows as $row) {
            $options[] = [
                'name' => $row['name']
            ];
        }

        return $options;
    }

    /**
     * Rdzeń zapytania SQL dla trybów Latest i Sales.
     * * @param array $filter_data
     * @return array
     */
    protected function getProductsFromDB(array $filter_data = []): array {
        
        $customer_group_id = (int)$this->config->get('config_customer_group_id');
        $language_id = (int)$this->config->get('config_language_id');
        
        $sql = "SELECT p.product_id FROM `" . DB_PREFIX . "product` p";
        
        // Łączenie z opisem
        $sql .= " LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (p.product_id = pd.product_id)";
        
        // Łączenie z cenami specjalnymi (jeśli potrzebne)
        $sql .= " LEFT JOIN `" . DB_PREFIX . "product_discount` ps ON (p.product_id = ps.product_id AND ps.customer_group_id = '" . $customer_group_id . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())))";

        // Łączenie z kategoriami
        if (!empty($filter_data['filter_category_id'])) {
            $sql .= " LEFT JOIN `" . DB_PREFIX . "product_to_category` p2c ON (p.product_id = p2c.product_id)";
        }

        $sql .= " WHERE p.status = '1' AND p.date_available <= NOW() AND pd.language_id = '" . $language_id . "'";

        // Filtr kategorii
        if (!empty($filter_data['filter_category_id'])) {
            $sql .= " AND p2c.category_id = '" . (int)$filter_data['filter_category_id'] . "'";
        }
        
        // Filtr promocji (sales)
        if (!empty($filter_data['filter_special'])) {
             $sql .= " AND ps.product_id IS NOT NULL GROUP BY p.product_id"; // Upewniamy się, że to jest promocja i grupujemy
        }

        // Sortowanie i Limit
        $sql .= " ORDER BY " . $filter_data['sort'] . " " . $filter_data['order'];
        
        if (isset($filter_data['limit'])) {
            $sql .= " LIMIT " . (int)$filter_data['limit'];
        }

        $query = $this->db->query($sql);

        $product_ids = [];
        foreach($query->rows as $row) {
            $product_ids[] = $row['product_id'];
        }

        // Pobieramy pełne dane produktów, aby uniknąć problemów z JOIN-ami i zduplikowanymi danymi w tablicy products[]
        return $this->getProductsFullData($product_ids, $language_id, $customer_group_id);
    }
    
    /**
     * Właściwa funkcja pobierająca wszystkie detale produktów po ID.
     * * @param array $product_ids
     * * @param int $language_id
     * * @param int $customer_group_id
     * @return array
     */
    protected function getProductsFullData(array $product_ids, int $language_id, int $customer_group_id): array {
        if (!$product_ids) {
            return [];
        }

        $sql = "SELECT p.*, pd.name, pd.description, pd.tag, ps.price AS special FROM `" . DB_PREFIX . "product` p";
        $sql .= " LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (p.product_id = pd.product_id)";
        
        // Ponowne łączenie dla aktualnej ceny specjalnej
        $sql .= " LEFT JOIN `" . DB_PREFIX . "product_discount` ps ON (p.product_id = ps.product_id AND ps.customer_group_id = '" . $customer_group_id . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())))";

        $sql .= " WHERE p.product_id IN (" . implode(',', array_map('intval', $product_ids)) . ") AND pd.language_id = '" . $language_id . "'";
        $sql .= " GROUP BY p.product_id"; // Upewniamy się, że każdy produkt jest tylko raz

        $query = $this->db->query($sql);
        
        return $query->rows;
    }
}