<?php

class ModelModuleNewsletter2Go extends Model
{

    /**
     * Checks API users credentials
     * @param string $apikey
     * @return bool true if user has rights, otherwise false
     */
    public function validateUser($apikey = '')
    {
        $result = $this->db->query('SELECT * FROM `' . DB_PREFIX . "user` WHERE n2go_apikey = '$apikey' AND n2go_api_enabled = 1");

        return $result->num_rows == 1;
    }

    /**
     * Sets newsletter flag on 0 for user(s) with given e-mail
     * @param string $email
     * @param int $status
     */
    public function unsubscribeByEmail($email, $status)
    {
        $this->event->trigger('pre.customer.edit.newsletter');
        $result = $this->db->query("UPDATE `" . DB_PREFIX . "customer` SET newsletter = $status WHERE email = '$email'");
        $this->event->trigger('post.customer.edit.newsletter');

        return $result;
    }

    public function customerGroups()
    {
        $languageId = $this->getDefaultLanguageId();
        $query = 'SELECT g.customer_group_id AS id, name, description
                  FROM `' . DB_PREFIX . 'customer_group` g
                  LEFT JOIN `' . DB_PREFIX . 'customer_group_description` gd
                        ON g.customer_group_id = gd.customer_group_id AND gd.language_id = ' . $languageId;

        $resultGroups = $this->db->query($query);

        return array('groups' => $resultGroups->rows);
    }

