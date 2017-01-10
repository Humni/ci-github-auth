<?php defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Login with GitHub for CodeIgniter
 *
 * Library for CodeIgniter that integrates with GitHubs OAuth API
 *
 * @authors     Nick Humphries (Humni)
 * @license     MIT
 * @link        https://github.com/Humni/ci-github-auth.git
 */

class Github {

    public function __construct(){
        $this->ci =& get_instance();

        $this->ci->load->config('github');
        $this->client_id = $this->ci->config->item('client_id');
        $this->client_secret = $this->ci->config->item('client_secret');
        $this->redirect_uri = $this->ci->config->item('redirect_uri');
        $this->scope = $this->ci->config->item('scope');
        $this->allow_signup = $this->ci->config->item('allow_signup');

        $this->ci->load->library('session');
    }

    /*
     * Creates a url to send the user to
     */
    public function generateLoginURL(){
        return urldecode('https://github.com/login/oauth/authorize?client_id='.$this->client_id.'&redirect_uri='.$this->redirect_uri
            .'&scope='.$this->scope.'&state='.$this->generate_state().'&allow_signup='.$this->allow_signup);
    }

    /*
     * Gets the temporary code from the URI and gets the access token from GitHub
     */
    public function getAccessToken() {
        $code = $this->ci->input->get('code');
        $state = $this->ci->input->get('state');

        if($state != $this->ci->session->userdata('github_state')){
            return false;
        }

        $data = [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'code' => $code,
            'redirect_uri' => $this->redirect_uri,
            'state' => $this->ci->session->userdata('github_state'),
        ];

        //post to github to get the token
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_URL,'https://github.com/login/oauth/access_token');
        curl_setopt($ch,CURLOPT_HTTPHEADER, array('Accept: application/json'));
        curl_setopt($ch,CURLOPT_POST,count($data));
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data);

        //execute post
        $result = json_decode(curl_exec($ch),true);

        if(isset($result['error'])){
            return false;
        }

        return $result['access_token'];
    }

    /*
     * Generates a state to send with the GitHub authentication to prevent CSRF
     */
    private function generate_state(){
        $hash = hash('sha256', microtime(TRUE).rand().$_SERVER['REMOTE_ADDR']);
        $this->ci->session->set_userdata('github_state', $hash);
        return $hash;
    }

}