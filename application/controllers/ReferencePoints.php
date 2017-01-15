<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH.'/libraries/Jsv4/Validator.php';
require APPPATH.'/libraries/Jsv4/ValidationException.php';
include( APPPATH.'controllers/Authentication.php' );

class ReferencePoints extends Authentication {

    function loadModel(){
        $this->load->model('ReferencePoints_model', '', TRUE);
    }
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
