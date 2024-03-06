<?php
/**
 * @copyright Copywright (c) San Miguel Software (http://www.sanmiguelsoftware.com)
 * @author    Carlos Cárdenas Negro
 * @version   1.0
 * @link      (http://www.nuimsa.es)
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace CCN\Base;

/**
 * Clase Base.
 * 
 * Creación programada de Forms.
 * 
 * Se puede crear "vacía" y luego añadirle
 * lo necesario que será como mínimo un
 * archivo json conteniendo los campos.
 * 
 * Si se pasa un .json de clases estas se usarán
 * si no la salida será "plain".
 * 
 * También se puede pasar un array asociativo
 * conteniendo los parámetros a rellenar los
 * campos 'value' de cada Field ($param)
 * 
 * Además de todo la clase puede contener un \PDO
 * para acceso a la  base de datos en la que
 * podríamos guardar los datos recogidos del
 * Form...
 *
 */  
class Base {
    //properties

    /**
     * PDO
     * 
     * Para almacenar los datos recogidos en el FORM
     * 
     * @var string
     */
    private $pdo;

    /**
     * Definición de todos los Campos del Form.
     * 
     * Se leen a partir de un archivo .json
     * decodificado como array.
     * 
     * @access protected
     * @var array 
     */
    protected $_datos = [];

    /**
     * 
     * @access private
     * @var string nombre del archivo .json que contiene la definición del Form
     */
    private $_datosFileName = null;

    /**
     * Definición de las clases que definen el estilo de cada Campo.
     * 
     * Se leen desde un archivo .json
     * decodificado como array ($var[key]=>value).
     * 
     * @access private
     * @var array
     */
    private $_clases = [];
    private $_clasesFileName = null;
    
    //methods

    //constructor
    /**
     * (Nota: todos los parámetros son opcionales pero
     * interdependientes unos de otros obviamente).
     * 
     * @param \PDO $pdo para guardar los datos recogidos
     * @param string $datosFN nombre del archivo de definición del Form
     * @param string $clasesFN nombre del archivo de clases
     * @param string $param array de parámetros pasados.
     *      El parámetro ($param[0]) será un array ASOCIATIVO¡,
     *      con valores para inicializar la clase (los que queramos).
     * 
     *  Ejemplo: $param[0] = ['id' => 34554, 'nombre' => 'Carlos','apellido_2' => 'Negro']
     * 
     */
    function __construct (\PDO $pdo = null, $datosFN = null, $clasesFN = null, ...$param) {

                
        if(isset($pdo)) {
          // acceso a base de datos
          $this->pdo = $pdo;
        }
        
        if (isset($datosFN)) {
            // set property
            $this->_datosFileName = $datosFN;
            // Recupero definición de Fields
            $myfile = fopen($datosFN, 'r') or die('No puedo abrir el archivo de definición de Fields.');
            $this->_datos = json_decode(fread($myfile, filesize($datosFN)), true);
            unset($myfile);
        }
        
        if (isset($clasesFN)) {
            // set property
            $this->_clasesFileName = $clasesFN;
            
            // Recupero clases
            $myfile = fopen($clasesFN, 'r') or die('No puedo abrir el archivo de Clases.');
            $this->_clases = json_decode(fread($myfile, filesize($clasesFN)), true);
            unset($myfile);
        }
        
        /**
         * He decidido que podemos crear la clase vacía
         * o con valores de inicio pasados en $param
         */
        if (count($param) <> 0) {
            // el primer $param[0] contendrá un PDO (ya inicializado)
            
            foreach ($this->_datos['campos'] as $key => $value) {
                if (array_key_exists($key, $param[0])) {
                    $this->_datos['campos'][$key]['value'] = $param[0][$key];
                }
            }
        }        
    }
 
