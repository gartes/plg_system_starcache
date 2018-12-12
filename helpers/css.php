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
			
			$penthouse = $app->input->get('penthouse' , false ) ;
			
			
			echo'<pre>';print_r( $app->input->get('penthouse' , false ) );echo'</pre>'.__FILE__.' '.__LINE__;
			
			
			
			
			#Адрес текущей страницы
			$uri = \JFactory::getURI();
			$url = $uri->toString(/*array('path', 'query', 'fragment')*/);
			$urlMd5 = md5($url) ;
			
			
			$cache_name ='starcache_CriticalCss';
			$cache  = \JFactory :: getCache ( $cache_name ,  '' ) ;
			$cache->setCaching ( true ) ;
			$cache -> setLifeTime ( 100 ) ;
			
			
			// получаем кэш
			$output  =  $cache -> get ( $urlMd5 ) ;
			
			// создаем кэш если пустой
			if (  empty ( $output )  )  {
				if (!$penthouse){
					# Сохранить все чтиле в файле
					$fileCss = self::createFileAllCss( $allCss['file'] , $app );
					if ( !class_exists( 'Optimize\zazOptimize' ) ) require JPATH_LIBRARIES . '/zaz/Optimize/zazOptimize.php';
					$css = new \Optimize\css\cssOptimize();
					# отправить запрос к penthouse
					$res = $css->sendPost( $fileCss , $url.'?penthouse=1' );
					$data = json_decode($res  )  ;
					$output = file_get_contents($data->Url);
					// $cache -> store ( $output ,  $urlMd5 ) ;
				}
				
			}#END IF
			
			//echo'<pre>';print_r( $urlMd5 );echo'</pre>'.__FILE__.' '.__LINE__;
			//echo'<pre>';print_r( $output );echo'</pre>'.__FILE__.' '.__LINE__;
			
			
			
			
			
			
			/*'https://nobd.ml/assets/smska.tk/a77caabfbfb3a71294f3eda661ebf445.css' ;
			
			$fh = fopen($data->Url, "r");
			$datacss = fread($fh, 10000 );
			fclose($fh);*/
			

		 
			
			
			 
			
			
			
			//$CriticalCss = $css->getCriticalCss($bodyHtml , $allCss);
			
			$bodyHtml = str_replace( '</head>' , '<style>'.$output .'</style></head>' , $bodyHtml) ;
			
			$app->setBody($bodyHtml) ;
			
		// 	echo'<pre>';print_r( $CriticalCss );echo'</pre>'.__FILE__.' '.__LINE__;
			
		}#END FN
	
	 
		public static function createFileAllCss ( $allCss , $app ){
			
			
			// echo'<pre>';print_r( $app->input );echo'</pre>'.__FILE__.' '.__LINE__;
			jimport ( 'joomla.filesystem.file' );
			jimport( 'joomla.environment.uri' );
			
			$uri = \JFactory::getURI();
			$url = $uri->toString(array('path', 'query', 'fragment'));
			$file_id  =  md5 ( $url ) ;
			
			$fileName = 'starcache_allCss_'.$file_id.'.css';
			// \JFile::exists('starcache_allCss.css');
		
			\JFile::write(JPATH_BASE.'/cache/starcache_allCss/' . $fileName ,  $allCss ) ;
			
			//echo'<pre>';print_r( \JURI::root(false) );echo'</pre>'.__FILE__.' '.__LINE__;
			
			$fileUrl = \JURI::root() . 'cache/starcache_allCss/' . $fileName ;
			
			return $fileUrl ;
			
			
			
			
			/*
			$cache_name ='starcache_allCss';
			
			
			
			
			
			$cache  = \JFactory :: getCache ( $cache_name ,  '' ) ;
			// устанавливаем состояние кэша
			$cache->setCaching ( true ) ;
			$cache -> setLifeTime ( 100 ) ;
			// идентифицируем наш кэш
			// для дальнейшего получения
			$cacheid  =  md5 ( $cache_name ) ;
			// получаем кэш
			$output  =  $cache -> get ( $cacheid ) ;
			
			// создаем кэш если пустой
			if (  empty ( $output )  )  {
				$output = $allCss ;
				$cache -> store ( $output ,  $cacheid ) ;
			}
			
			return  $output ;*/
			
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
		public static function downCss ( $PLG , $doc   )
		{
			
			
			$cssParams = $PLG->params->get('cssSetting');
			
			if ($cssParams->historyOn){
				$cssParams->fileCssRules = self::addHistoryParams( $doc->_styleSheets , $PLG  );
			}
			
			
			
			$rules = \starcache\helpers\helper::_prepareRules(  $cssParams->fileCssRules    );
			$doc->_styleSheets = \starcache\helpers\helper::_mergeRules(  $doc->_styleSheets , $rules   );
			
			
			/*$PLG->params->set( 'cssSetting' , $doc->_styleSheets ) ;
			
			echo'<pre>';print_r( $PLG->params->get( 'cssSetting' ) );echo'</pre>'.__FILE__.' '.__LINE__;
			echo'<pre>';print_r( $doc->_styleSheets );echo'</pre>'.__FILE__.' '.__LINE__;
			
			*/
			
			$css = new \Optimize\css\cssOptimize();
			
			
			// =>['historyOn'=>'BOOL']
			$css->addLazyLoadingCss( $doc->_styleSheets );
			$doc->_styleSheets = [];
		}#END FN
		
		
		/**
		 * @param $styleSheets
		 * @param $fileCssRules
		 *
		 * @return array
		 * @throws \Exception
		 * @author    Gartes
		 *
		 * @since     3.8
		 * @copyright 05.12.18
		 */
		public static function addHistoryParams( $styleSheets , $PLG ){
			
			$cssParams = $PLG->params->get( 'cssSetting' );
			$fileCssRules = $cssParams->fileCssRules ; 
			
			if (empty ( $fileCssRules ) ) $fileCssRules = array();
			$rules = \starcache\helpers\helper::_prepareRules(  $fileCssRules    );
			
			
			
			$ind = count($fileCssRules) ;
			
			$Buffer = new \stdClass() ;
			foreach ($styleSheets as $url => $opt ) {
				
				
				$copyUrl =  str_replace('?vmver='.VM_JS_VER , '' ,  $url );
				
				
				# Если настройки для файла присутствуют
				if ( is_array($rules) && array_key_exists( $copyUrl , $rules) ) {
					
					
				}else{
				 
					$data = new \stdClass();
					$data->file = $copyUrl ;
					$data->load = 1 ;
					
					$data->options = (object)$opt['options']  ;
					$data->options->detectDebug = (isset($data->options->detectDebug)?$data->options->detectDebug:false) ;
					unset($opt['options'] ) ;
					
					$data = (object)array_merge((array)$data, (array)$opt);
					$nameInd = 'fileCssRules'.$ind;
					$Buffer->$nameInd =  $data;
					
					$ind++;
				}#END IF
				
				
				
			}#END FOREACH
			
			
			
			$Buffer = (object)array_merge((array)$fileCssRules, (array)$Buffer );
			
//			 
			
			/*
			# Получить параметры плагина
			$param = \Core\extensions\zazExtensions::getParamsPlugin('system' , 'starcache' ) ;
			$cssSetting = $param->get('cssSetting');
			*/
			
			
			
			
			$cssParams->fileCssRules = $Buffer ;
			
			
			
			$PLG->params->set('cssSetting' , $cssParams );
			$PLG->params->set('upDateParams' , 1 );
			
			
			
			 /*
			# Сохранить параметры
			$Plg = new \stdClass();
			$Plg->_name = 'starcache';
			$Plg->_type = 'system';
			$zazExtensions = new \Core\extensions\zazExtensions();
			$PlgId = $zazExtensions->getJoomlaPluginId($Plg) ;
			
//
			
			$zazExtensions->updateExtensionParams( $param->toString() , $PlgId );*/
			
		 
			
			return  $Buffer  ;
			
		}#END FN
		
		
	}#END CLASS