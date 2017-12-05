<?php

class ControllerModuleNewsletter2Go extends Controller
{
    const N2GO_INTEGRATION_URL = 'https://ui.newsletter2go.com/integrations/connect/OPC/';
    private $version = '4.0.01';

    public function index()
    {
        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $this->load->language('module/newsletter2go');


        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_edit'] = $this->language->get('text_edit');
        $data['column_enabled'] = $this->language->get('column_enabled');
        $data['column_username'] = $this->language->get('column_username');
        $data['column_apikey'] = $this->language->get('column_apikey');
        $data['column_actions'] = $this->language->get('column_actions');
        $data['column_no'] = $this->language->get('column_no');
        $data['column_yes'] = $this->language->get('column_yes');
        $data['action_enable'] = $this->language->get('action_enable');
        $data['action_disable'] = $this->language->get('action_disable');
        $data['action_generate'] = $this->language->get('action_generate');
        $data['action_connect'] = $this->language->get('action_connect');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL');
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_module'),
            'href' => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('module/newsletter2go', 'token=' . $this->session->data['token'], 'SSL')
        );

        $filter_data = array(
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $this->load->model('user/user');
        $user_total = $this->model_user_user->getTotalUsers();
        $users = $this->model_user_user->getUsers($filter_data);
        $data['users'] = array();
        $url = '&page=' . $page;
        $queryParams['version'] = $this->version;
        $queryParams['language'] = $this->config->get('config_admin_language');
        $queryParams['url'] = $this->config->get('config_url');



        foreach ($users as $user) {
            $queryParams['apiKey'] = $user['n2go_apikey'];
            $data['users'][] = array(
                'username' => $user['username'],
                'apikey' => $user['n2go_apikey'],
                'enabled' => $user['n2go_api_enabled'],
                'enable' => $this->url->link('module/newsletter2go/enable', 'token=' . $this->session->data['token'] . '&user_id=' . $user['user_id'] . $url, 'SSL'),
                'disable' => $this->url->link('module/newsletter2go/disable', 'token=' . $this->session->data['token'] . '&user_id=' . $user['user_id'] . $url, 'SSL'),
                'generate' => $this->url->link('module/newsletter2go/generate', 'token=' . $this->session->data['token'] . '&user_id=' . $user['user_id'] . $url, 'SSL'),
                'connect' => self::N2GO_INTEGRATION_URL . '?' . http_build_query($queryParams),
            );
        }

        $pagination = new Pagination();
        $pagination->total = $user_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('module/newsletter2go', 'token=' . $this->session->data['token'] . '&page={page}', 'SSL');
        $data['pagination'] = $pagination->render();

        $this->response->setOutput($this->load->view('module/newsletter2go.tpl', $data));
    }

    public function enable()
    {
        if (isset($this->request->get['user_id']) && $this->validate()) {
            $id = intval($this->request->get['user_id']);
            $this->load->model('module/newsletter2go');
            $this->model_module_newsletter2go->enable($id);
        }

        $this->index();
    }

    public function disable()
    {
        if (isset($this->request->get['user_id']) && $this->validate()) {
            $id = intval($this->request->get['user_id']);
            $this->load->model('module/newsletter2go');
            $this->model_module_newsletter2go->disable($id);
        }

        $this->index();
    }

    public function generate()
    {
        if (isset($this->request->get['user_id']) && $this->validate()) {
            $id = intval($this->request->get['user_id']);
            $this->load->model('module/newsletter2go');
            $this->model_module_newsletter2go->generateApiKey($id);
        }

        $this->index();
    }

    public function install()
    {
        if ($this->validate()) {
            $this->load->model('module/newsletter2go');
            $this->model_module_newsletter2go->install();

            $this->load->model('setting/setting');
            $this->model_setting_setting->editSetting('Newsletter2Go', array('Newsletter2Go_status' => 1, 'Newsletter2Go_version' => $this->version));
        }
    }

    public function uninstall()
    {
        if ($this->validate()) {
            $this->load->model('module/newsletter2go');
            $this->model_module_newsletter2go->uninstall();

            $this->load->model('setting/setting');
            $this->model_setting_setting->editSetting('Newsletter2Go', array('Newsletter2Go_status' => 0));
        }
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'module/newsletter2go')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
}