    // getters
    public function __get($propertyName) {
     if (array_key_exists($propertyName, $this->_datos)) {
      return $this->_datos[$propertyName];
     } else {
      // puede que hayamos recibido un campo en cuyo caso devolvemos el ARRAY
      // de todas sus propiedades ej.: $test->iniciales.
      // Si queremos recuperar una propiedad específica debemos usar la variedad
      // $test->iniciales['type'], test->iniciales['value'], etc.
      //
      if (array_key_exists($propertyName, $this->_datos['campos'])) {
       return $this->_datos['campos'][$propertyName];
      } else {
          // no estamos buscando un valor de _datos sino
          // alguna otra de las propiedades... pero 
          // debo saber si existe
          $rClass = new \ReflectionClass($this);
          $rClassProperties = $rClass->getProperties();
          //
          // debo confirmar que el propertyName 
          // pasado existe dentro de la Clase...
          // 
          $found = false;
          foreach ($rClassProperties as $value) {
              if ($value->name === $propertyName) {
                  $found = true;
              }
          }
          // limpio un poco...
          unset ($rClass);
          
          if ($found) {
            return $this->$propertyName;
          } else {
              return null; 
          }
        }
     } 
    }

    //setters
    public function __set($propertyName, $propertyValue) {
     if (array_key_exists($propertyName, $this->_datos)) {
      $this->_campos[$propertyName] = $propertyValue;
     } else {
      // puede que hayamos recibido un campo pero,
      //  SOLO, SOLO, SOLO... podemos modificar el 'value'
      // ej.: $test->iniciales = "RGF"   
      if (array_key_exists($propertyName, $this->_datos['campos'])) {
          // hay dos posibilidades la más habitual es pasar
          // solo el campo 'value',... pero también podría
          // querer cambiar por completo su contenido
          // en cuyo caso paso un array con todas sus
          // propiedades...que sustituirá al actual...
          // esto es poco probable pero....
          // ESTO ES UNA IDEA... para crear una clase
          // TEMPLATE que podría rellenar completamente
          // partiendo de 0...
          // No le podría añadir nuevos campos pero
          // si podría modificarlos completos
          // obviamente esto solo valdría la pena
          // para modificar el campo _datos
          // que es el que maneja el HTML...
          // 
          if(is_array($propertyValue)) {
              $this->_datos['campos'][$propertyName] = $propertyValue;
          }else{
              // cambio solo 'value'...
              $this->_datos['campos'][$propertyName]['value'] = $propertyValue;    
          }
      } else {
          // quizás haya pasado TODO(AS) los campos de la variable
          // _datos... porque quiero crear una nueva "clase" completa
          // que estoy usando como Template... es algo extraño pero
          // podría hacerse... veamos pues...
          // debo crear una ReflectionClass de si misma....
          
          //
          //  he pansado que lo anterior es un poco bobo
          // lo mejor es pasar un nuevo nombre de archivo
          // con la definición y leerlo.
          // O sea que modifico esto...
          $rClass = new \ReflectionClass($this);
          $rClassProperties = $rClass->getProperties();
          //
          // debo confirmar que el propertyName 
          // pasado existe dentro de la Clase...
          // 
          foreach ($rClassProperties as $value) {
              if ($value->name === $propertyName) {
                  //$this->_datos = $propertyValue;
                  $this->$propertyName = $propertyValue;
              }
          }
          // limpio un poco...
          unset ($rClass);
      }
     } 
    }

    /**
     * Un conjunto de funciones para
     * devolver el array en diversas
     * formas además de la primaria
     * que sería HTML...
     */ 
    // to XML
    public function toXML() {
     $name = $this->_datos['title'];
     $salida = '<?xml version="1.0" encoding="UTF-8" ?>';
     $salida .= "<$name>";
     foreach ($this->_datos['campos'] as $key => $value) {
      //nota: solo proceso los 5 primeros elementos
      $valor = $value['value'];
      $salida .= "<$key>$valor</$key>";
     }
     $salida .= "</$name>";
     return $salida;
    }
    // to DOMNode
    public function toDOMNodes() {
     $_doc = new \DOMDocument('1.0', 'UTF-8');
     $_doc->loadXML($this->toXML());
     return $_doc->documentElement;
     unset($_doc);
    }
    // to associative array
    public function toArray() {
     $resul = array();
     foreach ($this->_datos['campos'] as $key => $value) {
      $resul[$key] = $value['value'];
     }
     return $resul;
    }
    // to numeral -simple- array
    public function toSimpleArray() {
     foreach ($this->_datos['campos'] as $key => $value) {
      $resul[] = $value['value'];
     }
     return $resul;
    }
    
