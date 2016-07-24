<?php
defined('BASEPATH') OR exit('No direct script access allowed');

//require APPPATH . '/libraries/REST_Controller.php';
require APPPATH.'/libraries/Jsv4/Validator.php';
require APPPATH.'/libraries/Jsv4/ValidationException.php';
include( APPPATH.'controllers/Authentication.php' );
/**
 * @apiDefine tokenParam
 * @apiHeader {String} Token Token do usuario que realizara a ação.
 */
class Routes extends Authentication {
	private $routes = array();
    
    function loadModel(){
        $this->load->model('routes_model', '', TRUE);
    }
    /**
     * @api {get} /routes Requisitar rotas
     * @apiName GetRoutes
     * @apiGroup Routes
     * @apiPermission client
     *
     * @apiUse tokenParam
     *
     * @apiParam (Parametros de url) {boolean} [points] Se false, retorna as rotas sem os pontos.
     *           Se true, retorna com os pontos. Por padrão é false.
     * @apiExample Exemplo de uso:
     *             curl -i http://host/BusTrackerAPI/index.php/routes?points=true
     *
     * @apiSuccess {Integer} id_routes ID unico da rota.
     * @apiSuccess {String} name  Nome da rota.
     * @apiSuccess {String} description Descricao da rota.
     * @apiSuccess {Array} id_buses Id de todos os onibus pertencentes a rota.
     * @apiSuccess {array} points Pontos com latitude e longitude que formam a rota.
     *
     * @apiSuccessExample Resposta successo sem pontos:
     *     HTTP/1.1 200 OK
     *     [
     *       {
     *       "id_routes": "1",
     *       "name": "UFC",
     *       "description": "Rota UFC - MED",
     *       "id_buses": [5,34,1]
     *     },
     *               ...
     *       {
     *       "id_routes": "2",
     *       "name": "UFC 2",
     *       "description": "Rota UFC - UVA",
     *       "id_buses": []
     *     }
     *    ]
     * @apiSuccessExample Resposta successo com pontos:
     *     HTTP/1.1 200 OK
     *     [
     *       {
     *         "id_routes": 1,
     *         "name": "UFC",
     *         "description": "Rota UFC - MED",
     *         "id_buses": [2,28,3]
     *         "points": [{"latitude":3.3232, "logitude":3.23232}, ..., {"latitude":2.343, "logitude":1.32} ]
     *       },
     *               ...
     *      ]
     */
    function getRoutes(){
        //check read permissions
        $token = $this->authenticate();
        if(!$token->valid(Authentication::CLIENT_PERMISSION))
            return $this->makeUnauthorizedResponse();

        $this->loadModel();
        $routes = $this->routes_model->index();
        
        //add buses
        foreach ($routes as $route){
            $this->addIdBuses($route);
        }

        //add points into each route
        if($this->input->get("points") === "true"){
            foreach ($routes as $route){
                $route->points = $this->routes_model->getPoints($route->id_routes);
                $route->googleRoute = $this->getGoogleRoute($route->points);
            }
        }

        return $this->makeJsonRespose($routes, 200);
    }
    /**
     * @api {post} /routes Adicionar uma rota
     * @apiName PostRoutes
     * @apiGroup Routes
     * @apiPermission tracker
     *
     * @apiUse tokenParam
     *
     * @apiParam {String} name Nome da rota a ser adicionada.
     * @apiParam {String} description Descrição da rota a ser adicionada.
     * @apiParam {array} points Conjuntos de pontos(coordenadas geográfica) que quando ligados formam a rota a ser adicionada. 
     *
     * @apiSuccess (201 - RouteCreated) {Integer} id ID unico da rota.
     *
     * @apiError (400 - InvalidJSON) O json enviado é invalido. Isso pode ocorrer por falta de parametros, erros de tipos e erros de sintaxe. 
     * 
     * @apiParamExample {json} Exemplo de requisição:
     *   {
     *       "name": "UFC",
     *       "description": "UFC - MED2",
     *       "points": [
     *         {
     *           "latitude": -3.3233,
     *           "longitude": 3.4323
     *         },
     *         {
     *           "latitude": -3.32332,
     *           "longitude": 3.23232
     *         }
     *       ]
     *   }
     * @apiSuccessExample Exemplo de respota com sucesso:
     *     HTTP/1.1 201 CREATED
     *     {"id" : 9}
     * @apiErrorExample {json} Exemplo de respota com erro no json da requisição :
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
    function addRoute(){
        //check write permissions
        $token = $this->authenticate();
        if(!$token->valid(Authentication::TRACKER_PERMISSION))
            return $this->makeUnauthorizedResponse();

        $validator = $this->validateJson($this->input->raw_input_stream, APPPATH.'/controllers/Schemas/RoutesAdd.json');
    	if($validator->valid){
            $this->loadModel();
            $input = json_decode($this->input->raw_input_stream);
            $input->googleRoute = $this->getGoogleRoute($input->points);
            $googleRoute = json_decode($this->getGoogleRoute($input->points), JSON_NUMERIC_CHECK); 
            // check errors in google route
            if(isset($googleRoute->error_message))
                return $this->makeJsonRespose($input->googleRoute, 400);

            $id = $this->routes_model->insertRoute($input);
            
            return $this->makeJsonRespose(["id" => $id], 201); 
        }
        else{
            return $this->makeJsonRespose($validator->errors, 400);
        }
    }
    function validateJson($json, $schemaPath){
       $route = json_decode($json);

       $schema = json_decode(file_get_contents($schemaPath));
       $validator = Jsv4\Validator::validate( $route, $schema);

       return $validator;
        
    }
    /**
     * @api {put} /routes/:id Atualizar uma rota
     * @apiName PutRoutes
     * @apiGroup Routes
     * @apiPermission tracker
     *
     * @apiUse tokenParam
     *
     * @apiParam {String} [name] Nome da rota a ser atualizada.
     * @apiParam {String} [description] Descrição da rota a ser atualizada.
     * @apiParam {array} [points] Conjuntos de pontos(coordenadas geográfica) que quando ligados formam a rota a ser atualizada.
     *
     * @apiSuccess (201 - RouteUpdated) {Integer} id ID unico da rota.
     *
     * @apiError (400 - InvalidJSON) root O json enviado é invalido. Isso pode ocorrer por falta de parametros, erros de tipos e erros de sintaxe.
     *
     * @apiParamExample {json} Exemplo de requisição:
     *   {
     *       "name": "UFC",
     *       "description": "UFC - MED2",
     *   }
     * @apiSuccessExample Exemplo de respota com sucesso:
     *     HTTP/1.1 201 Ok
     *     {"id" : 9}
     * @apiErrorExample {json} Exemplo de respota com erro no json da requisição :
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
    function updateRoute($id){
    	//check write permissions
        $token = $this->authenticate();
        if(!$token->valid(Authentication::TRACKER_PERMISSION))
            return $this->makeUnauthorizedResponse();

        $validator = $this->validateJson($this->input->raw_input_stream, APPPATH.'/controllers/Schemas/RoutesPut.json');
        if($validator->valid){
            $this->loadModel();
            $input = json_decode($this->input->raw_input_stream);
            
            if(isset($input->points)){
                $input->googleRoute = $this->getGoogleRoute($input->points);
                $googleRoute = json_decode($this->getGoogleRoute($input->points), JSON_NUMERIC_CHECK);
                // check errors in google route
                if(isset($googleRoute->error_message))
                    return $this->makeJsonRespose($input->googleRoute, 400);
            }

            $input->id_routes = $id;//adds id to route
            $id = $this->routes_model->updateRoute($input);

            return $this->makeJsonRespose(["id" => $id], 200);
        }
        else{
            return $this->makeJsonRespose($validator->errors, 400);
        }
    }
    /**
     * @api {get} /routes/:id Requisitar uma rota
     * @apiName GetRoute
     * @apiGroup Routes
     * @apiPermission client
     *
     * @apiUse tokenParam
     *
     * @apiSuccess {Integer} id_routes ID unico da rota.
     * @apiSuccess {String} name  Nome da rota.
     * @apiSuccess {String} description Descricao da rota.
     * @apiSuccess {Array} id_buses Id de todos os onibus pertencentes a rota.
     * @apiSuccess {array} googleRoute Um objeto recebido de uma requisição da Directions API. Requisição
     * cujo o argumento foi o array `points`
     * @apiSuccess {array} points Pontos com latitude e longitude que formam a rota.
     *
     *
     * @apiError (404 - RouteNotFound) {Interger} id ID da rota requisitada não encontrado.
     *
     * @apiSuccessExample Resposta successo:
     *     HTTP/1.1 200 OK
     *     {
     *         "id_routes": 1,
     *         "name": "UFC",
     *         "description": "Rota UFC-BB",
     *         "id_buses": [5,34,1],
     *         "googleRoute": {...}
     *         "points": [
     *           {
     *             "latitude": -3.694505,
     *             "longitude": -40.355569
     *           },
     *                       ...
     *           {
     *             "latitude": -3.690045,
     *             "longitude": -40.351126
     *           }
     *         ]
     *       }       
     *     }
     *
     *
     * @apiErrorExample {json} Exemplo de resposta de erro caso não exista rota com o id requisitado :
     * HTTP/1.1 404 NOT FOUND
     *     {
     *       "id": 9
     *     }
     */
    function getRoute($id){
        //check read permissions
        $token = $this->authenticate();
        if(!$token->valid(Authentication::CLIENT_PERMISSION))
            return $this->makeUnauthorizedResponse();

    	$this->loadModel();
        $route = $this->routes_model->getRoute($id);
        if($route != null){
            $this->addIdBuses($route);
            $route->points = $this->routes_model->getPoints($id);

            return $this->makeJsonRespose($route, 200);
        }

        return $this->makeJsonRespose(["id" => $id], 404);
    }
    function makeJsonRespose($output, $statusCode){
        return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header($statusCode)
                    ->set_output(json_encode($output, JSON_NUMERIC_CHECK));
    }
    function addIdBuses($route){
        //add buses
        $route->id_buses = [];
        $buses = $this->routes_model->getBuses($route->id_routes);
        foreach($buses as $bus)//make array without key 'id_buses'
            $route->id_buses[] = $bus->id_bus;
    }
    /**
     * @api {get} /routes/:id/points Requisitar pontos de um rota
     * @apiName GetPoints
     * @apiGroup Routes
     * @apiPermission client
     *
     * @apiUse tokenParam
     *
     * @apiParam {Integer} id ID da rota que se requisita os pontos.
     * @apiSuccess {array} points Pontos com latidute e longitude que formam a rota.
     *
     * @apiSuccessExample Resposta successo:
     *     HTTP/1.1 200 OK
     *          [
     *           {
     *             "latitude": 3.694505,
     *             "longitude": -40.355569
     *           },
     *           {
     *             "latitude": -3.693412,
     *             "longitude": -40.353905
     *           },
     *           {
     *             "latitude": -3.691779,
     *             "longitude": -40.354125
     *           },
     *           {
     *             "latitude": -3.690414,
     *             "longitude": -40.354297
     *           },
     *           {
     *             "latitude": -3.690318,
     *             "longitude": -40.353154
     *           },
     *           {
     *             "latitude": -3.690045,
     *             "longitude": -40.351126
     *           }
     *         ]
     */
    function getPoints($id){
        //check read permissions
        $token = $this->authenticate();
        if(!$token->valid(Authentication::CLIENT_PERMISSION))
            return $this->makeUnauthorizedResponse();

        $this->loadModel();
        $points = $this->routes_model->getPoints($id);
        
        if($this->routes_model->getPoints($id) == null )
            return $this->makeJsonRespose(["id" => $id], 404);

        return $this->makeJsonRespose($points, 200);
    }
    /**
     * @api {delete} /routes/:id Deletar uma rota 
     * @apiName DeleteRoutes
     * @apiGroup Routes
     * @apiPermission tracker
     *
     * @apiUse tokenParam
     *
     * @apiParam {Integer} id ID da rota a ser deletada.
     * @apiSuccess (204 - RouteDeleted) {Integer} id ID da rota deletada.
     * @apiError (404 - RouteNotFound) {Interger} id ID da rota requisitada não encontrado.
     * 
     * @apiErrorExample {json} Exemplo de resposta de erro caso não exista rota com o id requisitado :
     * HTTP/1.1 404 NOT FOUND
     *     {
     *       "id": 9
     *     }
     */
    function deleteRoute($id){
        //check write permissions
        $token = $this->authenticate();
        if(!$token->valid(Authentication::TRACKER_PERMISSION))
            return $this->makeUnauthorizedResponse();

    	$this->loadModel();
        $statusCode = 404;

        if($this->routes_model->getRoute($id) != null){
            $this->routes_model->deleteRoute($id);
            $statusCode = 204;
        }

        return $this->makeJsonRespose(["id" => $id], $statusCode);
    }
    private function getGoogleRoute($points){
        if(count($points) < 2)
            return json_encode(['error_message' => 'At least 2 points is required', 
                                'statusCode' => 400], JSON_NUMERIC_CHECK);

        //make way points
        $waypoints = "";
        for($i = 1; $i < count($points) - 1;$i++)
            $waypoints = $waypoints.$points[$i]->latitude.",".$points[$i]->longitude."|";

        $origin = $points[0];
        $destination = $points[count($points)-1];
        $request = "https://maps.googleapis.com/maps/api/directions/json?".
        "origin={$origin->latitude},{$origin->longitude}&".
        "destination={$destination->latitude},{$destination->longitude}&".
        "waypoints=via:{$waypoints}&".
        "key=".Routes::GOOGLE_SERVER_KEY();

        return Routes::requestGet($request);
    }
    private static function requestGet($url){
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url
        ));

        $resp = curl_exec($curl);

        curl_close($curl);
        return $resp;
    }
    private static function GOOGLE_SERVER_KEY(){
        global $BusTrackerConfig;
        return $BusTrackerConfig["GOOGLE_SERVER_KEY"];
    }
}
