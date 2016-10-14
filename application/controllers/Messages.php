<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH.'/libraries/Jsv4/Validator.php';
require APPPATH.'/libraries/Jsv4/ValidationException.php';
include( APPPATH.'controllers/Authentication.php' );
include( APPPATH.'third_party/FirebaseUtil.php' );
/**
 * @apiDefine tokenParam
 * @apiHeader {String} Token Token do usuario que realizara a ação.
 */
class Messages extends Authentication {
   /**
     * @api {post} /routes/:idRoute/buses/:idBus/messages Adicionar uma mensagem a um onibus
     * @apiName PostBusMessage
     * @apiGroup Messages
     * @apiPermission tracker
     *
     * @apiUse tokenParam
     *
     * @apiParam (URLParam) {Integer} idRoute ID unico da rota que possui o onibus da mensagem.
     * @apiParam (URLParam) {Integer} idBus ID unico do onibus que a mensagem pertence.
     * @apiParam (BodyParam) {String} titke Título da mensagem a ser adicionada.
     * @apiParam (BodyParam) {String} [message] Texto da mensagem.
     *
     * @apiSuccess (201 - MessageCreated) {Integer} id ID unico da mensagem.
     * @apiSuccess (201 - MessageCreated) {Integer} idRoute ID unico da rota que possui o onibus da mensagem.
     * @apiSuccess (201 - MessageCreated) {Integer} idBus ID unico do onibus que a mensagem pertence.
     * @apiSuccess (201 - MessageCreated) {String} titke Título da mensagem a ser adicionada.
     * @apiSuccess (201 - MessageCreated) {String} message Texto da mensagem.
     *
     * @apiError (400 - InvalidJSON) root O json enviado é invalido. Isso pode ocorrer por falta de parametros, erros de tipos e erros de sintaxe.
     *
     * @apiParamExample {json} Exemplo de requisição:
     *   {
     *       "title": "Onibus quebrado",
     *       "message": "O galaticus quebrou e não funcionará essa semana :("
     *   }
     * @apiSuccessExample Exemplo de respota com sucesso:
     *     HTTP/1.1 201 CREATED
     *     {
     *       "title": "Onibus quebrado",
     *       "message": "O galaticus quebrou e não funcionará essa semana :(",
     *       "id_routes": 86,
     *       "id_bus": 1,
     *       "id": 10
     *     }
     */
   /**
     * @api {post} /routes/:idRoute/messages Adicionar uma mensagem a uma rota
     * @apiName PostRouteMessage
     * @apiGroup Messages
     * @apiPermission tracker
     *
     * @apiUse tokenParam
     *
     * @apiParam (URLParam) {Integer} idRoute ID unico da rota que a mensagem pertence.
     * @apiParam (BodyParam) {String} titke Título da mensagem a ser adicionada.
     * @apiParam (BodyParam) {String} [message] Texto da mensagem.
     *
     * @apiSuccess (201 - MessageCreated) {Integer} id ID unico da mensagem.
     * @apiSuccess (201 - MessageCreated) {Integer} idRoute ID unico da rota que a mensagem pertence.
     * @apiSuccess (201 - MessageCreated) {String} title Título da mensagem a ser adicionada.
     * @apiSuccess (201 - MessageCreated) {String} message Texto da mensagem.
     *
     * @apiError (400 - InvalidJSON) root O json enviado é invalido. Isso pode ocorrer por falta de parametros, erros de tipos e erros de sintaxe.
     *
     * @apiParamExample {json} Exemplo de requisição:
     *   {
     *         "title": "Horario de funcionamento",
     *         "message": "Amanhã a rota intracampus não funcionará durante a tarde."
     *   }
     * @apiSuccessExample Exemplo de respota com sucesso:
     *     HTTP/1.1 201 CREATED
     *     {
     *       "title": "Onibus quebrado",
     *       "message": "Amanhã a rota intracampus não funcionará durante a tarde.",
     *       "id_routes": 86,
     *       "id": 10
     *     }
     */
    function addMessage($idRoute, $idBus){
        //check read permissions
        $token = $this->authenticate();
        if(!$token->valid(Authentication::TRACKER_PERMISSION))
            return $this->makeUnauthorizedResponse();

        $validator = $this->validateJson($this->input->raw_input_stream, APPPATH.'/controllers/Schemas/MessageAdd.json');
        if($validator->valid){
            $this->loadModel();
            $notification = json_decode($this->input->raw_input_stream);
            $notification->id_routes = $idRoute;
            $notification->id_bus = empty($idBus) ? NULL : $idBus;

            //check route exist
            $this->load->model('routes_model', '', TRUE);
            if(!$this->routes_model->existRoute($idRoute))
                return $this->makeJsonRespose(["error" => "NOT_FOUND_ROUTE"], 404);

            //get firebase id, to send message
            $listenersFirebaseIds = $this->messages_model->getNotificationRegistration($idRoute);

            //set tokens, to send for users specific
            //debug purpose only
            if(!empty($notification->registration_token_firebase)){
                $listenersFirebaseIds = $notification->registration_token_firebase;
            }
            else{//save notification in database
                $notification->id = $this->messages_model->insert($notification);
                if($notification->id == null)
                    return $this->makeJsonRespose(["error" => "INTERNAL_ERROR"], 500);
            }

            if(empty($idBus))
                unset($notification->id_bus);

            //put date on the firebase message 
            $notification->date = date('Y-m-d H:i:s');

            $this->notify($listenersFirebaseIds, $notification);

            return $this->makeJsonRespose($notification, 201);
        }else
            return $this->makeJsonRespose($validator->errors, 400);
    }
    private function notify($listenersFirebaseIds, $notification){
       FirebaseUtil::sendMessageMulticast($listenersFirebaseIds, $notification);
    }
    /**
     * @api {get} /routes/:idRoute/messages Requisitar mensagens de uma rota
     * @apiName GetRoutesMessages
     * @apiGroup Messages
     * @apiPermission client
     *
     * @apiExample Exemplo de uso:
     *             curl -i http://host/BusTrackerAPI/index.php/routes/86/messages?buses=true
     *
     * @apiUse tokenParam
     *
     * @apiParam (URLParam) {Integer} idRoute ID unico da rota que a mensagem pertence.
     * @apiParam (URLParam) {boolean}  buses Se true, retorna tambem as mensagens dos onibus da rota.
     *                               Caso contrário, retorna apenas as mensagens destinadas a rota.
     *
     * @apiSuccess {Integer} id_messages ID unico da mensagem.
     * @apiSuccess {Integer} id_routes  ID unico da rota que a mensagem pertence.
     * @apiSuccess {String} title Título da mensagem
     * @apiSuccess {String} message Texto da mensagem
     *
     *
     * @apiSuccessExample Resposta sucesso:
     *     HTTP/1.1 200 OK
     *   [
     *     {
     *       "id_messages": 16,
     *       "id_routes": 86,
     *       "title": "Tem titulo, mas, sem mensagem.",
     *       "message": null,
     *       "date": "2016-07-21 22:48:41"
     *     },
     *              ...
     *     {
     *       "id_messages": 92,
     *       "id_routes": 86,
     *       "title": "Horario de funcionamento",
     *       "message": "Amanhã a rota intracampus não funcionará durante a tarde.",
     *       "date": "2016-07-21 22:28:24"
     *     }
     *   ]
     * @apiSuccessExample Resposta sucesso com mensagens dos onibus tambem:
     *     HTTP/1.1 200 OK
     *       [
     *        {//essa é um mensagem de rota, porque id_bus = null
     *           "id_messages": 11,
     *           "id_routes": 86,
     *           "title": "Horario de funcionamento",
     *           "message": "Amanhã a rota intracampus não funcionará durante a tarde.",
     *           "date": "2016-07-21 22:23:11",
     *           "id_bus": null
     *        },
     *              ...
     *        {//essa é um mensagem de um onibus que pertence a rota, porque id_bus != null
     *           "id_messages": 10,
     *           "id_routes": 86,
     *           "title": "Onibus quebrado",
     *           "message": "O galaticus quebrou :(",
     *           "date": "2016-07-21 22:06:17",
     *           "id_bus": 1
     *        }
     *       ]
     */
    /**
     * @api {get} /routes/:idRoute/buses/:idBus/messages/ Requisitar mensagens de um onibus
     * @apiName GetBusMessages
     * @apiGroup Messages
     * @apiPermission client
     *
     * @apiExample Exemplo de uso:
     *             curl -i http://host/BusTrackerAPI/index.php/routes/86/buses/1/messages
     *
     * @apiUse tokenParam
     *
     * @apiParam (URLParam) {Integer} idRoute ID unico da rota que a mensagem pertence.
     * @apiParam (URLParam) {Integer} idBus ID unico do onibus que a mensagem pertence.
     *
     *
     * @apiSuccess {Integer} id_messages ID unico da mensagem.
     * @apiSuccess {Integer} id_routes  ID unico da rota que a mensagem pertence.
     * @apiSuccess {String} title Título da mensagem
     * @apiSuccess {String} message Texto da mensagem
     *
     *
     * @apiSuccessExample Resposta successo:
     *     HTTP/1.1 200 OK
     *   [
     *     {
     *       "id_messages": 10,
     *       "id_routes": 86,
     *       "title": "Onibus quebrado",
     *       "message": "O galaticus quebrou :(",
     *       "date": "2016-07-21 22:06:17",
     *       "id_bus": 1
     *     },
     *         ...
     *   ]
     */
    function getMessages($idRoutes, $idBus){
        $token = $this->authenticate();
        if(!$token->valid(Authentication::CLIENT_PERMISSION))
            return $this->makeUnauthorizedResponse();

        if($this->input->get("buses") == "true"){
          $idBus = 0;
        }

        $this->loadModel();
        $notifications = $this->messages_model->index($idRoutes, $idBus);

        return $this->makeJsonRespose($notifications, 200);
    }
    /**
     * @api {post} /routes/:idRoute/messages/register Registrar um usuário firebase para receber mensagens(Android)
     * @apiName RegisterNotificationMessages
     * @apiGroup Messages
     * @apiPermission client
     *
     * @apiDescription Use esta requisição para registrar um usuario firebase nas notificações de mensagens de uma rota. Após
     * registrado, o usuário receberá mensagens pelo firebase como as mensagens abaixo:
     *
     *<pre><code>
     * {//mensagem de uma rota<br/>
     *   "title": "Onibus",<br/>
     *   "message": "O esse onibus eh bom :)",<br/>
     *   "id_routes": 86,<br/>
     *   "id": 47<br/>
     * }<br/>
     *</code></pre>
     *<pre><code>
     *  {//mensagem de um onibus pq tem id onibus<br/>
     *    "title": "Onibus",<br/>
     *    "message": "O esse onibus eh bom :)",<br/>
     *    "id_routes": 86,<br/>
     *    "id_bus": 1,<br/>
     *    "id": 48<br/>
     *  }
     *</code></pre>
     *
     * @apiUse tokenParam
     *
     * @apiParam (BodyParam) {String} registration_token_firebase Token firebase do usuário. Veja [aqui](https://firebase.google.com/docs/cloud-messaging/android/client#sample-register)
     *            como adquirir em android.
     *
     * @apiError 404  RouteNotFound
     * @apiError 401 InvalidToken
     * @apiError 409 ExistingRegistry
     * @apiError (400 - InvalidJSON) root O json enviado é invalido. Isso pode ocorrer por falta de parametros, erros de tipos e erros de sintaxe.
     *
     *
     * @apiSuccessExample Exemplo de respota com sucesso:
     *     HTTP/1.1 201 OK
     *     {
     *       "registration_token_firebase": "dIqrrKgmq40:APA91bGFbK1eLFjHJrVPxiQpuu_WUOoB6ZLkl8XxkEAbmf1jKrthc9_sZfWEqViVzhoqjYgstKpr4RjCvr4eV30dgJnQJO6YmBqT2jg-ME4q0M5dGPu3Uez1vo3aX0xuzdMgT0epj3tz",
     *       "id_routes": 86,
     *       "email": "santana@email.com"
     *     }
     *
     */
    function registerNotification($idRoute){
        $token = $this->authenticate();
        if(!$token->valid(Authentication::CLIENT_PERMISSION))
            return $this->makeUnauthorizedResponse();

         $validator = $this->validateJson($this->input->raw_input_stream, APPPATH.'/controllers/Schemas/NotificationRegistryAdd.json');
        if($validator->valid){
            $registry = json_decode($this->input->raw_input_stream);

            //check route exist
            $this->load->model('routes_model', '', TRUE);
            if(!$this->routes_model->existRoute($idRoute))
                return $this->makeJsonRespose(["error" => "NOT_FOUND_ROUTE"], 404);

            $user = parent::getUser();
            if($user == null)
                return $this->makeJsonRespose(["error" => "INVALID_TOKEN"], 401);

            $registry->id_routes = $idRoute;
            $registry->email = $user->email;
            $this->loadModel();
            $result = $this->messages_model->insertNotificationRegistration($registry);

            return $this->makeJsonRespose($registry, 201);
        }else
            return $this->makeJsonRespose($validator->errors, 400);
    }
    function loadModel(){
        $this->load->model('messages_model', '', TRUE);
    }
    function validateJson($json, $schemaPath){
       $route = json_decode($json);

       $schema = json_decode(file_get_contents($schemaPath));
       $validator = Jsv4\Validator::validate( $route, $schema);

       return $validator;

    }
    function makeJsonRespose($output, $statusCode){
        return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header($statusCode)
                    ->set_output(json_encode($output, JSON_NUMERIC_CHECK));
    }
}