    /**
     * salida HTML
     * 
     * En esta version voy a pasar la salida
     * a una variable que luego devuelvo...
     * 
     * @param object $param opcional
     */
    public function toHTML( ...$param ) { 
     
        $resul = "";

        // (1) Div (id=title)
        $resul = '<div id=' . $this->comas($this->title) . 
             ' class= ' . $this->comas($this->_clases["div"]);

        /**
         * $param se va a pasar como objeto, ej.:
         * {'display' => 'none', 'width' => '80%',...}
         * Este style afectará a todo el Form.
         * Habitualmente lo usaremos para el display que es el 
         * display:none o block, ...,... visibility: hidden....
         */
        if (count($param) <> 0) {
            $style = $param[0];
            if (isset($style)) {
                $resul .= ' style= "';
                foreach ($style as $key => $value) {
                        $resul .= $key . ':' . $value . '; ';
                }
                $resul .= '">';
            } else {
                $resul .= '>';
            }
        } else {
            $resul .= '>';
        }

        // (2) Cabecera...
        $resul .= '<header class=' . $this->comas($this->_clases["header"]) . '>';
        $resul .= '<h3 class=' . $this->comas($this->_clases["h3"]) . '>' . $this->description . '</h3>';
        $resul .= '</header>';
        
        // (3) Campos...
        $resul .= '<section class=' . $this->comas($this->_clases["section"]) . ' style="display:block">';    

        foreach ($this->campos as $desc => $valor) {
            
            // la etiqueta...
            // debug
            //$deb = $valor['label'];
            //$typ = $valor['type'];
            //debug
            
            $label = '<label class=' . $this->comas($this->_clases["label"]) . '>' . $valor['label'] . '</label><br/>';

            // la clase...
            //$cl = $this->_clases[$valor["type"]];

            // el campo... 
            switch ($valor['type']) {
            case 'text':
                $campo =
                '<input  id='   . $this->comas($valor['id']) .
                ' class='       . $this->comas($this->_clases[$valor["type"]]) .
                ' type='        . $this->comas($valor["type"]) .
                ' name='        . $this->comas($desc) .
                ' value='       . $this->comas($valor["value"]);
                // opciones específicas
                // 
                // opciones comunes
                if (isset($valor['size'])) {
                    $campo .= ' size=' . $this->comas($valor['size']);
                }
                if(isset($valor['required'])) {
                    $campo .= ' required=' . $this->comas($valor['required']);                
                }            
                if(isset($valor['disabled'])) {
                    $campo .= ' disabled=' . $this->comas($valor['disabled']);                
                }            
                if(isset($valor['autofocus'])) {
                    $campo .= ' autofocus=' . $this->comas($valor['autofocus']);                
                }
                if(isset($valor['placeholder'])) {
                    $campo .= ' placeholder=' . $this->comas($valor['placeholder']);                
                }
                if(isset($valor['readonly'])) {
                    $campo .= ' readonly' ;
                }                                            
                if(isset($valor['style'])) {
                    //$campo .= ' autofocus=' . $this->comas($valor['autofocus']);                
                    $campo .= ' style=' . $this->comas($valor['style']);                
                }
                $campo .= ' /><p/>';
                break;

            case 'email':
                $campo =
                '<input  id='   . $this->comas($valor['id']) .
                ' class='       . $this->comas($this->_clases[$valor["type"]]) .
                ' type='        . $this->comas($valor["type"]) .
                ' name='        . $this->comas($desc) .
                ' value='       . $this->comas($valor["value"]);
                // opciones específicas
                // 
                // opciones comunes
                // valores opcionales
                if(isset($valor['readonly'])) {
                    $campo .= ' readonly' ;
                }                            
                if(isset($valor['required'])) {
                    $campo .= ' required=' . $this->comas($valor['required']);                
                }            
                if(isset($valor['disabled'])) {
                    $campo .= ' disabled=' . $this->comas($valor['disabled']);                
                }            
                if(isset($valor['autofocus'])) {
                    $campo .= ' autofocus=' . $this->comas($valor['autofocus']);                
                }
                if(isset($valor['placeholder'])) {
                    $campo .= ' placeholder=' . $this->comas($valor['placeholder']);                
                }
                if(isset($valor['style'])) {
                    //$campo .= ' autofocus=' . $this->comas($valor['autofocus']);                
                    $campo .= ' style=' . $this->comas($valor['style']);                
                }
                $campo .= ' /><p/>';
                break;

            case 'password':
                $campo =
                '<input  id='   . $this->comas($valor['id']) .
                ' class='       . $this->comas($this->_clases[$valor["type"]]) .
                ' type='        . $this->comas($valor["type"]) .
                ' name='        . $this->comas($desc) .
                ' value='       . $this->comas($valor["value"]);
                // valores opcionales
                if(isset($valor['required'])) {
                    $campo .= ' required=' . $this->comas($valor['required']);                
                }            
                if(isset($valor['disabled'])) {
                    $campo .= ' disabled=' . $this->comas($valor['disabled']);                
                }            
                if(isset($valor['autofocus'])) {
                    $campo .= ' autofocus=' . $this->comas($valor['autofocus']);                
                }
                if(isset($valor['placeholder'])) {
                    $campo .= ' placeholder=' . $this->comas($valor['placeholder']);                
                }
                if(isset($valor['style'])) {
                    //$campo .= ' autofocus=' . $this->comas($valor['autofocus']);                
                    $campo .= ' style=' . $this->comas($valor['style']);                
                }
                $campo .= ' /><p/>';
                break;

            case 'number':
                //
                // un numero puede tener...
                // max
                // maxlenght
                // min
                // pattern (for validation regex)
                $campo =
                '<input  id='   . $this->comas($valor['id']) .
                ' class='       . $this->comas($this->_clases[$valor["type"]]) .
                ' type='        . $this->comas($valor["type"]) .
                ' name='        . $this->comas($desc) .
                ' value='       . $this->comas($valor["value"]);
                // valores opcionales
                if(isset($valor['required'])) {
                    $campo .= ' required=' . $this->comas($valor['required']);                
                }            
                if(isset($valor['disabled'])) {
                    $campo .= ' disabled=' . $this->comas($valor['disabled']);                
                }            
                if(isset($valor['autofocus'])) {
                    $campo .= ' autofocus=' . $this->comas($valor['autofocus']);                
                }
                if(isset($valor['placeholder'])) {
                    $campo .= ' placeholder=' . $this->comas($valor['placeholder']);                
                }
                if(isset($valor['max'])) {
                                $campo .= ' max=' . $this->comas($valor['max']);
                }
                if(isset($valor['min'])) {
                                $campo .= ' min=' . $this->comas($valor['min']);
                }
                if(isset($valor['maxlength'])) {
                                $campo .= ' maxlength=' . $this->comas($valor['maxlength']);
                }
                if(isset($valor['pattern'])) {
                                $campo .= ' pattern=' . $this->comas($valor['pattern']);
                }
                if(isset($valor['style'])) {
                    //$campo .= ' autofocus=' . $this->comas($valor['autofocus']);                
                    $campo .= ' style=' . $this->comas($valor['style']);                
                }
                 $campo .= ' /><p/>';
             break;

             case 'date':
                if ($valor['type'] == 'date') { $valor['value'] = $this->convierteFecha($valor['value'], 'ISO'); }
                $campo = 
                '<input  id='   . $this->comas($valor['id']) .
                ' class='       . $this->comas($this->_clases[$valor["type"]]) .
                ' type='        . $this->comas($valor["type"]) .
                ' name='        . $this->comas($desc) .
                ' value='       . $this->comas($valor["value"]);
                // valores opcionales
                if(isset($valor['required'])) {
                    $campo .= ' required=' . $this->comas($valor['required']);                
                }            
                if(isset($valor['disabled'])) {
                    $campo .= ' disabled=' . $this->comas($valor['disabled']);                
                }            
                if(isset($valor['autofocus'])) {
                    $campo .= ' autofocus=' . $this->comas($valor['autofocus']);                
                }
                $campo .= ' /><p/>';
                break;

            case 'textarea':
                $campo = 
                '<textarea  id='   . $this->comas($valor['id']) .
                ' class='       . $this->comas($this->_clases[$valor["type"]]) .
                ' name='        . $this->comas($desc) .
                ' value='       . $this->comas($valor["value"]);
                // valores opcionales
                if(isset($valor['required'])) {
                    $campo .= ' required=' . $this->comas($valor['required']);
                }
                if(isset($valor['disabled'])) {
                    $campo .= ' disabled=' . $this->comas($valor['disabled']);
                }
                if(isset($valor['autofocus'])) {
                    $campo .= ' autofocus=' . $this->comas($valor['autofocus']);
                }            
                if(isset($valor['rows'])) {
                    $campo .= ' rows=' . $this->comas($valor['rows']);
                }
                if(isset($valor['cols'])) {
                    $campo .= ' cols=' . $this->comas($valor['cols']);
                }
                if(isset($valor['placeholder'])) {
                    $campo .= ' placeholder=' . $this->comas($valor['placeholder']);                
                }
                if(isset($valor['wrap'])) {
                    $campo .= ' wrap=' . $this->comas($valor['wrap']);
                }
                $campo .=  ' >' . $valor["value"] . '</textarea><p/>';
                break;

            case 'select':
                $campo  =
                '<select id='   . $this->comas($valor['id']) .
                ' class='       . $this->comas($this->_clases[$valor["type"]]) .
                ' name='        . $this->comas($desc);
                // opciones específicas de este tipo de input
                if(isset($valor['multiple'])) {
                    $campo .= ' multiple';
                }
                // opciones comunes
                if (isset($valor['size'])) {
                    $campo .= ' size=' . $this->comas($valor['size']);
                }
                if(isset($valor['required'])) {
                    $campo .= ' required';
                }            
                if(isset($valor['disabled'])) {
                    $campo .= ' disabled';
                }            
                if(isset($valor['autofocus'])) {
                    $campo .= ' autofocus';
                }
                if(isset($valor['placeholder'])) {
                    $campo .= ' placeholder=' . $this->comas($valor['placeholder']);                
                }
                if(isset($valor['style'])) {
                    $campo .= ' style=' . $this->comas($valor['style']);
                }
                
                $campo .= '>';

                //'<option value= "none">none</option>';

                foreach ($valor['valores'] as $key => $value) {
                    $match = false;
                    if (is_array($value)) {
                        // $value contiene todo lo necesario...

                        //OJO, $valor['value'] puede también ser un array
                        // como ocurre en 'tags' al ser un select multiple..!!
                        if ((array)$valor['value'] === $valor['value']) {
                            // debo comparar cada valor de $value['id']
                            // con los elementos del array
                            if (in_array($value['id'], $valor['value'])) {
                                $match = true;                            
                            }
                        } else {
                            // comparación normal contra un solo value
                            if ($value['id'] == $valor['value']) {
                                $match = true;
                            }                        
                        }

                        if ($match) {
                            $campo .= '<option value= ' . $this->comas($value['id']);
                            if(isset($value['data-SAP'])) {
                                $campo .= ' data-SAP= ' . $this->comas($value["SAPid"]);
                            }
                            $campo .= ' selected="selected">' . $value['description'] . '</option>';
                        } else {
                            $campo .= '<option value= ' . $this->comas($value['id']);
                            if(isset($value['data-SAP'])) {
                                $campo .= ' data-SAP= ' . $this->comas($value["SAPid"]);
                            }
                            $campo .= '>' . $value['description'] . '</option>';          
                        }
                    } else {
                        // es un array indexado
                        // comparo $key
                        //OJO, $valor['value'] puede también ser un array
                        // como ocurre en 'tags' al ser un select multiple..!!
                        if ((array)$valor['value'] === $valor['value']) {
                            $total = implode(',', $valor['value']);                        
                        } else {
                            $total = $valor['value'];
                        }

                        if (strpos($total, (string)$key)) {
                            $campo .=
                             '<option value= ' . $this->comas($key) .
                             ' selected>' . $value . '</option>';
                        } else {
                            $campo .=
                             '<option value= ' . $this->comas($key) .
                             '>' . $value . '</option>';                    
                        }
                    }
                }
                $campo .= '</select><p/>';    
                break; 

            case 'radio':
                $cont = -1;
                $campo = '';
                // primero el envoltorio
                $campo .= 
                '<div id=' . $this->comas($valor['label']) .
                ' class='  . $this->comas($this->_clases["groupbox"]);
                // luego formato del encabezado
                $campo .= ' style="position:relative; margin-top: 4%">';
                // luego el texto del encabezado
                $campo .=  '<p class="w3-card w3-theme-d5 w3-text-theme" ' .
                        ' style="position: absolute; margin:-4px; top: -14px; border-radius: 3px 3px 0px 0px; padding: 1px 12px">' . $valor['label'] . '</p>';

                foreach ($valor['valores'] as $rad_key => $rad_valor) {
                    // creo el id
                    $id = $this->getID($rad_key);    
                    if (!empty($valor['value']) && $rad_key == $valor['value']) {
                        $campo .=
                        '<input id='    . $this->comas($id) .
                        ' class='       . $this->comas($this->_clases[$valor["type"]]) .
                        ' type='        . $this->comas($valor['type']) .
                        ' name='        . $this->comas($desc) .
                        ' value='       . $this->comas($rad_key) .
                        ' onchange="radioChange($(this));" ' .
                        ' checked="checked" >';
                    } else {
                        $campo .=
                        '<input id='    . $this->comas($id) .
                        ' class='       . $this->comas($this->_clases[$valor["type"]]) .
                        ' type='        . $this->comas($valor['type']) .
                        ' name='        . $this->comas($desc) .
                        ' value='       . $this->comas($rad_key) . '/>';
                        //' onchange="radioChange($(this));" >';  
                    }
                    $campo .=
                    '<label class='     . $this->comas($this->_clases["radiolabel"]) .
                    '>' . $rad_valor . '</label><br/>';
                }
                $campo .= '</div>';
                break;

            case 'checkbox':
                $checked = isset($valor['checked']) ? true : false;
                if ($checked) {
                    $campo =
                    '<input id='    . $this->comas($valor['id']) .
                    ' class='       . $this->comas($this->_clases[$valor["type"]]) .
                    ' type='        . $this->comas($valor['type']) .
                    ' name='        . $this->comas($desc) .
                    ' value='       . $this->comas($valor['value']) .
                    ' onchange="checkboxChange ($(this));" ' .    
                    ' checked >';
                } else {
                    $campo = 
                    '<input id='    . $this->comas($valor['id']) .
                    ' class='       . $this->comas($this->_clases[$valor["type"]]) .
                    ' type='        . $this->comas($valor['type']) .
                    ' name='        . $this->comas($desc) .
                    ' value='       . $this->comas($valor['value']) .
                    ' onchange="checkboxChange ($(this));" >';    
                }
                $campo .= 
                '<label class='     . $this->comas($this->_clases["checklabel"]) .
                '>' . $valor ['label']. '</label><br/>';
                break;

             case 'hidden':
                if (!empty($valor['value'])) {
                    $campo =
                    '<input id='    . $this->comas($valor['id']) .
                    ' type='        . $this->comas($valor['type']) .
                    ' name='        . $this->comas($desc) .
                    ' value='       . $this->comas($valor['value']) .'/>';

                    /**
                     * caso especial de nom_arc_hid
                     */
                    if ($desc === 'nombre_archivo_hidden') {
                        $lista = explode(';', $valor['value']);
                        echo '<br/><table class="w3-table-all w3-margin-top" style="width: 60%">';
                        echo '<tr><th class="w3-theme-d2">Documentos ya subidos:</th></tr>';
                        for ($i = 0; $i < count($lista); $i++) {
                            echo '<tr><td>';
                            echo '<a href="' . strtolower($lista[$i]) . ' " target="_blank">' . $lista[$i] . '</a></td></tr>';
                        }
                        echo '</table><br/>';                    
                    }

                } else {
                    // relleno los campos hidden con la hora y, por defecto, el SAP code para la Ósea...
                    $campo =
                    '<input id='    . $this->comas($valor['id']) .
                    ' type='        . $this->comas($valor['type']) .
                    ' name='        . $this->comas($desc) .
                    ' value='       . $this->comas($valor['value']) .'/>';
                }
                break;

             case 'file':

                //$label = '<label class=' . $this->comas($cl) . '>' . $valor ['label'] . '</label><br/>';

                $campo =
                '<input id='    . $this->comas($valor['id']) .
                ' class='       . $this->comas($this->_clases[$valor["type"]]) .
                ' type='        . $this->comas($valor['type']);
                // para el caso clínico se pueden seleccionar
                // varias imágenes... el name será un array..¡¡
                if ($valor['id'] === 'img') {
                    $campo .= ' name='  . $this->comas($desc . '[]');
                    $campo .= ' value=' . $this->comas($valor['value']) .  ' multiple >';                
                } else {
                    $campo .= ' name='  . $this->comas($desc);
                    $campo .= ' value=' . $this->comas($valor['value']) .  '>';
                }
                break;            
            }

            // salida
            if ($valor['type'] == 'hidden' || $valor['type'] == 'radio' || $valor['type'] == 'checkbox') {
                 $resul = $resul . $campo; }
            if ($valor['type'] == 'text' || $valor['type'] == 'password' || $valor['type'] == 'email' || $valor['type'] == 'textarea' || $valor['type'] == 'select' || $valor['type'] == 'date' || $valor['type'] == 'file' || $valor['type'] == "number") {
                 $resul = $resul . $label . $campo; }
            if ($valor['type'] == 'etiqueta') { $resul = $resul . $label; }
         }
         // cierra el segmento
         $resul .= '</section>';
         $resul .= '</div>';

         return $resul;

     } 

