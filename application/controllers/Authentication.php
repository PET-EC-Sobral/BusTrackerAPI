<?php
defined('BASEPATH') OR exit('No direct script access allowed');

include( APPPATH.'libraries/aes.class.php' );
include(APPPATH."config/config.php");

class Token {
    private $permission;

    public function __construct($permission){
        $this->permission = $permission;
    }
    public function getPermission(){
        return $this->permission;
    }
    public function valid($permission){
        if($this->getPermission() != $permission)
            return false;

        return true;
    }
}

class Authentication extends CI_Controller {
    const CLIENT_PERMISSION = 1;
    const TRACKER_PERMISSION = 2;

    final function authenticate(){
        //decode token
        $tokenCrypt = $this->input->get_request_header('Token', TRUE);
        $tokenCrypt = base64_decode($tokenCrypt);

        $aes = new AES(Authentication::CRYPT_KEY());
        $token = json_decode($aes->decrypt($tokenCrypt));

        if($token == null || json_last_error() != JSON_ERROR_NONE)//invalid token
            return new Token(-1);

        //get user from database
        $this->load->model("user_model",  '', TRUE);
        $user = $this->user_model->get($token);
        if($user == null)//user not found
            return new Token(-1);

        return new Token($user->permission);
    }

    final static function buildToken($user){
        $aes = new AES(Authentication::CRYPT_KEY());
        $tokenJson = json_encode(["email" => $user->email,
                                  "password" => $user->password]);

        return base64_encode($aes->encrypt($tokenJson));
    }
    private static function CRYPT_KEY(){
        global $BusTrackerConfig;
        return $BusTrackerConfig["CRYPT_KEY"];//a 128 bits crypt key
    }
    public function makeUnauthorizedResponse(){
        return $this->output->set_status_header(401);
    }
}
