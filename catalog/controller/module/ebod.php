<?php
namespace Opencart\Catalog\Controller\Extension\Ebod\Module;

class ebod extends \Opencart\System\Engine\Controller
{
    private $error = [];

    public function ebodEventHandler(string &$route, array &$data, mixed &$output): void {
        if (isset($this->request->get['product_id'])) {
            $productId = $this->request->get['product_id'];
        }

        $code = $this->config->get('module_ebod_code');
        if (isset($productId)) {
            $this->load->model('catalog/product');
            $products = $this->model_catalog_product->getProduct($productId);
            $html = $this->getEbodTag($code, $products['model']);
        } else {
            $html = $this->getEbodTag($code);
        }

        $module =  html_entity_decode($html, ENT_QUOTES, 'UTF-8');
        $output = str_replace('<body>',  '<body>' . $module,  $output);
    }

    private function getEbodTag($trackCode, $orderEmail = null, $orderId = null, $products = [], $currencyCode = '', $total = 0, $languageCode = 'en'): string {
        $html = '<!-- EBOD code start -->' . "\n";
        if ($orderId === null) {
            $html .= '' . "\n";
        }

        if (!empty($orderId)) {
            $html .= '<script>' . "\n";
            $html .= 'window.dataLayer = window.dataLayer || [];' . "\n";
            $html .= 'window.dataLayer.push({' . "\n";
            $html .= '  event: \'ebod_purchase\',' . "\n";
            $html .= '  email: \'' . $orderEmail . '\',' . "\n";
            $html .= '  externalId: \'' . $orderId . '\',' . "\n";
            $html .= '  currency: \'' . $currencyCode . '\',' . "\n";
            $html .= '  totalPrice: \'' . $total . '\',' . "\n";
            $html .= '  languageCode: \'' . $languageCode . '\'' . "\n";
            $html .= '});' . "\n";
            $html .= '(function(e,b,o,d){a=b.getElementsByTagName("head")[0];r=b.createElement("script");r.async=1;r.src=o+d;r.onload=function(){Ebod.init(\'' . $trackCode . '\');Ebod.purchase()};a.appendChild(r);})(window,document,"https"+":"+"//e.bod.digital/init",".js");' . "\n";
            $html .= '</script>' . "\n";
        }

        $html .= ' <!-- EBOD code end -->' . "\n";

        return $html;
    }

    public function ebodOrderHandler(string &$route, array &$data, mixed &$output) {
        if (isset($this->session->data['order_id_last'])) {
            $this->load->model('checkout/order');
            $code = $this->config->get('module_ebod_code');
            $orderDetails = $this->model_checkout_order->getOrder($this->session->data['order_id_last']);
            unset($this->session->data['order_id_last']);

            $this->load->model('account/order');
            $products = $this->model_account_order->getProducts($orderDetails["order_id"]);
            $html = $this->getEbodTag($code, $orderDetails['email'],  $orderDetails["order_id"], $products, $orderDetails['currency_code'], $orderDetails["total"], $orderDetails['language_code']);
            $output = str_replace('</body>',  $html.'</body>',  $output);
        }
    }

    public function ebodPreOrderHandler(string &$route, array &$data) {
        if (isset($this->session->data['order_id'])) {
            $this->session->data['order_id_last'] = $this->session->data['order_id'];
        }
    }
}
