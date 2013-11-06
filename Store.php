<?php
/**
 * Zend Framework Customization
 *
 * This helps to store and retrieve data from cookies, session and local Storages
 *
 * @author      Antoine LUCAS <cooluhuru@gmail.com>
 * @uses        Zend_Registry_Store
 * @category    Zend
 * @package     Zend_Registry
 * @license     http://framework.zend.com/license/new-bsd     New BSD License
 * @version     customversionstring0.1
 * @todo        Implement all configuration options
 * @todo        Add a persistent Layer ( Java style )
 * 
 * const persistent = 4;
 */

require_once "Element.php";

/**
 * This stocks data that are available trough all the site, usually, these data are stocked from an action or component element, and are retrieved by widgets.
 *
 */
class Zend_Registry_Store {

    const session = 1;
    const cookie = 2;
    const local = 3;
    
    const SESSION_NAME = "Registry";
    /**
     *
     * @var Zend_Serializer
     */
    public static $serializer;
    
    
    /**
     *
     * @var Zend_Config
     */
    private static $config;

    /**
     *
     * @var Zend_Registry_Element[]
     */
    private static $data;

    
    
    /**
     *
     * @var Zend_Log
     */
    private static $logger;

    /**
     *
     * @var Zend_Session_Namespace
     */
    private static $sessionNamespace;

    

    
    
    private $defaultConfig = [
        'order' => 'LCS',
        'cookie_expiration' => 3600,
        'session_expiration' => 3600,
        'persistent_expiration' => 3600,
        'cookie_expire_on_close' => false,
        'session_expire_on_close' => false,
        'persistent_expire_on_close' => false,
        'encrypt' => false ,
        'matchIP' => false ,
        'matchUserAgent' => false
    ];

    public function __construct() {
        if(!isset(self::$serializer)){
            self::$serializer = Zend_Serializer::factory('PhpSerialize');
        }
        if(!isset(self::$logger)){
            self::$logger =  new Zend_Log();
        }
        
        if (!isset(self::$config) || self::$config==null) {
            $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
            $options = $bootstrap->getOptions();
            if(!isset($options['registry'])){
                self::$logger->log("No Registry config data. Forgot to configure Registry in application.ini? Loading default config.", Zend_log::WARN);
                self::$config = $this->defaultConfig;
            }
            else{            
                self::$config = array_merge( $this->defaultConfig, $options['resources'] );  
            }
            
        }

        if(!isset(self::$sessionNamespace)){
            self::$sessionNamespace = new Zend_Session_Namespace(self::SESSION_NAME);
        }

        $this->loadOrder();
    }


    /**
     *
     * @param string $name
     * @param object $value
     * @param boolean $isPersistent
     * @param int $persistentMethod 1->session, 2->cookie, 3->local (default)
     * @throws \Exception
     */

    public  function set($name, $value,  $persistentMethod ) {

        switch($persistentMethod){
            case self::session :
            case self::cookie :
                $rData = new Zend_Registry_Element($name, $persistentMethod);
                $rData->setValue($value);  
                self::$data[$name] = $rData;
                break;
            case self::local :    
                $rData = new Zend_Registry_Element($name, self::local);
                $rData->setValue($value);
                self::$data[$name] = $rData;
                break;
            default : 
                throw new Exception("Invalid Persistent Method.");            
        }        
    }

    public function delete($name){
        if(isset(self::$data[$name])){
            self::$data[$name]->delete();
            unset(self::$data[$name]);
        }
        else{
            throw new \Exception("Can't delete a registry data that is not defined. Use registry->isset(name) to check first if the value is defined");
        }
    }
    /**
     *
     * @param string $name
     * @return boolean
     */
    public function __isset($name){
        return $this->has($name);
    }
    /**
     *
     * @param string $name
     * @return boolean
     */
    public function has($name){
        return isset(self::$data[$name]);
    }
    public function &__get($name) {
        if (!$this->has($name)) {
            echo "<pre>";
            debug_print_backtrace();
            echo "</pre>";
            throw new \Exception("$name is not defined");
        }
        return self::$data[$name]->getValue();
    }

    public function write() {

        foreach (self::$data as $rData) {
            switch ($rData->getStorageType()) {
                case self::session :
                    try{
                        self::$sessionNamespace->{$rData->getName()} = self::$serializer->serialize($rData->getValue());
                    }
                    catch (Zend_Serializer_Exception $e) {
                        echo $e;
                    }
                    break;
                case self::cookie :
                    if (headers_sent() === FALSE) {
                        try{
                            setcookie($rData->getName(), self::$serializer->serialize($rData->getValue()), time()+60*60*24*30, "/" );
                            $_COOKIE[$rData->getName()] = self::$serializer->serialize($rData->getValue());
                        }
                        catch (Zend_Serializer_Exception $e) {
                            echo $e;
                        }
                    } else {
                        /**
                         * @todo enable/disable this exception
                         */
                        throw new LogicException("You can't set cookies after header are sent");
                    }
                    break;
                default : 
                    throw new LogicException("Invalid storage Type");
            }
        }
    }

    private function loadOrder() {
        
        $order = self::$config['order'];
        if(strlen($order)!=3){
            throw new \Exception("Invalid length".var_export($order, true));
        }
        $first  = substr($order, 0, 1);
        $second =  substr($order, 1, 1);
        $third =  substr($order, 2, 1);

        $this->load($first);
        $this->load($second);
        $this->load($third);
    }

    private function load($type){
        switch($type){
            case "S" :
                $this->loadSession();
                break;
            case "C" :
                $this->loadCookie();
                break;
            case "L" : 
                $this->loadLocal();
                break;
            default :
                throw new \Exception();
        }
    }


    private function loadSession() {
        foreach (self::$sessionNamespace as $name => $data) {
            if (!isset(self::$data[$name])) {
                $rData = new Zend_Registry_Element($name, self::session);
                $rData->setValue($data, true);
                self::$data[$name] = $rData;
            }
        }
    }

    private function loadCookie() {
        foreach ($_COOKIE as $name => $data) {
            
            if ($name!= session_name() && !isset(self::$data[$name]) && $data!=="null" ) {
                
                $rData = new Zend_Registry_Element($name, self::cookie);
                try{
                    $rData->setValue($data, true);
                }
                catch(Exception $e){
                    echo $e->getMessage();
                    $rData->setValue($data, false);
                }
                self::$data[$name] = $rData;
            }
        }
    }
    
    private function loadLocal(){
        //throw new \Exception("TODO");
    }
    
    public function debug(){
        var_dump(self::$data);
    }

}

?>