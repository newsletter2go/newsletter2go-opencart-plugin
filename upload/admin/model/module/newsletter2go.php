<?php

class ModelModuleNewsletter2Go extends Model
{

    /**
     * Deletes columns enabled and apikey from table users
     */
    public function uninstall()
    {
        $this->db->query('ALTER TABLE `' . DB_PREFIX . 'user` '
                . 'DROP COLUMN `n2go_apikey`, '
                . 'DROP COLUMN `n2go_api_enabled`;');
    }

    /**
     * Adds columns enabled and apikey in table users
     */
    public function install()
    {
        $this->db->query('ALTER TABLE `' . DB_PREFIX . 'user` '
                . 'ADD COLUMN `n2go_apikey` VARCHAR(100) NULL AFTER `date_added`, '
                . 'ADD COLUMN `n2go_api_enabled` TINYINT(1) DEFAULT 0  NOT NULL AFTER `n2go_apikey`;');
    }

    /**
     * Disables API flag for user with given ID
     * @param int $id
     */
    public function disable($id)
    {
        $this->db->query('UPDATE `' . DB_PREFIX . 'user` SET `n2go_api_enabled` = 0 WHERE `user_id` = ' . $id . ';');
    }

    /**
     * 
     * @param int $id
     * @return mixed
     */
    public function enable($id)
    {
        $query = $this->db->query('SELECT * FROM `' . DB_PREFIX . 'user` WHERE `user_id` = ' . $id . ';');
        if ($query->num_rows == 1) {
            $result = $query->row;
            if (!$result['n2go_apikey']) {
                $apikey = $this->makeApiKey($id);
                $this->db->query('UPDATE `' . DB_PREFIX . 'user` SET `n2go_api_enabled` = 1, `n2go_apikey` = "' . $apikey . '" WHERE `user_id` = ' . $id . ';');
            } else {
                $this->db->query('UPDATE `' . DB_PREFIX . 'user` SET `n2go_api_enabled` = 1 WHERE `user_id` = ' . $id . ';');
            }
        }
    }

    /**
     * 
     * @param int $id
     */
    public function generateApiKey($id)
    {
        $apikey = $this->makeApiKey($id);
        $this->db->query('UPDATE `' . DB_PREFIX . 'user` SET `n2go_apikey` = "' . $apikey . '" WHERE `user_id` = ' . $id . ';');
    }

    /**
     * 
     * @param int $id
     * @return string
     */
    protected function makeApiKey($id)
    {
        return md5(time() . $id . rand());
    }
}