       /**
        * Helper functions
        */

       /**
        * Añade comillas antes de los campos para
        * la salida HTML
        *
        * @param $var string string a entrecomillar
        * @return string entrecomillada
        */
       private function comas($var) {
           return '"' . $var . '"';
       }

       /**
        * Convierte entre fecha Local (dd/mm/yyyy) e ISO-Chrome (yyyy-mm-dd)
        * 
        * @param string $fecha Fecha a convertir
        * @param string $modo  Modo de conversión ('local', 'ISO')
        */
       private function convierteFecha ($fecha, $modo) {
           if (empty($fecha)) { return ''; }

           if (strtolower($modo) === 'local') {
               if ($temp = date_create_from_format('d/m/Y', $fecha)) {
                   // la fecha está en modo local, la devuelvo intacta
                   return $fecha;
               } else {
                   // se paso en modo Chrome, la convierto a local
                   $temp = date_create_from_format('Y-m-d', $fecha); 
                   return $temp->format('d/m/Y');
               }
           } else {
               if ($temp = date_create_from_format('Y-m-d', $fecha)) {
                   // la fecha está en modo ISO, la devuelvo intacta
                   return $fecha;
               } else {
                   // se paso en modo local, la convierto a ISO
                   $temp = date_create_from_format('d/m/Y', $fecha);
                   return $temp->format('Y-m-d');
               }
           }
       }
       /**
        * Formatea un id a partir de la string
        * pasada que vendrá con underscore '_'
        * Se usan los tres primeros caracteres 
        * de cada palabra.
        *
        * @param $valor string
        * @return $temp la string preparada
        */     
       private function getID($valor) {         
           $parts = explode('_', $valor);    
           $temp = '';
           foreach ($parts as $value) {
               $temp .= substr($value, 0, 3) . '_';
           }
           // antes de salir quito el último _
           return substr($temp, 0, strlen($temp)-1);
        }
       /**
        * Establece el $_estado en función de 
        * si la clase tiene datos o no.
        * Devuelve el resultado como:
        * - true ... tiene datos ('llena')
        * - false .. no tiene datos ('vacía')
        */
       public function estado() {         
           foreach ($this->_datos['campos'] as $key => $value) {
               // ojo algunas clases tienen valores por defecto
               // y devolverán siempre true,... por ahora lo 
               // dejo así...
               // demograficos siempre devolverá true...¡¡¡
               if (!empty($value['value'])) { 
                   $this->_estado = true;
                   return true;                 
               }
           }
           // si llegamos aqui es que no hay ningun valor            
           $this->_estado = false;
           return false;
       }

