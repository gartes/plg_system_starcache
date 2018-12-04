<?php
	
	namespace starcache\helpers;
	
	use DOMDocument;
	use JFactory;
	use JUserHelper;
	use vmJsApi;
	
	/**
	 * @package     helpers
	 * @subpackage
	 *
	 * @copyright   A copyright
	 * @license     A "Slug" license name e.g. GPL2
	 */
	class scripts
	{
		
		/**
		 * scripts constructor.
		 *
		 * @param $subject
		 * @param $config
		 */
		public function __construct ()
		{
		
		}#END FUN
		
		
		
		
		
		/**
		 * Обновить в параметрах пллагина  MediaVersion
		 *
		 * @param $jPlugin
		 *
		 * @return mixed media Version
		 * @throws \Exception
		 * @author    Gartes
		 * @since     3.8
		 * @copyright 28.11.18
		 */
		public static function updateMediaVersion ( $jPlugin )
		{
			
			
			$mediaVersion = JUserHelper::genRandomPassword( $length = 16 );
			$jPlugin->params->set( 'mediaVersion', $mediaVersion );
			
			if ( !class_exists( 'Core\zazCore' ) ) require JPATH_LIBRARIES . '/zaz/Core/zazCore.php';
			
			$extensions = new \Core\extensions\zazExtensions();
			$PluginId   = $extensions->getJoomlaPluginId( $jPlugin );
			$extensions->updateExtensionParams( $jPlugin->params->toString(), $PluginId );
			
			return $mediaVersion;
			
		}#END FUN
		
		
		/**
		 * Pick up js file downloaders
		 *
		 *
		 * @param $doc
		 *
		 * @author    Gartes
		 * @since     3.8
		 * @copyright 27.11.18
		 *
		 */
		public static function _removeScripts ( \PlgSystemStarcache &$Starcache , $doc )
		{
			$regex        = self::_prepExclude( (array) $Starcache->params->get( 'scripts', [] ) );
			$rules = self::_prepareRules($Starcache->params->get('fileJsRules') , array() );
			
			
			$matched      = [];
			$regexinclude = $Starcache->params->get( 'include', false );
			
			foreach ( $regex as $r )
			{
				
				$match   = preg_grep( '/' . $r . '/', array_keys( $doc->_scripts ) );
				$matched = array_merge( $matched, $match );
				
			}#END FOREACH
			
			
			
			foreach ( $doc->_scripts as $src => $attribs )
			{
				
				# Если есть в правилах загрузки
				if (isset($rules[$src])){
					
					# Если отмечено не загружать
					if ( !isset($rules[$src]->load)) {
						unset( $doc->_scripts[ $src ] );
						continue;
					}#END IF
					
					$attribs = array_merge( $attribs , (array)$rules[$src] );
					
					# Если замена файла
					if ( $rules[$src]->overrideFile ){
						$newSrc = $rules[$src]->overrideFile ;
						
						$Starcache->_scripts[ $newSrc ] = $attribs;
						unset( $doc->_scripts[ $src ] );
						
						self::_createPreloadTag( $newSrc , $attribs , $doc);
						
						continue;
					
					}#END IF
					
					self::_createPreloadTag( $src , $attribs , $doc);
					
					
					/*echo'<pre>';print_r(  $rules[$src] );echo'</pre>'.__FILE__.' '.__LINE__;
					echo'<pre>';print_r( $src );echo'</pre>'.__FILE__.' '.__LINE__;
					echo'<pre>';print_r( $attribs );echo'</pre>'.__FILE__.' '.__LINE__;*/
					
				}#END IF
				
				
				
				
				
				if ( !$regexinclude )
				{
					if ( !in_array( $src, $matched ) )
					{
						$Starcache->_scripts[ $src ] = $attribs;
						unset( $doc->_scripts[ $src ] );
					}#END IF
					continue;
				}#END IF
				
				
				
				if ( in_array( $src, $matched ) )
				{
					
					$attribs[ 'defer' ]              = 1;
					$attribs[ 'options' ][ 'defer' ] = 1;
					
					$Starcache->_scripts[ $src ] = $attribs;
					unset( $doc->_scripts[ $src ] );
					
				}#END IF
				
			}#END FOREACH
			
			// xzlib.js
			//    echo'<pre>';print_r( $Starcache->_scripts );echo'</pre>'.__FILE__.' '.__LINE__;
			
		}#END FUN
		
		private static function _createPreloadTag( $src , $attribs , $doc ){
			if ( !$attribs['preload']) return ;
			$tag = '<link rel="preload" href="'.$src.'" as="script">';
			$doc->addCustomTag( $tag );
		}#END FUN
		
		
		/**
		 * Подготовка правил загрузки JS Файлов из настроек плагина 
		 * @param $reules
		 *
		 * @return array
		 * @author    Gartes
		 *
		 * @since     3.8
		 * @copyright 04.12.18
		 */
		private static function _prepareRules ( $reules )
		{
			
			$ret = [];
			foreach ( $reules as $r )
			{
				$ret[ $r->file ] = $r;
				
				
				
			}#END FOREACH
			
			return $ret;
		}#END FUN
		
		
		/**
		 * Перенос декларированных скриптов в низ
		 *
		 * @param \PlgSystemStarcache $Starcache
		 *
		 * @throws \Exception
		 * @author    Gartes
		 *
		 * @since     3.8
		 * @copyright 01.12.18
		 */
		public static function _moveScript( \PlgSystemStarcache &$Starcache  ) {
			$app = JFactory::getApplication();
			
			$body = str_replace('</body>', self::_renderDeclaration( $Starcache ,  $Starcache->_script) . "</body>", $app->getBody());
			$app->setBody($body);
		}#END FN
		
		/**
		 * Создание Декларированных блоков
		 *
		 * @param \PlgSystemStarcache $Starcache
		 * @param $script
		 *
		 * @return mixed
		 * @author    Gartes
		 *
		 * @since     3.8
		 * @copyright 01.12.18
		 */
		private static function _renderDeclaration( $Starcache , $script) {
			$tag = self::_renderScript($Starcache , false , array('type' => 'text/javascript'));
			return str_replace('</script>', "\n" . $script . "\n</script>", $tag);
		}#END FN
		
		
		/**
		 * Вырезать все теги <scipt> из тела страницы
		 *
		 * @param \PlgSystemStarcache $Starcache
		 *
		 * @throws \Exception
		 * @author    Gartes
		 *
		 * @since     3.8
		 * @copyright 01.12.18
		 */
		public static function _excludeScriptInBody (\PlgSystemStarcache &$Starcache){
			$app = JFactory::getApplication();
			$body = $app->getBody();
			$regex = '/(\<script(.*?)?\>(.|\s)*?\<\/script\>)/i';
			preg_match_all($regex, $body , $scripts);
			
			$Starcache->_ScrptInBody =  $scripts[0] ;
			
			$regex='#<script(.*?)>(.*?)</script>#is' ;
			$body = preg_replace($regex, "", $body );
			
			$app->setBody( $body );
		}#END FN
		
		/**
		 * Вситавить вниз body теги <scripts /> найденные в теле
		 *
		 * @param \PlgSystemStarcache $Starcache
		 *
		 * @throws \Exception
		 * @author    Gartes
		 *
		 * @since     3.8
		 * @copyright 01.12.18
		 *
		 */
		public static function _includeScriptInBody ( \PlgSystemStarcache &$Starcache ){
			$app = JFactory::getApplication();
			$body = $app->getBody();
			$scriptStr = '';
			
			
			
			foreach ($Starcache->_ScrptInBody as  $scriptTag){
				// TODO Add script compress
				$scriptStr .= $scriptTag ;
			}#END FOREACH
			
			$body = str_replace('</body>', $scriptStr . "</body>", $body);
			
			$app->setBody($body);
		}#END FN
		
		/**
		 * Записать ссылки на файлы скриптов
		 *
		 * @param \PlgSystemStarcache $Starcache
		 *
		 * @throws \Exception
		 * @author    Gartes
		 *
		 * @since     3.8
		 * @copyright 01.12.18
		 */
		public static function _moveScripts( \PlgSystemStarcache &$Starcache){
			$app = JFactory::getApplication();
			$body = $app->getBody();
			
			foreach ($Starcache->_scripts as $src => $attribs)
			{
				$body = str_replace('</body>', self::_renderScript($Starcache ,  $src, $attribs) . "</body>", $body);
			}
			$app->setBody($body);
		}#END FUN
		
		/**
		 * Записать _scripts в тело страницы
		 *
		 * @param      $Starcache
		 * @param bool $src
		 * @param      $attribs
		 *
		 * @return string
		 * @author    Gartes
		 *
		 * @since     3.8
		 * @copyright 01.12.18
		 */
		private static function _renderScript( $Starcache ,  $src = false, $attribs = [] ) {
			
//			echo'<pre>';print_r( $src );echo'</pre>'.__FILE__.' '.__LINE__;
//			echo'<pre>';print_r( $attribs );echo'</pre>'.__FILE__.' '.__LINE__;
			
			$defaultJsMimes = array('text/javascript', 'application/javascript', 'text/x-javascript', 'application/x-javascript');
			$doc = JFactory::getDocument();
			
			$mediaVersion = (
				isset($attribs['options']['version'])
				&&
				$attribs['options']['version']
				&& strpos($src, '?') === false && ($Starcache->_mediaVersion || $attribs['options']['version'] !== 'auto')) ? $Starcache->_mediaVersion : '';
				
			$dom = new DOMDocument('1.0', 'UTF-8');
			$script = $dom->createElement('script');
			
			# add src attribute
			if ($src)
			{
				self::_addAttribute($dom, $script, 'src', $src . $mediaVersion);
			}
			# add type attribute
			if (array_intersect(array_keys($attribs), array('type', 'mime')) && !$doc->isHtml5() && in_array((isset($attribs['type']) ? $attribs['type'] : $attribs['mime']), $defaultJsMimes))
			{
				self::_addAttribute($dom, $script, 'type', isset($attribs['type'])?$attribs['type']:$attribs['mime']);
			}
			
			
			/*if($src == 'https://smska.tk/libraries/xzlib/app/document/assets/js/xzlib.js') {
				echo'<pre>';print_r( $src );echo'</pre>'.__FILE__.' '.__LINE__;
				echo'<pre>';print_r( $attribs );echo'</pre>'.__FILE__.' '.__LINE__;
				echo'<pre>';print_r(  $attribs['defer'] == true   );echo'</pre>'.__FILE__.' '.__LINE__;
				
			}*/
		
		
			# add defer attribute
			if (isset($attribs['defer']) && $attribs['defer'] == true)
			{
				self::_addAttribute($dom, $script, 'defer');
			}
			
			
			
			
			# add async attribute
			if (isset($attribs['async']) && $attribs['async'] == true)
			{
				self::_addAttribute($dom, $script, 'asnyc');
			}
			
			
			
			# add charset attribute
			if (isset($attribs['charset']))
			{
				self::_addAttribute($dom, $script, 'charset', $attribs['charset']);
			}
			$dom->appendChild($script);
			
			if (isset($attribs['options']) && isset($attribs['options']['conditional']))
			{
				$tag = $dom->saveHTML();
				return implode("\n", array('<!--[if ' . $attribs['options']['conditional'] . ']>', $tag . '<![endif]-->', ''));
			}
			return $dom->saveHTML();
		}#END FN
		
		
		/**
		 * Добавить атребуты к элементу
		 *  # add src
		 *  # add type
		 *  # add defer
		 *  # add async
		 *  # add charset
		 *
		 * @param      $dom
		 * @param      $element
		 * @param      $name
		 * @param bool $value
		 *
		 * @author    Gartes
		 *
		 * @since     3.8
		 * @copyright 01.12.18
		 */
		private static function _addAttribute($dom, &$element, $name, $value = false) {
			$attr = $dom->createAttribute($name);
			if ($value)
			{
				$attr->value = $value;
			}
			$element->appendChild($attr);
		}#END FN
		
		
		/**
		 * Отделить скрипты из списка исключений
		 *
		 * @param $a
		 *
		 * @return array
		 * @author    Gartes
		 *
		 * @since     3.8
		 * @copyright 01.12.18
		 */
		private static function _prepExclude ( $a )
		{
			return array_values( array_map( function ( $i ) {
				return $i->regex;
			}, $a ) );
		}#END FN
		
		
	}#END CLASS