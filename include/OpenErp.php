<?php
require_once( plugin_dir_path(__FILE__) . "openerp.class.php" );

class OpenErp extends OpenErpManager
{
    /**
    * Getters and Setters
    */
    /**
    * Set / Get the Mail Address
    * this value is not stored in openerp db
    *
    * @param string $_db
    * @return void
    */
    public function get_mail()
    {
          return $this->oe_mail;
    }

    public function set_mail($_mailaddress)
    {
          $this->oe_mail=$_mailaddress;
    }

    /**
    * Set / Get the Server
    *
    * @param string $_db
    * @return void
    */

    public function set_server($_server)
    {
          $this->oe_server = $_server;
    }


    public function get_server()
    {
          return $this->oe_server;
    }

    /**
    * Set / Get the dbname
    *
    * @param string $_db
    * @return void
    */
    public function set_db($_db)
    {
          $this->oe_database = $_db;
    }

    public function get_db()
    {
          return $this->oe_database;
    }

    /**
    * Set the username of an account with higher priviledges
    *
    * @param string $_username
    * @return void
    */
    public function set_username($_username)
    {
          $this->oe_username = $_username;
    }

    /**
    * Set the password of an account with higher priviledges
    *
    * @param string $_password
    * @return void
    */
    public function set_password($_password)
    {
          $this->oe_password = $_password;
    }



    /**
    * Default Constructor
    *
    * Tries to bind to the AD domain over LDAP or LDAPs
    *
    * @param array $options Array of options to pass to the constructor
    * @throws Exception - if unable to bind to Domain Controller
    * @return bool
    */
    function __construct($options=array()){
        // You can specifically overide any of the default configuration options setup above
        if (count($options)>0){
            if (array_key_exists("oe_server",$options)){ $this->oe_server=$options["oe_server"]; }
            if (array_key_exists("oe_database",$options)){ $this->oe_database=$options["oe_database"]; }
            if (array_key_exists("oe_username",$options)){ $this->oe_username=$options["oe_username"]; }
            if (array_key_exists("oe_password",$options)){ $this->oe_password=$options["oe_password"]; }
        }

        //~ return function login($this->oe_password,$this->oe_username,$this->oe_database,$this->oe_server) ;
        return $this;
    }
    public function authenticate($username, $password){
        $ret=-1;
        $ret=$this->login($username,$password,$this->oe_database,$this->oe_server);
        if ($ret>0){
            $pos = strrpos($username,"@");
            if ($pos === false) {
                $_ids=Array($ret);
                $_model='res.users';
                $_fields=Array("partner_id");
                $res = $this->read($_ids, $_fields, $_model);
                $_ids=Array($res[0][$_fields]);
                $_model='res.partner';
                $_fields=Array("email");
                $res = $this->read($_ids, $_fields, $_model);
                $this->oe_mail=$res[0][$_fields];
            }else{
                $this->oe_mail=$username;
            }
            return true;
        }
        else{
            return false;
        }


    }


}

class OeException extends Exception {}

?>
