<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH.'/libraries/Jsv4/Validator.php';
require APPPATH.'/libraries/Jsv4/ValidationException.php';
include( APPPATH.'controllers/Authentication.php' );
/**
 * @apiDefine tokenParam
 * @apiHeader {String} Token Token do usuario que realizara a ação.
 */
class ReferencePoints extends Authentication {

    function loadModel(){
        $this->load->model('ReferencePoints_model', '', TRUE);
    }
    /**
     * @api {get} /routes/:idRoute/referencepoints Requisitar pontos de referência de uma rota
     * @apiName IndexReferencePoints
     * @apiGroup Routes
     * @apiPermission client
     *
     * @apiExample Exemplo de uso:
     *             curl -i http://host/BusTrackerAPI/index.php/routes/86/referencepoints
     *
     * @apiUse tokenParam
     *
     * @apiParam (URLParam) {Integer} idRoute ID unico da rota que os pontos pertencem.
     *
     * @apiSuccess {Integer} id_routes  ID unico da rota que os pontos pertencem.
     * @apiSuccess {String} name Nome do ponto
     * @apiSuccess {String} image URL de uma imagem que representa o ponto.
     * @apiSuccess {String} description Pequena descrição e observações sobre o ponto.
     * @apiSuccess {String} latitude Latitude do ponto.
     * @apiSuccess {String} longitude Longitude do ponto.
     *
     *
     * @apiSuccessExample Resposta sucesso:
     *     HTTP/1.1 200 OK
     *   [
     *    {
     *      "id_routes": 86,
     *      "name": "Parada 1",
     *      "image": "http://image.com/img.png",
     *      "description": "Este é o ponto de parada 1.",
     *      "latitude": 3.44343,
     *      "longitude": 40.43984
     *    },
     *    ...
     *    {
     *      "id_routes": 86,
     *      "name": "Parada 2",
     *      "image": "http://image.com/img.png",
     *      "description": "Este é o ponto de parada 2.",
     *      "latitude": 3.44343,
     *      "longitude": 41.43984
     *    }
     *  ]
     */
    function index($idRoute){
        //check read permissions
        $token = $this->authenticate();
        if(!$token->valid(Authentication::CLIENT_PERMISSION))
            return $this->makeUnauthorizedResponse();

        //check route exist
        $this->load->model('routes_model', '', TRUE);
        if(!$this->routes_model->existRoute($idRoute))
            return $this->makeJsonRespose(["error" => "NOT_FOUND_ROUTE"], 404);

        $this->loadModel();
        $routes = $this->ReferencePoints_model->index($idRoute);

        return $this->makeJsonRespose($routes, 200);
    }
    /**
     * @api {post} /routes/:idRoute/referencepoints Adicionar ponto de referência em uma rota
     * @apiName PostReferencePoint
     * @apiGroup Routes
     * @apiPermission tracker
     *
     * @apiUse tokenParam
     *
     * @apiParam (URLParam) {Integer} id_routes ID unico da rota que o ponto pertence.
     * @apiParam (BodyParam) {String} name Nome do ponto
     * @apiParam (BodyParam) {String} image URL de uma imagem que representa o ponto.
     * @apiParam (BodyParam) {String} [description] Pequena descrição e observações sobre o ponto.
     * @apiParam (BodyParam) {String} latitude Latitude do ponto.
     * @apiParam (BodyParam) {String} longitude Longitude do ponto.
     *
     * @apiSuccess {Integer} id_routes  ID unico da rota que os pontos pertencem.
     * @apiSuccess {String} name Nome do ponto
     * @apiSuccess {String} image URL de uma imagem que representa o ponto.
     * @apiSuccess {String} description Pequena descrição e observações sobre o ponto.
     * @apiSuccess {String} latitude Latitude do ponto.
     * @apiSuccess {String} longitude Longitude do ponto.
     * @apiSuccess {Boolean} result TRUE se o ponto foi adcionado com sucesso, FALSE caso contrário.
     *
     * @apiError (400 - InvalidJSON) root O json enviado é invalido. Isso pode ocorrer por falta de parametros, erros de tipos e erros de sintaxe.
     *
     * @apiParamExample {json} Exemplo de requisição:
     *   {
     *       "name": "Parada 2",
     *       "image": "http://image.com/img.png",
     *       "description": "Este é o ponto de parada 2.",
     *       "location":{
     *           "latitude": 3.44343,
     *           "longitude": 41.43984
     *       }
     *   }
     * @apiSuccessExample Exemplo de respota com sucesso:
     *     HTTP/1.1 201 CREATED
     *     {
     *       "result": true,
     *       "reference": {
     *         "name": "Parada 2",
     *         "image": "http://image.com/img.png",
     *         "description": "Este é o ponto de parada 2.",
     *         "location": {
     *           "latitude": 3.44343,
     *           "longitude": 41.43984
     *         },
     *         "id_routes": 86
     *       }
     *     }
     */
    function add($idRoute){
        //check write permissions
        $token = $this->authenticate();
        if(!$token->valid(Authentication::TRACKER_PERMISSION))
            return $this->makeUnauthorizedResponse();

        $validator = $this->validateJson($this->input->raw_input_stream, APPPATH.'/controllers/Schemas/ReferencePointsAdd.json');
    	if($validator->valid){
            $this->loadModel();
            $reference = json_decode($this->input->raw_input_stream);
            $reference->id_routes = $idRoute;

            //check route exist
            $this->load->model('routes_model', '', TRUE);
            if(!$this->routes_model->existRoute($idRoute))
                return $this->makeJsonRespose(["error" => "NOT_FOUND_ROUTE"], 404);

            $this->loadModel();
            $result = new stdClass();
            $result->result = $this->ReferencePoints_model->insert($reference);
            $result->reference = $reference;

            return $this->makeJsonRespose($result, 201);
        }
        else{
            return $this->makeJsonRespose($validator->errors, 400);
        }
    }
    function validateJson($json, $schemaPath){
       $input = json_decode($json);

       $schema = json_decode(file_get_contents($schemaPath));
       $validator = Jsv4\Validator::validate( $input, $schema);

       return $validator;
    }
    function makeJsonRespose($output, $statusCode){
        return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header($statusCode)
                    ->set_output(json_encode($output, JSON_NUMERIC_CHECK));
    }
}
