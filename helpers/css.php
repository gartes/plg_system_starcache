<?php
	
	/**
	 * Created by PhpStorm.
	 * User: oleg
	 * Date: 03.12.18
	 * Time: 4:08
	 */
	
	
	namespace starcache\helpers;
	
	defined( '_JEXEC' ) or die( 'Restricted access' );
	
	include_once JPATH_LIBRARIES . '/zaz/Core/vendor/autoload.php' ;
	
	class css
	{
		
		
		/**
		 * css constructor.
		 */
		public function __construct ()
		{
			
			echo'<pre>';print_r( $this );echo'</pre>'.__FILE__.' '.__LINE__;
			
		}#END FN
		
		
		public static function getCriticalCss(){
			
			if ( !class_exists( 'Optimize\zazOptimize' ) ) require JPATH_LIBRARIES . '/zaz/Optimize/zazOptimize.php';
			$css = new \Optimize\css\cssOptimize();
			$css->getCriticalCss();
		
		}#END FN
		
	}#END CLASS