       /**
        * Recupero los valores desde una tabla...¡¡
        * no me parece correcto que una clase dependa
        * de una tabla externa,.. pero...???
        * de forma experimental me voy a meter
        * en el ajo este...
        *
        * He añadido la posibilidad o necesidad de pasar el 'id'
        * pues por lo general el 'id' se pasará al campo 'value'
        * y la 'description' al campo 'valores', ya que, las id no
        * tienen porque ser correlativas pues si borramos una no se recupera
        * su id y se saltan valores,...
        * ATENCION: voy a ordenar por el PRIMER CAMPO PASADO SERÁ EL id gral. ...¡¡
        *
        * @param string $tabla Nombre de la Tabla donde se encuentran los valores
        * @param array $campos Nombre de(los) campo(s) a recuperar
        */
       protected function getValores($tabla, $campos) {

           $result = array(); 
           $campos_s = implode(',', $campos);
           $sql = "SELECT $campos_s FROM $tabla ORDER BY " . $campos[0];

           /**
            * Set parameters according to Host (local o web server)
            */
           if ($_SERVER['HTTP_HOST'] === 'localhost') {
               $servername = 'localhost';
           } else {
               $servername = 'mysql.hostinger.es';
           }
           $username = 'u525741712_quiz';
           $password = 'XpUPQEthoAcKK5Y30b';    

           try {
               $conn = new \PDO("mysql:host=$servername;dbname=u525741712_quiz", $username, $password);
               // set the PDO error mode to exception
               $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

               $cont = 0;
               foreach ($conn->query($sql, \PDO::FETCH_ASSOC) as $row) {
                   for ($i = 0; $i < count($campos); $i++) {
                       $result[$cont][$campos[$i]] = $row[$campos[$i]];
                   }
                   $cont += 1;
               }
               return $result;
               $conn = null;        
           }
           catch(\PDOException $e) {
               return "Connection failed: " . $e->getMessage();
           }        
       }
    
