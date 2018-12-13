<?php
	
	/**
	 * Created by PhpStorm.
	 * User: oleg
	 * Date: 03.12.18
	 * Time: 4:08
	 */
	
	
	namespace starcache\helpers;
	
	use JFactory;
	use JFile;
	use JFolder;
	
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
		public static function getCriticalCss( $PLG ,    $allCss ){
			
			jimport( 'joomla.filesystem.folder' );
			jimport ( 'joomla.filesystem.file' );
			
			$app = JFactory::getApplication() ;
			$bodyHtml  = $app->getBody() ;
			$criticalCssSetting =  $PLG->params->get('criticalCssSetting') ;
			 
			// echo'<pre>';print_r( $criticalCssSetting->beforeAddCriticalCss );echo'</pre>'.__FILE__.' '.__LINE__;
			
			$penthouse = $app->input->get('penthouse' , false ) ;
			
			#Адрес текущей страницы
			$uri = JFactory::getURI();
			$url = $uri->toString(/*array('path', 'query', 'fragment')*/);
			$urlMd5 = md5($url) ;
			
			
			$cache_name ='starcache_CriticalCss';
			$cache  = JFactory :: getCache ( $cache_name ,  '' ) ;
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
					$Arrdata = [
						'task' => 'getCtiticalCss' ,
						'cssUrl'  => $fileCss,
						'urlSite' => $url . '?penthouse=1',
					];
					
					$_Err = false ;
					try {
						$data = $css->sendPost( $Arrdata   );
					} catch (\Exception $e) {
						echo 'Ошибка: ',  $e->getMessage(), "\n";
						$output = self::getBackup($urlMd5  ) ;
						$_Err = true ;
						
						// return false ;
					}
					
					
					
					
					
				// 	echo'<pre>';print_r( $data );echo'</pre>'.__FILE__.' '.__LINE__;
					
					/*$a_after = '<a terget="_blank"  href="'.$data->screenshots->after.'">Screenshot after</a><br>';
					$a_before = '<a  terget="_blank" href="'.$data->screenshots->before.'">Screenshot before</a><br>';
					//$a_css = '<a terget="_blank"  href="'.$data->Url.'">Url css file</a><br>';
					$app = JFactory::getApplication();
					$app->enqueueMessage('Критические стили созданны<br>'. $a_before . $a_after   );
					
					echo'<pre>';print_r( $data->screenshots->after );echo'</pre>'.__FILE__.' '.__LINE__;*/
					if (!$_Err ){
						$output = file_get_contents($data->Url);
						self::addBackup( $urlMd5 ,$url, $output ) ;
					
					}
					
					$cache -> store ( $output ,  $urlMd5 ) ;
					
					
					
				}
				
			}#END IF
			
			
			$output = $criticalCssSetting->beforeAddCriticalCss. $output ;
			
			# Если запрос от клиента
			if ( !$penthouse )
			{
				# Критические стили в файле
				if ( $criticalCssSetting->loadCssAsFile )
				{
					$path = JPATH_STARCACHE_CRITICAL_CSS;
					$file = $urlMd5 . '.css';
					if ( !JFolder::exists( $path ) )
					{
						JFolder::create( $path, $mode = 0755 );
					}#END IF
					JFile::write( $path . '/' . $file, '/***' . $url . '***/' . $output );
					
					
					$link = '<link type="text/css" rel="stylesheet" href="/media/starcache/critical_css/'.$file.'">' ;
					$bodyHtml = str_replace( '</head>' , $link .'</head>' , $bodyHtml) ;
					
				}else{
					$bodyHtml = str_replace( '</head>' , '<style>'.$output .'</style></head>' , $bodyHtml) ;
				}#END IF
			}#END IF
			
			
			
			$app->setBody($bodyHtml) ;
		}#END FN
		
		/**
		 * Получение данных из резеврной копии
		 *
		 * @param $urlMd5
		 * @param $url
		 * @param $output
		 *
		 * @author    Gartes
		 *
		 * @since     3.8
		 * @copyright 13.12.18
		 * @return false|string
		 */
		public static function getBackup($urlMd5   ){
			$path = JPATH_STARCACHE_CRITICAL_CSS_BACKUP;
			$file = $urlMd5 . '.css';
			if (!JFile::exists( $path.'/'.$file ) ) return false ;
			
			$output = file_get_contents($path  . '/' .  $file );
			return $output ;
		}#END FN
		
		
		/**
		 * Создание резервной копии
		 * @param $urlMd5
		 * @param $url
		 * @param $output
		 *
		 * @author    Gartes
		 *
		 * @since     3.8
		 * @copyright 13.12.18
		 */
		public static function addBackup($urlMd5 , $url,  $output ){
			$path = JPATH_STARCACHE_CRITICAL_CSS_BACKUP;
			$file = $urlMd5 . '.css';
			if ( !JFolder::exists( $path ) )
			{
				JFolder::create( $path, $mode = 0755 );
			}#END IF
			JFile::write( $path . '/' . $file, '/***' . $url . '***/' . $output );
		}
		
	 
		public static function createFileAllCss ( $allCss , $app ){
			
			
			// echo'<pre>';print_r( $app->input );echo'</pre>'.__FILE__.' '.__LINE__;
			jimport ( 'joomla.filesystem.file' );
			jimport( 'joomla.environment.uri' );
			
			$uri = JFactory::getURI();
			$url = $uri->toString(array('path', 'query', 'fragment'));
			$file_id  =  md5 ( $url ) ;
			
			$fileName = 'starcache_allCss_'.$file_id.'.css';
			// \JFile::exists('starcache_allCss.css');
		
			JFile::write(JPATH_BASE.'/cache/starcache_allCss/' . $fileName ,  $allCss ) ;
			
			
			
			$fileUrl = \JURI::root() . 'cache/starcache_allCss/' . $fileName ;
			
			return $fileUrl ;
			
			
			
			
		}#END FN
		
		
		/**
		 * @return bool
		 * @throws \Exception
		 * @author    Gartes
		 *
		 * @since     3.8
		 * @copyright 13.12.18
		 */
		public static function testConnect(){
			$app = JFactory::getApplication();
			$penthouse = $app->input->get('penthouse' , false ) ;
			$css = new \Optimize\css\cssOptimize();
			if ( !$penthouse ) {
				
				$ArrData = [ 'task' => 'test' ,];
				try {
					
					$res = $css->sendPost($ArrData);
				} catch (\Exception $e) {
					return false ;
				}
				return true ;
			}
			return false ;
		}
		
		
		/**
		 * Создание отложенной загрузки для CSS файлов
		 *
		 * @param $doc
		 *
		 * @author    Gartes
		 *
		 * @since     3.8
		 * @copyright 04.12.18
		 * @return bool
		 * @throws \Exception
		 */
		public static function downCss ( $PLG , $doc   )
		{
			
			
			$cssParams = $PLG->params->get('cssSetting');
			
			if ($cssParams->historyOn){
				$cssParams->fileCssRules = self::addHistoryParams( $doc->_styleSheets , $PLG  );
			}
			$styleSheetsBACK =  $doc->_styleSheets ; 
			$rules = \starcache\helpers\helper::_prepareRules(  $cssParams->fileCssRules    );
			$doc->_styleSheets = \starcache\helpers\helper::_mergeRules(  $doc->_styleSheets , $rules   );
			
			$css = new \Optimize\css\cssOptimize();
			
			$res = $css->addLazyLoadingCss( $doc->_styleSheets );
		 
			if ( $res ){
				$doc->_styleSheets = [];
				return true ;
			}
			$doc->_styleSheets = $styleSheetsBACK ;
			return false ;
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