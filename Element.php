<?php
/**
 * Zend Framework Customization
 *
 * Represent a variable and within his value., can be a session, cookie or local variable.
 *
 * @author Antoine LUCAS <cooluhuru@gmail.com>
 * @uses       Zend_Registry_Store
 * @category   Zend
 * @package    Zend_Registry
 * @subpackage Store
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    customversionstring0.1
 */

class Zend_Registry_Element {

    private $name;

    private $storageType;

    private $value;



    /**
     *
     * @param string $name
     * @param int $storageType
     */
    public function __construct($name,  $storageType){
        switch($storageType){
            case Zend_Registry_Store::session : 
            case Zend_Registry_Store::cookie : 
            case Zend_Registry_Store::local :
                break;
            default :
                throw new Exception();
                
        }
        $this->name=$name;
        $this->storageType = $storageType;
    }

   
    
    /**
     * 
     * @param type $value
     * @param type $isSerialized
     * @throws Zend_Serializer_Exception
     */
    public function setValue($value, $isSerialized=false) {
        if($isSerialized){
            try{
                $this->value = Zend_Registry_Store::$serializer->unserialize($value);
            }
            catch (Zend_Serializer_Exception $e) {
                throw $e;
            }
        }
        else{
            $this->value=$value;
        }
    }

    /**
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }
    /**
     *
     * @return int
     */
    public function getStorageType() {
        return $this->storageType;
    }

    /**
     * 
     * @return type
     */
    public function &getValue() {
        return $this->value;
    }
    
    public function delete(){
         switch($this->storageType){
            case Zend_Registry_Store::session : 
                unset($_SESSION[Zend_Registry_Store::SESSION_NAME][$this->name]);
                break;
            case Zend_Registry_Store::cookie : 
                setcookie($this->name, "null", time()-3600, "/");
                unset($_COOKIE[$this->name]);
                break;
            case Zend_Registry_Store::local :
                break;
            default :
                throw new LogicException();
           
                
        }
    }

}

?>