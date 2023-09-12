<?php
namespace Opencart\Admin\Controller\Extension\Ebod\Module;

class ebod extends \Opencart\System\Engine\Controller
{
    private $error = [];

    public function index(): void
    {
        $this->load->model('setting/module');
        $this->load->language('extension/ebod/module/ebod');

        $this->document->setTitle($this->language->get('heading_title'));

        $data = [];

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/ebod', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['code'] = $this->config->get('module_ebod_code');
        $data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');
        $data['save'] = $this->url->link('extension/ebod/module/ebod|save', 'user_token=' . $this->session->data['user_token']);

        $data['error'] = $this->error;
        $data['module_ebod_status'] = $this->config->get('module_ebod_status');
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/ebod/module/ebod', $data));
    }

    public function save(): void
    {
        $this->load->language('extension/ebod/module/ebod');

        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/ebod/module/ebod')) {
            $json['error'] = $this->language->get('error_permission');
        }

        if (!$json) {
            $this->load->model('setting/setting');

            $this->model_setting_setting->editSetting('module_ebod', $this->request->post);

            $json['success'] = $this->language->get('text_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/module/ebod')) {
            $this->error['permission'] = true;
            return false;
        }

        if (!utf8_strlen($this->request->post['code'])) {
            $this->error['code'] = true;
        }

        return empty($this->error);
    }

    public function install()
    {
        $this->load->model('setting/setting');
        $this->load->model('setting/module');
        $this->model_setting_module->addModule('ebod', ['name' => 'ebod', 'code' => '12345678-ab12-34ce-f456-46655440000']);
        
        //install event
        $this->load->model('setting/event');
        $event = [
            'code' => 'ebod',
            'trigger' => 'catalog/view/common/header/after',
            'action' => 'extension/ebod/module/ebod|ebodEventHandler',
            'description' => 'EBOD connector event',
            'sort_order' => 1,
            'status' => true
        ];
        $this->model_setting_event->addEvent($event);
        $orderEvent = [
            'code' => 'ebod_order',
            'trigger' => "catalog/view/common/success/after",
            'action' => 'extension/ebod/module/ebod|ebodOrderHandler',
            'description' => 'EBOD connector order event',
            'sort_order' => 1,
            'status' => true
        ];
        $this->model_setting_event->addEvent($orderEvent);

		$orderPreEvent = [
            'code' => 'ebod_order_pre',
            'trigger' => "catalog/controller/checkout/success/before",
            'action' => 'extension/ebod/module/ebod|ebodPreOrderHandler',
            'description' => 'EBOD connector order event',
            'sort_order' => 1,
            'status' => true
        ];
        $this->model_setting_event->addEvent($orderPreEvent);
    }

    public function uninstall()
    {
        $this->load->model('setting/setting');
        $this->load->model('setting/event');
        $this->model_setting_setting->deleteSetting('module_ebod');
        $this->model_setting_event->deleteEventByCode('ebod');
        $this->model_setting_event->deleteEventByCode('ebod_order');
		$this->model_setting_event->deleteEventByCode('ebod_order_pre');
    }
}
