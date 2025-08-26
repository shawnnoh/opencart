<?php
class ControllerAccountSocialLogin extends Controller {
    public function google() {
        $client_id = $this->config->get('social_google_client_id');
        $client_secret = $this->config->get('social_google_client_secret');
        $redirect_uri = $this->url->link('account/social_login/google', '', true);

        if (!isset($this->request->get['code'])) {
            $params = array(
                'client_id' => $client_id,
                'redirect_uri' => $redirect_uri,
                'scope' => 'openid email profile',
                'response_type' => 'code'
            );

            $this->response->redirect('https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
        } else {
            $params = array(
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'code' => $this->request->get['code'],
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirect_uri
            );

            $curl = curl_init('https://oauth2.googleapis.com/token');
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($curl);
            curl_close($curl);

            $token = json_decode($response, true);

            if (isset($token['access_token'])) {
                $curl = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
                curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $token['access_token']));
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($curl);
                curl_close($curl);

                $user = json_decode($response, true);

                if (isset($user['email'])) {
                    $this->load->model('account/customer');
                    $customer_info = $this->model_account_customer->getCustomerByEmail($user['email']);

                    if (!$customer_info) {
                        $customer_id = $this->model_account_customer->addCustomer(array(
                            'customer_group_id' => $this->config->get('config_customer_group_id'),
                            'firstname' => isset($user['given_name']) ? $user['given_name'] : '',
                            'lastname' => isset($user['family_name']) ? $user['family_name'] : '',
                            'email' => $user['email'],
                            'telephone' => '',
                            'fax' => '',
                            'password' => token(10),
                            'company' => '',
                            'address_1' => '',
                            'address_2' => '',
                            'city' => '',
                            'postcode' => '',
                            'country_id' => 0,
                            'zone_id' => 0
                        ));
                    } else {
                        $customer_id = $customer_info['customer_id'];
                    }

                    $this->customer->login($user['email'], '', true);
                    $this->response->redirect($this->url->link('account/account', '', true));
                    return;
                }
            }

            $this->response->redirect($this->url->link('account/login', '', true));
        }
    }

    public function apple() {
        $client_id = $this->config->get('social_apple_client_id');
        $client_secret = $this->config->get('social_apple_client_secret');
        $redirect_uri = $this->url->link('account/social_login/apple', '', true);

        if (!isset($this->request->get['code'])) {
            $params = array(
                'client_id' => $client_id,
                'redirect_uri' => $redirect_uri,
                'scope' => 'name email',
                'response_type' => 'code',
                'response_mode' => 'form_post'
            );

            $this->response->redirect('https://appleid.apple.com/auth/authorize?' . http_build_query($params));
        } else {
            $params = array(
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'code' => $this->request->get['code'],
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirect_uri
            );

            $curl = curl_init('https://appleid.apple.com/auth/token');
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($curl);
            curl_close($curl);

            $token = json_decode($response, true);

            if (isset($token['id_token'])) {
                $payload = explode('.', $token['id_token']);
                $user = json_decode(base64_decode($payload[1]), true);

                if (isset($user['email'])) {
                    $this->load->model('account/customer');
                    $customer_info = $this->model_account_customer->getCustomerByEmail($user['email']);

                    if (!$customer_info) {
                        $name = isset($user['name']) ? $user['name'] : array();
                        $customer_id = $this->model_account_customer->addCustomer(array(
                            'customer_group_id' => $this->config->get('config_customer_group_id'),
                            'firstname' => isset($name['firstName']) ? $name['firstName'] : '',
                            'lastname' => isset($name['lastName']) ? $name['lastName'] : '',
                            'email' => $user['email'],
                            'telephone' => '',
                            'fax' => '',
                            'password' => token(10),
                            'company' => '',
                            'address_1' => '',
                            'address_2' => '',
                            'city' => '',
                            'postcode' => '',
                            'country_id' => 0,
                            'zone_id' => 0
                        ));
                    } else {
                        $customer_id = $customer_info['customer_id'];
                    }

                    $this->customer->login($user['email'], '', true);
                    $this->response->redirect($this->url->link('account/account', '', true));
                    return;
                }
            }

            $this->response->redirect($this->url->link('account/login', '', true));
        }
    }
}
