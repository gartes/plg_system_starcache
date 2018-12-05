<?php
	namespace starcache\helpers;
	/**
	 * Created by PhpStorm.
	 * User: oleg
	 * Date: 04.12.18
	 * Time: 13:24
	 */
	
	
	
	
	defined( '_JEXEC' ) or die( 'Restricted access' );
	include_once JPATH_LIBRARIES . '/zaz/Core/vendor/autoload.php' ;
	
	class admin
	{
		public static function addJS (){
			\Core\js\CoreJs::addBtnYesNo();
			$doc = \JFactory::getDocument();
			
			$doc->addStyleSheet(\JURI::root().'plugins/system/starcache/assets/css/pluginEdit.css');
		}#END FN
		
	}#END CLASS