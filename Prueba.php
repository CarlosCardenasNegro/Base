<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

//namespace CCN\Base;

/**
 * Description of Prueba
 *
 * @author Developper
 */
class Prueba extends CCN\Base\Base {
    //put your code here
    public $_estado = false;

    protected $_filename;
    protected $_clases;
    protected $_datos;
    
    /*
    protected $_datos = [
        'title' => 'prueba',
        'desc' => 'Registro de Usuario',
        'campos' => [
            "id" =>         ["label" => "id",           "type" => "hidden", "value" => "", "id" => "id"],
            "nombre" =>     ["label" => "Nombre",       "type" => "text",   "value" => "", "id" => "nom",   "required" => "required", "autofocus" => "autofocus"],
            "apellido_1" => ["label" => "1er Apellido", "type" => "text",   "value" => "", "id" => "ape_1", "required" => "required"],
            "apellido_2" => ["label" => "2o Apellido",  "type" => "text",   "value" => "", "id" => "ape_1", "required" => "required"],
            "edad" =>       ["label" => "Edad",         "type" => "number", "value" => "", "id" => "edad",  "required" => "required", "min" => "0", "max" => "130", "maxlength" => "3"],
            "gender" =>     ["label" => "Genero",       "type" => "radio",  "value" => "", "id" => "gender",      "valores" => ["MALE" => "Masculino", "FEMALE" => "Femenino"]],
            "categoria" =>  ["label" => "Categoría",    "type" => "radio",  "value" => "", "id" => "categoria", "valores" => ["ADMIN" => "Administrador", "STANDARD" => "Estándar"]]
        ]        
    ];    
     *
     */

    // methods
    // contructor
    /**
     * 
     * @param type $filename Nombre del archivo .json que contiene la definición
     * @param type $clases Nombre del archivo .json que contiene las clases
     * @param type $param (Opcional) valores iniciales del campo 'value'
     * 
     */
    function __construct ($filename, $clases, ...$param) {
    
        try {
            $this->_filename = $filename;
            $this->_clases = $clases;
            
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }





        try {
            $_stream = fopen($filename, 'r');
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }



        
        $json = fread($_stream, filesize($filename));
        
        if (($salida = json_decode($json, true)) == null) {

            switch (json_last_error()) {
                case JSON_ERROR_DEPTH:
                    echo ' - Maximum stack depth exceeded';
                break;
                case JSON_ERROR_STATE_MISMATCH:
                    echo ' - Underflow or the modes mismatch';
                break;
                case JSON_ERROR_CTRL_CHAR:
                    echo ' - Unexpected control character found';
                break;
                case JSON_ERROR_SYNTAX:
                    echo ' - Syntax error, malformed JSON';
                break;
                case JSON_ERROR_UTF8:
                    echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
                default:
                    echo ' - Unknown error';
                break;
            }    
        }

        
        // 
     // valores por defecto
     $this->_datos['campos']["nombre"]['value'] = "";
     $this->_datos['campos']["apellido_1"]['value'] = "";
     $this->_datos['campos']["apellido_2"]['value'] = "";
     $this->_datos['campos']["edad"]['value'] = "";
     $this->_datos['campos']["gender"]['value'] = "";
     $this->_datos['campos']["categoria"]['value'] = "";

     // valores pasados...
     // si ya se que debería usar un For..next...enterado..
     switch (count($param)) {
         case 0:
             // already set...
             break;
         case 1:
             $this->_datos['campos']["nombre"]['value'] = $param[0];
             break;
         case 2:
             $this->_datos['campos']["nombre"]['value'] = $param[0];
             $this->_datos['campos']["apellido_1"]['value'] = $param[1];
             break;
         case 3:
             $this->_datos['campos']["nombre"]['value'] = $param[0];
             $this->_datos['campos']["apellido_1"]['value'] = $param[1];
             $this->_datos['campos']["apellido_2"]['value'] = $param[2];
             break;
         case 4:
             $this->_datos['campos']["nombre"]['value'] = $param[0];
             $this->_datos['campos']["apellido_1"]['value'] = $param[1];
             $this->_datos['campos']["apellido_2"]['value'] = $param[2];
             $this->_datos['campos']["edad"]['value'] = $param[3];
             break;
         case 5:
             $this->_datos['campos']["nombre"]['value'] = $param[0];
             $this->_datos['campos']["apellido_1"]['value'] = $param[1];
             $this->_datos['campos']["apellido_2"]['value'] = $param[2];
             $this->_datos['campos']["edad"]['value'] = $param[3];
             $this->_datos['campos']["gender"]['value'] = $param[4];
             break;
         case 6:
             $this->_datos['campos']["nombre"]['value'] = $param[0];
             $this->_datos['campos']["apellido_1"]['value'] = $param[1];
             $this->_datos['campos']["apellido_2"]['value'] = $param[2];
             $this->_datos['campos']["edad"]['value'] = $param[3];
             $this->_datos['campos']["gender"]['value'] = $param[4];
             $this->_datos['campos']["categoria"]['value'] = $param[5];
     }
     /*if (func_num_args() == 1) {
       $this->_datos['campos']["intensidad"]['value'] = $param[0];
      }
      * 
      */
      parent::__construct($this->_datos);  
    }

    // getters 
    function __get($propertyName) {
           if ($propertyName === 'estado') {
               return $this->_estado;
           } else {
               return(parent::__get($propertyName));        
           }
       }
    // setters
    function __set($propertyName, $propertyValue) {
           if ($propertyName === 'estado') {            
               $this->_estado = gettype($propertyValue) === 'boolean' ? $propertyValue : false;
           } else {
           return(parent::__set($propertyName, $propertyValue));
           }
       } 

    // tools
    public function toXML() {
     return(parent::toXML());
    }
    public function toDOMNodes() {
     return(parent::toDOMNodes());  
    }
    public function toArray() {
     return(parent::toArray());  
    }
    public function getModos($campo) {
     return(parent::getModos($campo));
    }
    public function toSimpleArray() {
     return(parent::toSimpleArray());  
    }
    /**
     * Estilos opcionales para la sección
     * pasados de forma literal como string
     * 
     * (esto es inválido:...
     * como array asociativo, ej.:
     * {'display' => 'none', 'width' => '80%',...}
     * habitualmente usaremos el display que es el 
     * más útil...)
     */
    public function toHTML($style = null) {
     return(parent::toHTML($style));  
    }
    public function estado() {
        return(parent::estado());     
    }
}
