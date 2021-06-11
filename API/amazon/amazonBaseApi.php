<?php
require_once(dirname(__FILE__).'/lib/.config.inc.php');


class amazonBaseApi
{

    public $merchant_id;
    public $mws_auth_token;
    public $marketplaces_id; //array

    public function __construct($merchant_id ,$mws_auth_token, $marketplaces_ids = array())
    {
        $this->merchant_id = $merchant_id;
        $this->mws_auth_token = $mws_auth_token;
        $this->marketplaces_ids = $marketplaces_ids;

    }


    //Agrega la clase del CORE correspondiente a cada funcionalidad
    public static function add_class_amazon($subpath, $recursive = true)
    {
        $path = dirname(__FILE__).'/lib/' .$subpath;
     
		$module_dir = scandir($path);
        foreach ($module_dir as $key => $value)
        {
            if(!in_array($value,array(".","..")))
            {
                if (is_dir($path."/".$value))
                {
					if($recursive)
                    	self::add_class_amazon($subpath."/".$value);

                }
                if(file_exists($path."/".$value))
                {
                    if(strpos($value,".php")>0)
					{
                    	include_once $path."/".$value;
							
					}
						
                }

            }
        }
		
    }

}