    /**
     * 
     * readDatos
     * 
     * Lee la definición del Form desde un archivo
     * .json y almacena el resultado en $this->_datos.
     * Esto es por si quiero cambiar de definición
     * on line.
     * 
     * Primero habre de haber set la propertie _datosFileName
     * al nuevo nombre requerido.
     * 
     */
     public function readDatos() {
        if (isset($this->_datosFileName)) {
            // Recupero definición de Fields
            $myfile = fopen($this->_datosFileName, 'r') or die('No puedo abrir el archivo de definición de Fields.');
            $this->_datos = json_decode(fread($myfile, filesize($this->_datosFileName)), true);
            unset($myfile);
        }
    }

    /**
     * 
     * readClases (NOTA: lo duplico por gandulismo)
     * 
     * Lee la definición de Clases desde un archivo
     * .json y almacena el resultado en $this->_clases.
     * Esto es por si quiero cambiar de definición
     * on line.
     * 
     * Primero habre de haber set la propertie _clasesFileName
     * al nuevo nombre requerido.
     * 
     */
     public function readClases() {
        if (isset($this->_clasesFileName)) {
            // Recupero definición de Fields
            $myfile = fopen($this->_clasesFileName, 'r') or die('No puedo abrir el archivo de definición de Fields.');
            $this->_clases = json_decode(fread($myfile, filesize($this->_clasesFileName)), true);
            unset($myfile);
        }
    }
}
