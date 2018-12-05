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
		
		
		/**
		 * Установить Критические стили перед тегом </head>
		 *
		 * @param $allCss
		 *
		 * @throws \Exception
		 * @author    Gartes
		 *
		 * @since     3.8
		 * @copyright 04.12.18
		 */
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
		 * @throws \Exception
		 */
		public static function downCss ( $doc , $params  )
		{
			$cssParams = $params->get( 'cssSetting' );
			if ($cssParams->historyOn){
				$cssParams->fileCssRules = self::addHistoryParams( $doc->_styleSheets , $cssParams->fileCssRules );
			}
			
			
			$css = new \Optimize\css\cssOptimize();
			
			
			
			
			// =>['historyOn'=>'BOOL']
			$css->addLazyLoadingCss( $doc->_styleSheets );
			$doc->_styleSheets = [];
		}#END FN
		
		/**
		 * @param $styleSheets
		 * @param $fileCssRules
		 *
		 * @throws \Exception
		 * @author    Gartes
		 *
		 * @since     3.8
		 * @copyright 05.12.18
		 */
		public static function addHistoryParams( $styleSheets , $fileCssRules ){
			
			if (empty ( $fileCssRules ) ) $fileCssRules = array();
			$rules = \starcache\helpers\helper::_prepareRules(  $fileCssRules    );
			
			
			
			
			$ind = count($fileCssRules) ;
			
			
			$Buffer = new \stdClass() ;
			foreach ($styleSheets as $url => $opt ) {
				if ( is_array($rules) && array_key_exists($url, $rules) ) {
					
					
					
					/*echo'<pre>';print_r( $opt );echo'</pre>'.__FILE__.' '.__LINE__;
					echo'<pre>';print_r(  $rules[$url] );echo'</pre>'.__FILE__.' '.__LINE__;*/
					
					
				 /*
					
					$rules[$url]->options =  (array)$rules[$url]->options;
					$styleSheets[$url] = (array)$rules[$url] ;
					*/
					
				}else{
				 
					$data = new \stdClass();
					$data->file = $url ;
					$data->load = 1 ;
					
					$data->options = (object)$opt['options']  ;
					unset($opt['options'] ) ;
					
					$data = (object)array_merge((array)$data, (array)$opt);
					$nameInd = 'fileCssRules'.$ind;
					$Buffer->$nameInd =  $data;
					
					$ind++;
				}#END IF
			}#END FOREACH
			
			$Buffer = (object)array_merge((array)$fileCssRules, (array)$Buffer );
			
			echo'<pre>';print_r( $fileCssRules );echo'</pre>'.__FILE__.' '.__LINE__;
			echo'<pre>';print_r( $Buffer );echo'</pre>'.__FILE__.' '.__LINE__;
			
			
			
		 
			
			
			$param = \Core\extensions\zazExtensions::getParamsPlugin('system' , 'starcache' ) ;
			
			
			$cssSetting = $param->get('cssSetting');
			$cssSetting->fileCssRules = $Buffer ;
			$param->set('cssSetting' , $cssSetting );
			
			
			# Сохранить параметры
			$Plg = new \stdClass();
			$Plg->_name = 'starcache';
			$Plg->_type = 'system';
			$zazExtensions = new \Core\extensions\zazExtensions();
			$PlgId = $zazExtensions->getJoomlaPluginId($Plg) ;
			$zazExtensions->updateExtensionParams( $param->toString() , $PlgId );
			
			
			
			
			
		}#END FN
		
		
	}#END CLASS