    public function customerCount($subscribed, $group)
    {
        $conditions = array();
        $query = 'SELECT COUNT(*) as total FROM ' . DB_PREFIX . 'customer';
        if (!empty($group)) {
            $conditions[] = 'customer_group_id = ' . $group;
        }

        if (!empty($subscribed)) {
            $conditions[] = 'newsletter = 1';
        }

        if (!empty($conditions)) {
            $query .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $resultGroups = $this->db->query($query);

        return array('customers' => $resultGroups->rows[0]['total']);
    }

    public function getCustomers($criteria = array())
    {
        $conditions = array();
        $query = $this->buildCustomersQuery($criteria['fields']);
        $query .= ' FROM ' . DB_PREFIX . 'customer c
                    LEFT JOIN ' . DB_PREFIX . 'address a ON a.address_id = c.address_id 
                    LEFT JOIN ' . DB_PREFIX . 'country co ON co.country_id = a.country_id ';

        if ($criteria['subscribed']) {
            $conditions[] = 'c.newsletter = 1';
        }

        if (!empty($criteria['group'])) {
            $conditions[] = 'c.customer_group_id = ' . $criteria['group'];
        }

        if (!empty($criteria['emails'])) {
            $conditions[] = 'c.email IN (\'' . implode("', '", $criteria['emails']) . '\')';
        }

        if (!empty($conditions)) {
            $query .= ' WHERE ' . implode(' AND ', $conditions);
        }

        if ($criteria['limit']) {
            $criteria['offset'] = ($criteria['offset'] ?: 0);
            $query .= ' LIMIT ' . $criteria['offset'] . ', ' . $criteria['limit'];
        }

        $resultCustomers = $this->db->query($query);

        return array('customers' => $resultCustomers->rows);
    }

    public function getLanguages()
    {
        $result = array();
        $query = $this->db->query('SELECT name, code FROM ' . DB_PREFIX . 'language');

        foreach ($query->rows as $lang) {
            $result[$lang['code']] = $lang['name'];
        }

        return $result;
    }

    public function getProduct($id, $lang = '', $fields = array())
    {
        if (empty($lang)) {
            $lang = $this->getDefaultLanguageId();
        }

        $query = $this->buildItemQuery($fields);
        $query .= ' FROM ' . DB_PREFIX . 'product p
                    LEFT JOIN ' . DB_PREFIX . 'manufacturer mf ON mf.manufacturer_id = p.manufacturer_id
                    LEFT JOIN ' . DB_PREFIX . 'product_description pd ON pd.product_id = p.product_id
                    LEFT JOIN ' . DB_PREFIX . 'stock_status s ON s.stock_status_id = p.stock_status_id
                    LEFT JOIN ' . DB_PREFIX . 'language lg ON lg.language_id = pd.language_id AND lg.language_id = s.language_id
                    LEFT JOIN (SELECT tax_class_id, tax_rate_id, MIN(priority) FROM ' . DB_PREFIX . 'tax_rule GROUP BY tax_class_id) tr ON tr.tax_class_id = p.tax_class_id
                    LEFT JOIN ' . DB_PREFIX . 'tax_rate ta ON ta.tax_rate_id = tr.tax_rate_id '
            . "WHERE p.product_id = $id AND (lg.code = '$lang' OR  lg.language_id = '$lang')";

        $result = $this->db->query($query);
        $product = $result->row;
        if (array_key_exists('link', $product)) {
            $product['link'] = substr($this->url->link('product/product', 'product_id=' . $id), strlen(HTTP_SERVER));
        }

        if (array_key_exists('url', $product)) {
            $product['url'] = HTTP_SERVER;
        }

        if (array_key_exists('ta.rate', $product) && $product['ta.rate']) {
            if (array_key_exists('oldPrice', $product)) {
                $product['oldPrice'] = round($product['oldPrice'] * (1 + $product['ta.rate'] * 0.01), 2);
            }

            if (array_key_exists('newPrice', $product)) {
                $product['newPrice'] = round($product['newPrice'] * (1 + $product['ta.rate'] * 0.01), 2);
            }

            if (in_array('ta.rate', $fields) || empty($fields)) {
                $product['ta.rate'] = round($product['ta.rate'] * 0.01, 2);
            } else {
                unset($product['ta.rate']);
            }
        }

        if (array_key_exists('oldPriceNet', $product)) {
            $product['oldPriceNet'] = round($product['oldPriceNet'], 2);
        }

        if (array_key_exists('newPriceNet', $product)) {
            $product['newPriceNet'] = round($product['newPriceNet'], 2);
        }

        if (array_key_exists('images', $product)) {
            $images = array(HTTP_SERVER . 'image/' . $product['images']);
            $query = 'SELECT image FROM ' . DB_PREFIX . 'product_image WHERE product_id = ' . $id;
            $result = $this->db->query($query);
            foreach ($result->rows as $image) {
                $images[] = HTTP_SERVER . 'image/' . $image['image'];
            }

            $product['images'] = $images;
        }

        return $product;
    }

    /**
     * Returns customer fields array
     * @return array
     */
    public function customerFields()
    {
        $fields = array();
        $fields['c.customer_id'] = $this->createField('c.customer_id', 'Customer Id.', 'Integer');
        $fields['c.customer_group_id'] = $this->createField('c.customer_group_id', 'Customer Id.', 'Integer');
        $fields['c.firstname'] = $this->createField('c.firstname', 'First name');
        $fields['c.lastname'] = $this->createField('c.lastname', 'Last name');
        $fields['c.status'] = $this->createField('c.status', 'Customer status', 'Boolean');
        $fields['c.ip'] = $this->createField('c.ip', 'Customer IP address');
        $fields['c.approved'] = $this->createField('c.approved', 'Customer approved', 'Boolean');
        $fields['c.safe'] = $this->createField('c.safe', 'Customer safe', 'Boolean');
        $fields['c.date_added'] = $this->createField('c.date_added', 'Date added');
        $fields['c.email'] = $this->createField('c.email', 'E-mail address');
        $fields['c.telephone'] = $this->createField('c.telephone', 'Phone number');
        $fields['c.fax'] = $this->createField('c.fax', 'Fax number');
        $fields['c.newsletter'] = $this->createField('c.newsletter', 'Subscribed', 'Boolean');
        $fields['a.company'] = $this->createField('a.company', 'Company');
        $fields['a.address_1'] = $this->createField('a.address_1', 'Street');
        $fields['a.city'] = $this->createField('a.city', 'City');
        $fields['co.name'] = $this->createField('co.name', 'Country');
        $fields['c.newsletter'] = $this->createField('c.newsletter', 'Subscribed');

        return $fields;
    }

    /**
     * Returns customer fields array
     * @return array
     */
    public function itemFields()
    {
        $fields = array();
        $fields['p.product_id'] = $this->createField('p.product_id', 'Product Id.', 'Integer');
        $fields['pd.name'] = $this->createField('pd.name', 'Product name', 'String');
        $fields['pd.description'] = $this->createField('pd.description', 'Product description', 'String');
        $fields['pd.tag'] = $this->createField('pd.tag', 'Product tag', 'String');
        $fields['pd.meta_title'] = $this->createField('pd.meta_title', 'Product meta title', 'String');
        $fields['pd.meta_description'] = $this->createField('pd.meta_description', 'Product meta description', 'String');
        $fields['pd.meta_keyword'] = $this->createField('pd.meta_keyword', 'Product meta keyword', 'String');
        $fields['p.model'] = $this->createField('p.model', 'Product model.', 'String');
        $fields['p.sku'] = $this->createField('p.sku', 'SKU.', 'String', 'Stock Keeping Unit');
        $fields['p.upc'] = $this->createField('p.upc', 'UPC.', 'String', 'Universal Product Code');
        $fields['p.ean'] = $this->createField('p.ean', 'EAN.', 'String', 'European Article Number');
        $fields['p.jan'] = $this->createField('p.jan', 'JAN.', 'String', 'Japanese Article Number');
        $fields['p.isbn'] = $this->createField('p.isbn', 'ISBN.', 'String', 'International Standard Book Number');
        $fields['p.mpn'] = $this->createField('p.mpn', 'MPN.', 'String', 'Manufacturer Part Number');
        $fields['p.location'] = $this->createField('p.location', 'Location', 'String');
        $fields['p.quantity'] = $this->createField('p.quantity', 'Quantity', 'String');
        $fields['s.name'] = $this->createField('s.name', 'Stock Status', 'String');
        $fields['ta.rate'] = $this->createField('ta.rate', 'VAT', 'String', 'Value Added Tax');
        $fields['mf.name'] = $this->createField('mf.name', 'Manufacturer name', 'String');
        $fields['images'] = $this->createField('images', 'Product images', 'Array');
        $fields['p.shipping'] = $this->createField('p.shipping', 'Shipping', 'Boolean');
        $fields['p.points'] = $this->createField('p.points', 'Points', 'Integer');
        $fields['p.date_available'] = $this->createField('p.date_available', 'Date available', 'Date');
        $fields['p.status'] = $this->createField('p.status', 'Product status', 'Boolean');
        $fields['p.viewed'] = $this->createField('p.viewed', 'Viewed', 'Integer');
        $fields['p.date_added'] = $this->createField('p.date_added', 'Date added', 'Date');
        $fields['p.date_modified'] = $this->createField('p.date_modified', 'Date modified', 'Date');
        $fields['oldPrice'] = $this->createField('oldPrice', 'Old price', 'Float');
        $fields['oldPriceNet'] = $this->createField('oldPriceNet', 'Old net price', 'Float');
        $fields['newPrice'] = $this->createField('newPrice', 'New price', 'Float');
        $fields['newPriceNet'] = $this->createField('newPriceNet', 'New net price', 'Float');
        $fields['url'] = $this->createField('url', 'Shop url', 'String');
        $fields['link'] = $this->createField('link', 'Product relative url', 'String');
        $fields['shortDescription'] = $this->createField('shortDescription', 'Product short description', 'String');

        return $fields;
    }

    /**
     * Helper function to create field array
     * @param $id
     * @param $name
     * @param string $type
     * @param string $description
     * @return array
     */
    private function createField($id, $name, $type = 'String', $description = '')
    {
        return array('id' => $id, 'name' => $name, 'description' => $description, 'type' => $type);
    }

    /**
     * @param array $fields
     * @return string
     */
    private function buildCustomersQuery($fields = array())
    {
        $select = array();
        if (empty($fields)) {
            $fields = array_keys($this->customerFields());
        } else if (!in_array('c.customer_id', $fields)) {
            //customer Id must always be present
            $fields[] = 'c.customer_id';
        }

        foreach ($fields as $field) {
            $select[] = "$field AS '$field'";
        }

        return 'SELECT ' . implode(', ', $select);
    }

    /**
     * @param array $fields
     * @return string
     */
    private function buildItemQuery($fields)
    {
        $select = array();
        if (empty($fields)) {
            $fields = array_keys($this->itemFields());
        } else {
            if (!in_array('p.product_id', $fields)) {
                //item Id must always be present
                $fields[] = 'p.product_id';
            }

            if (!in_array('ta.rate', $fields)) {
                $fields[] = 'ta.rate';
            }
        }

        foreach ($fields as $field) {
            switch ($field) {
                case 'url':
                case 'link':
                case 'shortDescription':
                    $select[] = "NULL AS '$field'";
                    break;
                case 'oldPrice':
                case 'oldPriceNet':
                case 'newPrice':
                case 'newPriceNet':
                    $select[] = "p.price AS '$field'";
                    break;
                case 'images':
                    $select[] = 'p.image AS images';
                    break;
                default:
                    $select[] = "$field AS '$field'";
                    break;
            }

        }

        return 'SELECT ' . implode(', ', $select);
    }

    /**
     *
     * @return mixed
     */
    private function getDefaultLanguageId()
    {
        $query = 'SELECT l.language_id as language_id FROM `' . DB_PREFIX . 'language` l WHERE l.code IN
                (SELECT o.value FROM `' . DB_PREFIX . 'setting` o WHERE o.key = \'config_admin_language\')';
        $result = $this->db->query($query);

        return $result->rows[0]['language_id'];
    }

}
