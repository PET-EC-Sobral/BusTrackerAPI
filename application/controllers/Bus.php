<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH.'/libraries/Jsv4/Validator.php';
require APPPATH.'/libraries/Jsv4/ValidationException.php';

class Bus extends CI_Controller { 
    
    function loadModel(){
        $this->load->model('bus_model', '', TRUE);
    }
    /**
     * @api {get} /routes/:idRoute/buses Requisitar os ônibus de uma rota
     * @apiName GetBuses
     * @apiGroup Bus
     *
     * @apiParam {Integer} idRoute ID da rota onde se deseja requisitar os onibus.
     * @apiParam (Parametros de url) {Interger} localizations Se maior que 0, retorna os ônibus com suas n ultimas 
     *            localizações. Onde n = localizations. 
     * 
     * @apiExample Exemplo de uso:
     *             curl -i http://host/BusTrackerAPI/index.php/routes/7/buses?localizations=2
     *
     * @apiSuccess (200 - Success) {Integer} id_bus ID unico do ônibus.
     * @apiSuccess (200 - Success) {String} velocity  Velocidade atual do ônibus.
     * @apiSuccess (200 - Success) {String} id_routes ID da rota cujo o ônibus pertence.
     * @apiSuccess (200 - Success) {String} lastLocalization Array com as ultimas localizações do ônibus.
     * 
     * @apiError (404 - BusNotFound) {Interger} idRoute ID da rota requisita não encontrada, ou a rota não contém ônibus.
     *
     * @apiSuccessExample Resposta de successo sem localizações:
     *     HTTP/1.1 200 OK
     * [
     *   {
     *     "id_bus": 1,
     *     "velocity": 30,
     *     "id_routes": 86
     *   },
     *           ...
     *   {
     *     "id_bus": 2,
     *     "velocity": 34,
     *     "id_routes": 86
     *   }
     * ]
     * @apiSuccessExample Resposta de successo com localizações:
     *     HTTP/1.1 200 OK
     *     [
     *       {
     *         "id_bus": 1,
     *         "velocity": 30,
     *         "id_routes": 86,
     *         "lastLocalizations": [
     *           {
     *             "latitude": 3.2,
     *             "longitude": -3.2,
     *             "date": "2016-06-16 08:22:43"
     *           },
     *              ...
     *           {
     *             "latitude": 3,
     *             "longitude": 4,
     *             "date": "2016-06-16 10:08:25"
     *           }
     *         ]
     *       },
     *           ...
     *       {
     *         "id_bus": 2,
     *         "velocity": 34,
     *         "id_routes": 86,
     *         "lastLocalizations": []
     *       }
     *     ]
     */
    function getBuses($routeId){
        $this->loadModel();
        $buses = $this->bus_model->index($routeId);
        
        if(count($buses) == 0){//if not found buses
            return $this->makeJsonRespose(['id' => $routeId], 404);
        }

        //add last localizations to bus
        $limit = (int) $this->input->get("localizations"); 
        if($limit > 0){
            foreach ($buses as $bus){
                $bus->lastLocalizations = $this->bus_model->getLastLocalizations($bus->id_bus, $limit);
            }
        }

        return $this->makeJsonRespose($buses, 200);
    }
    /**
     * @api {get} /routes/:idRoute/buses/:idBus Requisitar um ônibus
     * @apiName GetBus
     * @apiGroup Bus
     *
     * @apiParam {Integer} idRoute ID da rota que contem o onibus que se requisita.
     * @apiParam {Integer} idBus ID do onibus solicitado.          
     * @apiParam (Parametros de url) {Interger} localizations Se maior que 0, retorna o ônibus com suas n ultimas 
     *            localizações. Onde n = localizations. Caso contrario, retorna o onibus com uma localização apenas, a saber, a ultima localização.
     * 
     * @apiExample Exemplo de uso:
     *             curl -i http://host/BusTrackerAPI/index.php/routes/7/buses/2?localizations=2
     *
     * @apiSuccess (200 - Success) {Integer} id_bus ID unico do ônibus.
     * @apiSuccess (200 - Success) {String} velocity  Velocidade atual do ônibus.
     * @apiSuccess (200 - Success) {String} id_routes ID da rota cujo o ônibus pertence.
     * @apiSuccess (200 - Success) {String} lastLocalization Array com as ultimas localizações do ônibus.
     * 
     * @apiError (404 - BusNotFound) {Interger} idBus ID do onibus requisitado não encontrado.
     * @apiError (404 - BusNotFound) {Interger} idRoute ID da rota que não foi encontrada, ou a rota não contem um onibus
     *                    com o id = idBus.
     *
     * @apiSuccessExample Resposta com successo:
     *     HTTP/1.1 200 OK
     *       {
     *         "id_bus": 1,
     *         "velocity": 30,
     *         "id_routes": 86,
     *         "lastLocalizations": [
     *           {
     *             "latitude": 3.2,
     *             "longitude": -3.2,
     *             "date": "2016-06-16 08:22:43"
     *           },
     *              ...
     *           {
     *             "latitude": 3.56,
     *             "longitude": 4.12,
     *             "date": "2016-06-16 10:08:25"
     *           }
     *         ]
     *       }
     */
    function getBus($idRoute, $idBus){
        $this->loadModel();
        $bus = $this->bus_model->getBus($idRoute, $idBus);

        $localizations = (int) $this->input->get("localizations"); 
        $localizations = $localizations < 1 ? 1 : $localizations;
        if($bus != null){
            $bus->lastLocalizations = $this->bus_model->getLastLocalizations($bus->id_bus, $localizations);

            return $this->makeJsonRespose($bus, 200);
        }

        return $this->makeJsonRespose(["idBus" => $idBus, "idRoute" => $idRoute], 404);                
    }
    /**
     * @api {get} /routes/:idRoute/buses/:idBus/positions Requisitar localizações de um ônibus
     * @apiName GetPositions
     * @apiGroup Bus
     *
     * @apiParam {Integer} idRoute ID da rota que contem o onibus que se requisita as localizações.
     * @apiParam {Integer} idBus ID do onibus que se requisita as localizações.
     *
     * @apiParam (Parametros de url) {Integer} length Especifica o tamanho do array de localizações desejado. 
     * 
     * @apiExample Exemplo de uso:
     *             curl -i http://host/BusTrackerAPI/index.php/routes/7/buses/2/positions?length=2
     *
     * @apiSuccess {array} root Array com ultimas localizações (latidute e longitude) que formam a rota.
     *
     * @apiError (404 - BusNotFound) {Interger} idBus ID do onibus requisitado não encontrado.
     * @apiError (404 - BusNotFound) {Interger} idRoute ID da rota que não foi encontrada, ou a rota não contem um onibus
     *                    com o id = idBus.
     *
     * @apiSuccessExample Exemplo de resposta com successo:
     *     HTTP/1.1 200 OK
     *          [
     *             {
     *               "latitude": 3.2,
     *               "longitude": -3.2,
     *               "date": "2016-06-16 08:22:43"
     *             },
     *                   ...
     *             {
     *               "latitude": 3.92,
     *               "longitude": 4.122,
     *               "date": "2016-06-16 10:08:25"
     *             }
     *           ]
     */
    function getLocalizations($idRoute, $idBus){
        $this->loadModel();
        $lengthDefault = 10;

        if($this->bus_model->getBus($idRoute, $idBus) === null){
            return $this->makeJsonRespose(["idBus" => $idBus, "idRoute" => $idRoute], 404);
        }

        $length = (int) $this->input->get("length");
        $length = $length < 1 ?  $lengthDefault : $length; 

        $lastLocalizations = $this->bus_model->getLastLocalizations($idBus, $length);
        
        return $this->makeJsonRespose($lastLocalizations, 200);
    }
    /**
     * @api {post} /routes/:idRoute/buses/:idBus/position Adicionar uma localização a um ônibus
     * @apiName PostPosition
     * @apiGroup Bus
     *
     * @apiParam {Integer} idRoute ID da rota que contem o onibus que será adicionada a localização.
     * @apiParam {Integer} idBus ID do onibus que será adicionada a localização.
     *
     * @apiParam {double} latitude Latitude da localização a ser adicionada.
     * @apiParam {double} longitude Longitude da localização a ser adicionada. 
     *
     * 
     * @apiSuccess (201 - LocalizationCreated) {string} date String formatada com a data da inserçao da localização.
     *
     * @apiError (404 - BusNotFound) {Interger} idBus ID do onibus requisitado não encontrado.
     * @apiError (404 - BusNotFound) {Interger} idRoute ID da rota que não foi encontrada, ou a rota não contem um onibus
     *                    com o id = idBus.
     *
     * @apiError (400 - InvalidJSON) O json enviado é invalido. Isso pode ocorrer por falta de parametros, erros de tipos e erros de sintaxe.
     *
     * @apiSuccessExample {json} Exemplo de respota com sucesso:
     *     HTTP/1.1 201 CREATED
     *     {"date": "2016-06-16 08:22:43"}
     *
     * @apiParamExample {json} Exemplo de requisição:
     *      {
     *        "latitude": 3.2921,
     *        "longitude": -3.23123,
     *      }
     *
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
     *           
     */
    function addLocalization($idRoute, $idBus){
        $this->loadModel();

        if($this->bus_model->getBus($idRoute, $idBus) === null){
            return $this->makeJsonRespose(["idBus" => $idBus, "idRoute" => $idRoute], 404);
        }

        $validator = $this->validateJson($this->input->raw_input_stream, APPPATH.'/controllers/Schemas/LocalizationsAdd.json');
        if($validator->valid){
            $this->loadModel();
            $input = json_decode($this->input->raw_input_stream);
            $date = $this->bus_model->insertLocalization($idRoute, $idBus, $input);
            
            return $this->makeJsonRespose(["date" => $date], 201);
        }
        else{
            return $this->makeJsonRespose($validator->errors, 400);
        }
    }
    /**
     * @api {post} /routes/:idRoute/buses Adicionar um ônibus a uma rota
     * @apiName PostBus
     * @apiGroup Bus
     *
     * @apiSuccess (201 - RouteCreated) {Integer} idBus ID unico do onibus criado.
     * @apiSuccess (201 - RouteCreated) {Integer} idRoute ID da rota em que o onibus foi criado.
     *
     * @apiError (400 - InvalidJSON) root O json enviado é invalido. Isso pode ocorrer por falta de parametros, erros de tipos e erros de sintaxe. 
     * 
     * @apiParamExample {json} Exemplo de requisição:
     *   {//Isso mesmo, um objeto vazio :/
     *   }
     * @apiSuccessExample Exemplo de respota com sucesso:
     *     HTTP/1.1 201 CREATED
     *     {"idBus" : 5, "idRoute": 77}
     * @apiErrorExample {json} Exemplo de resposta com erro no json da requisição :
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
    function addBus($idRoute){
        $this->loadModel();
        $validator = $this->validateJson($this->input->raw_input_stream, APPPATH.'/controllers/Schemas/BusesAdd.json');

        if($validator->valid){
            $this->loadModel();
            $input = json_decode($this->input->raw_input_stream);
            $idBus = $this->bus_model->insertBus($idRoute, $input);
            
            return $this->makeJsonRespose(["idBus" => $idBus, "idRoute" => $idRoute], 201); 
        }
        else{
            return $this->makeJsonRespose($validator->errors, 400);
        }
    }
    /**
     * @api {delete} /routes/:idRoute/buses/:idBus/positions Deletar as localizações de um ônibus
     * @apiName DeleteBusPositions
     * @apiGroup Bus
     *
     * @apiParam {Integer} idRoute ID da rota que contem o onibus.
     * @apiParam {Integer} idBus ID do onibus que se deseja apagar as localizações.
     *
     * @apiSuccess (204 - LocalizationsDeleted) {Integer} idBus ID do onibus que deve suas localizações apagadas.
     * @apiSuccess (204 - LocalizationsDeleted) {Integer} idRoutes ID da rota do onibus especificado.
     *
     * @apiError (404 - RouteNotFound) {Interger} idRoute ID da rota requisitada não encontrado.
     * @apiError (404 - RouteNotFound) {Interger} idBus ID do onibus requisitado não encontrado.
     * 
     * @apiErrorExample {json} Exemplo de resposta de erro caso não exista o onibus com o id requisitado :
     * HTTP/1.1 404 NOT FOUND
     *     {
     *       "idBus": 9,
     *       "idRoute": 92
     *     }
     */
    function deleteLocalizations($idRoute, $idBus){
        $this->loadModel();
        $statusCode = 404;

        if($this->bus_model->getBus($idRoute, $idBus) != null){
            $this->bus_model->deleteLocalizations($idRoute, $idBus);
            $statusCode = 204;
        }

        return $this->makeJsonRespose(["idBus" => $idBus, "idRoute" => $idRoute], $statusCode);
    }
    /**
     * @api {delete} /routes/:idRoute/buses/:idBus Deletar um onibus de uma rota
     * @apiName DeleteBus
     * @apiGroup Bus
     *
     * @apiParam {Integer} idRoute ID da rota que contem o onibus.
     * @apiParam {Integer} idBus ID do onibus que se deseja apagar.
     *
     * @apiSuccess (204 - LocalizationsDeleted) {Integer} idBus ID do onibus deletado.
     * @apiSuccess (204 - LocalizationsDeleted) {Integer} idRoutes ID da rota do onibus deletado.
     *
     * @apiError (404 - RouteNotFound) {Interger} idRoute ID da rota requisitada não encontrado.
     * @apiError (404 - RouteNotFound) {Interger} idBus ID do onibus requisitado não encontrado.
     *
     * @apiErrorExample {json} Exemplo de resposta de erro caso não exista o onibus com o id requisitado :
     * HTTP/1.1 404 NOT FOUND
     *     {
     *       "idBus": 8,
     *       "idRoute": 2
     *     }
     */
    function deleteBus($idRoute, $idBus){
        $this->loadModel();
        $statusCode = 404;

        if($this->bus_model->getBus($idRoute, $idBus) != null){
            $this->bus_model->deleteBus($idRoute, $idBus);
            $statusCode = 204;
        }

        return $this->makeJsonRespose(["idBus" => $idBus, "idRoute" => $idRoute], $statusCode);
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
}
