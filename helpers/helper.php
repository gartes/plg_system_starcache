<?php
	namespace starcache\helpers;
	
	use JEventDispatcher;
	use JFactory;
	use JPluginHelper;
	use JUri;
	
	/**
	 * Created by PhpStorm.
	 * User: oleg
	 * Date: 04.12.18
	 * Time: 22:17
	 */
	
	
	
	
	defined( '_JEXEC' ) or die( 'Restricted access' );
	
	class helper
	{
	
		public static function preconnectHendler($PLG){
			$preconnectRules = $PLG->params->get('preconnectRules' , [] );
			if (!count($preconnectRules)) return ;
			
			$doc = JFactory::getDocument();
			
			
			foreach ($preconnectRules as $Rules) {
				if ( !$Rules->Published )  continue ;
				//
				$tag = '<link rel="'. $Rules->type.'" ' ;
				$tag .='href="'. $Rules->url .'" ';
				$tag .= ($Rules->type_preloaded ? 'as="'.$Rules->type_preloaded.'"' : '' ) ; 
				$tag .='>';
				$doc->addCustomTag( $tag );
//				echo'<pre>';print_r( $Rules );echo'</pre>'.__FILE__.' '.__LINE__;
			}#END FOREACH
			
		}#END FN
	 
	
		
		/**
		 * Get a cache key for the current page based on the url and possible other factors.
		 * Получите ключ кеша для текущей страницы на основе URL-адреса и возможных других факторов.
		 * @return  string
		 *
		 * @throws Exception
		 * @throws \Exception
		 * @since   3.7
		 */
		public static function getCacheKey()
		{
			static $key;
			
			if (!$key)
			{
				
				$jinput = JFactory::getApplication()->input;
				JPluginHelper::importPlugin('pagecache');
				
				$parts = JEventDispatcher::getInstance()->trigger('onPageCacheGetKey');
				$parts[] = JUri::getInstance()->toString();
				
				$parts[] = $jinput->cookie->get('cookieNotice', null, 'bool');
				
				$key = md5(serialize($parts));
			}#END IF
			
			return $key;
		}#END FN
		
		
		public static function _mergeRules ( $docData, $rules )
		{
			
			$doc = JFactory::getDocument();
			$MediaVersion = $doc->getMediaVersion();
			$retArr = [];
			foreach ($docData as $url => $paramData ){
				
				$url  =  str_replace('?vmver='.VM_JS_VER , '' ,  $url );
				
				# Если настройки для файла присутствуют
				if ( is_array( $rules ) && array_key_exists( $url , $rules) ) {
					
					
					$_MediaVersionLocal = (isset($rules[$url]->options->version)? $rules[$url]->options->version : false ) ;
					
					if (!isset($rules[$url]->options->verType)) $rules[$url]->options->verType = false ;
					
					$newUrl = $url;
					
					/*echo'<pre>';print_r( $url );echo'</pre>'.__FILE__.' '.__LINE__;
					echo'<pre>';print_r( $rules[$url] );echo'</pre>'.__FILE__.' '.__LINE__;*/
					
					$rules[$url]->options->detectDebug = (isset($rules[$url]->options->detectDebug) ? $rules[$url]->options->detectDebug : false ) ;
					
					
					if (  !$rules[$url]->options->detectDebug){
						$newUrl = $url.'?_='.( $rules[$url]->options->verType && !empty($_MediaVersionLocal) ? $_MediaVersionLocal : $MediaVersion );
					}
					
					
					$retArr[$newUrl] = (object)array_merge((array)$paramData, (array)$rules[$url] );
					
					
					
				}else{
					
					$retArr[$url] = (object)$paramData ;
				}#END IF
				
				
				
				
			}#END FOREACH
			
			return $retArr ;
			
		}#END FN
		
		/**
		 * Сохранить параметры плагина
		 * @param $PLG
		 *
		 * @throws \Exception
		 * @author    Gartes
		 *
		 * @since     3.8
		 * @copyright 06.12.18
		 */
		public static function saveParams( &$PLG ){
			
			$Plg = new \stdClass();
			$Plg->_name = 'starcache';
			$Plg->_type = 'system';
			$zazExtensions = new \Core\extensions\zazExtensions();
			$PlgId = $zazExtensions->getJoomlaPluginId($Plg) ;
			$zazExtensions->updateExtensionParams( $PLG->params->toString() , $PlgId );
		}#END FN
		
		/**
		 * Подготовка правил загрузки JS Файлов из настроек плагина
		 *
		 * @param $reules
		 *
		 *
		 * @author    Gartes
		 *
		 * @since     3.8
		 * @copyright 04.12.18
		 *
		 * @return array|bool
		 */
		public static function _prepareRules ( $rules )
		{
			
			$ret = [];
			
		 
			
			if ( empty($rules) || count($rules)==0 ) return false ;
			
			foreach ( $rules as $r )
			{
				$ret[ $r->file ] = $r;
			}#END FOREACH
			
			return $ret;
		}#END FUN
		
	}#END CLASS