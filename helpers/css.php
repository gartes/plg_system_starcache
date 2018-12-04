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
			
		
			
		}#END FN
		
		
		public static function getAllCss(){
			
			if ( !class_exists( 'Optimize\zazOptimize' ) ) require JPATH_LIBRARIES . '/zaz/Optimize/zazOptimize.php';
			$css = new \Optimize\css\cssOptimize();
			return $css->getAllCss();
		
		}#END FN
		
		
		
		public static function getCriticalCss(   $allCss ){
			
			$app = \JFactory::getApplication() ;
			
			
			
			$bodyHtml  = $app->getBody() ;
			
			if ( !class_exists( 'Optimize\zazOptimize' ) ) require JPATH_LIBRARIES . '/zaz/Optimize/zazOptimize.php';
			$css = new \Optimize\css\cssOptimize();
			$CriticalCss = $css->getCriticalCss($bodyHtml , $allCss);
			
			$bodyHtml = str_replace( '</head>' , '<style>'.$CriticalCss['criticalcss'].'</style></head>' , $bodyHtml) ;
			
			$app->setBody($bodyHtml) ;
			
		// 	echo'<pre>';print_r( $CriticalCss );echo'</pre>'.__FILE__.' '.__LINE__;
			
		}#END FN
		
		
		/**
		 * Создание отложенной загрузки для CSS файлов
		 *
		 * @param $doc
		 *
		 * @author    Gartes
		 *
		 * @since     3.8
		 * @copyright 04.12.18
		 */
		public static function downCss ( $doc )
		{
			$css = new \Optimize\css\cssOptimize();
			$css->addLazyLoadingCss( $doc->_styleSheets );
			$doc->_styleSheets = [];
		}#END FN
		
		
	}#END CLASS