<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH.'/libraries/Jsv4/Validator.php';
require APPPATH.'/libraries/Jsv4/ValidationException.php';
include( APPPATH.'controllers/Authentication.php' );
include(APPPATH."config/config.php");

/**
 * @apiDefine tokenParam
 * @apiHeader {String} Token Token do usuario que realizara a ação.
 */
/**
 * @apiDefine invalidJson
 * @apiError (400 - InvalidJSON) {json} root O json enviado é invalido. Isso pode ocorrer por falta de parametros, erros de tipos e erros de sintaxe.
 */
/**
 * @apiDefine badResquestExemple
 * @apiErrorExample {json} Exemplo de respota com erro no json da requisição:
 * HTTP/1.1 400 BAD REQUEST
 * [
 *     {
 *       "code": 0,
 *       "dataPath": "",
 *       "schemaPath": "/type",
 *       "message": "Invalid type: null"
 *     }
 *   ]
 */


class User extends Authentication {
    /**
     * @api {post} /users Registrar um usuário
     * @apiName PostUsers
     * @apiGroup Users
     * @apiPermission none
     *
     * @apiParam {String} name Nome do usuario a ser criado.
     * @apiParam {String} email Email do usuario a ser criado. Este email é unico para cada usuário.
     * @apiParam {String} password A senha do usuário a ser criado. É permitido todos os tipos de caracteres.
     * @apiParam {Integer} permission A permissão que o usuario tem. Se 1, tem permissão client. Se 2, tem permissão
     *           de tracker. Se 3, tem permissão de admin(este tem todas as permisões possiveis).
     *
     * @apiParam (201 - UserCreated) {String} name Nome do usuario criado.
     * @apiParam (201 - UserCreated) {String} email Email do usuario criado. Este email é unico para cada usuário.
     * @apiParam (201 - UserCreated) {Integer} permission A permissão que o usuario tem. Se 1, tem permissão de leitura. Se 2, tem permissão
     *           de escrita. Se 3, tem permissão de escrita e leitura.
     *
     * @apiError (409 - ExistingEmail) {json} root Já existe um usuário com este email.
     * @apiUse invalidJson
     *
     * @apiParamExample {json} Exemplo de requisição:
     *    {
     *     "name": "L Lawliet",
     *     "email": "llawliet@email.com",
     *     "password": "lsecret",
     *     "permission": 1
     *   }
     * @apiSuccessExample Exemplo de respota com sucesso:
     *     HTTP/1.1 201 CREATED
     *     {
     *        "name": "L Lawliet",
     *        "email": "llawliet@email.com",
     *        "permission": 1,
     *        "token": "Me1wdd1TCDqKVym2...hRhCQQ18pdEwjHuig=="
     *     }
     *
     * @apiUse badResquestExemple
     *
     * @apiErrorExample {json} Exemplo de respota com email em uso:
     * HTTP/1.1 409 CONFLICT
     * {
     *     "name": "L Lawliet",
     *     "email": "llawliet@email.com",
     *     "permission": 1
     *   }
     */
    public function create(){
        $validator = $this->validateJson($this->input->raw_input_stream, APPPATH.'/controllers/Schemas/UsersAdd.json');
        if($validator->valid){
            $this->loadModel();

            $input = $this->userFromJson($this->input->raw_input_stream);
            $result = $this->user_model->insert($input);

            if(!$result){//check conflict
                unset($input->password);
                return $this->makeJsonRespose($input, 409);
            }

            $input->token = Authentication::buildToken($input);

            unset($input->password);
            return $this->makeJsonRespose($input, 201);
        }
        else{
            return $this->makeJsonRespose($validator->errors, 400);
        }
    }
    /**
     * @api {post} /users/tokens Adquirir um token
     * @apiName PostToken
     * @apiGroup Users
     * @apiPermission none
     *
     * @apiParam {String} email Email do usuario que pertencerá o token.
     * @apiParam {String} password A senha do usuário que pertencerá o token.
     *
     * @apiParam (200 - Success) {String} token Token requisitado.
     *
     * @apiError (401 - InvalidCredentials) {json} root Email ou senha errados.
     * @apiUse invalidJson
     *
     * @apiParamExample {json} Exemplo de requisição:
     *    {
     *     "email": "llawliet@email.com",
     *     "password": "lsecret",
     *   }
     * @apiSuccessExample Exemplo de respota com sucesso:
     *     HTTP/1.1 200 OK
     *     {
     *        "token":"Me1wdd1TCDqKVym2...hRhCQQ18pdEwjHuig=="
     *     }
     *
     * @apiUse badResquestExemple
     *
     */
    public function getToken(){
        $validator = $this->validateJson($this->input->raw_input_stream, APPPATH.'/controllers/Schemas/UsersLogin.json');
        if($validator->valid){
            $this->loadModel();
            $input = userFromJson($this->input->raw_input_stream);

            $user = $this->user_model->get($input);
            unset($input->password);
            if($user == null)//invalid crenditials
                return $this->makeJsonRespose($input, 401);

            $token = Authentication::buildToken($user);

            return $this->makeJsonRespose(['token' => $token], 200);
        }
        else{
            return $this->makeJsonRespose($validator->errors, 400);
        }
    }
    /**
     * @api {get} /users Requisitar um usuario
     * @apiName GetUser
     * @apiGroup Users
     * @apiPermission client tracker admin
     *
     * @apiUse tokenParam
     * 
     * @apiParam (200 - Success) {String} name Nome do usuario.
     * @apiParam (200 - Success) {String} email Email do usuario.
     * @apiParam (200 - Success) {Integer} permission A permissão que o usuario tem. Se 1, tem permissão de leitura. Se 2, tem permissão
     *           de escrita. Se 3, tem permissão de escrita e leitura.
     *
     * @apiError 404  UserNotFound
     *
     * @apiHeaderExample {Json} Exemplo de Header:
     *  {"Token": "Me1wdd1TCDqKVym2...hRhCQQ18pdEwjHuig=="}
     *
     * @apiSuccessExample Exemplo de respota com sucesso:
     *     HTTP/1.1 200 OK
     *     {
     *       "email": "llawliet@email.com",
     *       "name": "L Lawliet",
     *       "permission": 1
     *     }
     *
     */
    public function getUser(){
        $user = parent::getUser();

        if($user == null)
            return $this->output->set_status_header(404);

        return $this->makeJsonRespose($user, 200);
    }
    function loadModel(){
        $this->load->model('user_model', '', TRUE);
    }
    function makeJsonRespose($output, $statusCode){
        return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header($statusCode)
                    ->set_output(json_encode($output, JSON_NUMERIC_CHECK));
    }
    function validateJson($json, $schemaPath){
       $route = json_decode($json);

       $schema = json_decode(file_get_contents($schemaPath));
       $validator = Jsv4\Validator::validate( $route, $schema);

       return $validator;
    }
    public function userFromJson($json){
        $user = json_decode($this->input->raw_input_stream);
        $user->password = User::hash($user->password);
        return $user;
    }
    private static function SALT_USER_PASSWORD(){
        global $BusTrackerConfig;
        return $BusTrackerConfig["SALT_USER_PASSWORD"];
    }
    private static function hash($value){
        return hash('sha512',  User::SALT_USER_PASSWORD().$value);
    }
}
