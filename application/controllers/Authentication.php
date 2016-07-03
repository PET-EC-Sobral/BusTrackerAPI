<?php
defined('BASEPATH') OR exit('No direct script access allowed');

include( APPPATH.'libraries/aes.class.php' );

class Token {
    private $permision;

    public function __construct($permision){
        $this->permision = $permision;
    }
    public function getPermision(){
        return $this->permision;
    }
    public function valid($permision){
        if($this->getPermision() != $permision)
            return false;

        return true;
    }
}

class Authentication extends CI_Controller {
    const CLIENT_PERMISION = 1;
    const TRACKER_PERMISION = 2;

    final function authenticate(){
        $tokenCrypt = $this->input->get_request_header('Token', TRUE);
        $tokenCrypt = base64_decode($tokenCrypt);

        $aes = new AES(Authentication::CRYPT_KEY());
        $token = json_decode($aes->decrypt($tokenCrypt));

        if($token == null || json_last_error() != JSON_ERROR_NONE)
            return new Token(-1);

        return new Token($token->permision);
    }

    final function buildToken($permision){
        $aes = new AES(Authentication::CRYPT_KEY());
        $tokenJson = json_encode(["permision" => $permision]);

        return base64_encode($aes->encrypt($tokenJson));
    }
    private static function CRYPT_KEY(){
        return "mysecretcryptkey";//a 128 bits crypt key 
    }
    public function makeUnauthorizedResponse(){
        return $this->output->set_status_header(401);
    }
}
