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
		 * @throws \Exception
		 */
		
		 
		
		public static function _removeScripts (   $Starcache , $doc )
		{
			$regex        = self::_prepExclude( (array) $Starcache->params->get( 'scripts', [] ) );
			
			$jsRules = $Starcache->params->get('jsSetting') ;
			$rules = \starcache\helpers\helper::_prepareRules(  $jsRules->filejsRules   );
		 
			 
			
			
			
			$matched      = [];
			$regexinclude = $Starcache->params->get( 'include', false );
			foreach ( $regex as $r )
			{
				
				$match   = preg_grep( '/' . $r . '/', array_keys( $doc->_scripts ) );
				$matched = array_merge( $matched, $match );
				
			}#END FOREACH
			
			
			$_scripts = [];
			$_ind = count($rules);
			$_saveData = new \stdClass();
			
			foreach ( $doc->_scripts as $src => $attribs )
			{
				$oldUrl = $src ;
				//$src =  str_replace('?vmver='.VM_JS_VER , '' ,  $src );
				$src = preg_replace("/(\?vmver=.*)/", "", $src);
				// echo'<pre>';print_r( $src );echo'</pre>'.__FILE__.' '.__LINE__;
				
				
				
				// echo'<pre>';print_r( $rules );echo'</pre>'.__FILE__.' '.__LINE__;
				# Если есть в правилах загрузки
				if ( isset( $rules[ $src ] ) )
				{
					# Если отмечено не загружать
					if ( isset($rules[ $src ]->load) && !$rules[ $src ]->load )
					{
						//	unset( $doc->_scripts[ $oldUrl ] );
						continue;
					}#END IF
					
					$u = $src ;
					if ( isset($rules[ $src ]->override) && $rules[ $src ]->override ){
						$u = $rules[ $src ]->overrideFile ;
					}#END IF
					
					$_scripts[ $u ] = new \stdClass();
					$_scripts[ $u ] = (object)array_merge( (array)$attribs, (array)$rules[ $src ] );
					$_scripts[ $u ]->options = (object)array_merge( (array)$attribs['options'] , (array)$rules[ $src ]->options );
					
					self::_createPreloadTag( $u , $_scripts[ $u ] , $doc );
//					
					continue;
				}else{
					$u = $src ;
					$_scripts[ $u ] = new \stdClass();
					$_scripts[ $u ] = (object) $attribs ;
					$_scripts[ $u ]->options = (object)$attribs['options']   ;
					$_scripts[ $u ]->file = $u   ;
					
					$t_ind = 'filejsRules'.$_ind ;
					$_saveData->$t_ind = $_scripts[ $u ] ;
					$_ind ++;
				}#END IF
				
			}#END FOREACH
			
			
			
			
			if ( !empty($jsRules->filejsRules)) {
				$_saveData = (object)array_merge( (array)$jsRules->filejsRules, (array) $_saveData );
			}#END IF
			
			
//			echo'<pre>';print_r( $_saveData );echo'</pre>'.__FILE__.' '.__LINE__;
			$doc->_scripts=[];
			$Starcache->_scripts = $_scripts ;
			
			$jsRules->filejsRules = $_saveData ;
			$Starcache->params->set('upDateParams' , 2);
			$Starcache->params->set('jsSetting' , $jsRules );
		}#END FUN
		
		
		
		
		/**
		 * @param $src
		 * @param $attribs
		 * @param $doc
		 *
		 * @author    Gartes
		 *
		 * @since     3.8
		 * @copyright 04.12.18
		 */
		private static function _createPreloadTag( $src , $attribs , $doc ){
			if ( !isset( $attribs->preload ) || !$attribs->preload ) return ;
			$tag = '<link rel="preload" href="'.$src.'" as="script">';
			$doc->addCustomTag( $tag );
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
			
			
//			echo'<pre>';print_r( $attribs );echo'</pre>'.__FILE__.' '.__LINE__;
			
			
			$mediaVersion = (
				isset($attribs->options->version )
				&&
				$attribs->options->version
				&& strpos($src, '?_') === false && ( $Starcache->_mediaVersion || $attribs->options->version !== 'auto')) ? $Starcache->_mediaVersion : '';
				
			$dom = new DOMDocument('1.0', 'UTF-8');
			$script = $dom->createElement('script');
			
			# add src attribute
			if ($src)
			{
				self::_addAttribute($dom, $script, 'src', $src . $mediaVersion);
			}
			# add type attribute
			if (array_intersect(array_keys((array)$attribs), array('type', 'mime')) && !$doc->isHtml5() && in_array((isset($attribs->type ) ? $attribs->type : $attribs->mime ), $defaultJsMimes))
			{
				self::_addAttribute($dom, $script, 'type', isset($attribs['type'])?$attribs['type']:$attribs['mime']);
			}
			
			
			/*if($src == 'https://smska.tk/libraries/xzlib/app/document/assets/js/xzlib.js') {
				echo'<pre>';print_r( $src );echo'</pre>'.__FILE__.' '.__LINE__;
				echo'<pre>';print_r( $attribs );echo'</pre>'.__FILE__.' '.__LINE__;
				echo'<pre>';print_r(  $attribs['defer'] == true   );echo'</pre>'.__FILE__.' '.__LINE__;
				
			}*/
		
		
			# add defer attribute
			if (isset($attribs->defer ) && $attribs->defer  == true)
			{
				self::_addAttribute($dom, $script, 'defer');
			}
			
			
			
			
			# add async attribute
			if (isset($attribs->async ) && $attribs->async  == true)
			{
				self::_addAttribute($dom, $script, 'asnyc');
			}
			
			
			
			# add charset attribute
			if (isset($attribs->charset ))
			{
				self::_addAttribute($dom, $script, 'charset', $attribs->charset );
			}
			$dom->appendChild($script);
			
			if (isset($attribs->options ) && isset( $attribs->options->conditional ))
			{
				$tag = $dom->saveHTML();
				return implode("\n", array('<!--[if ' . $attribs->options->conditional . ']>', $tag . '<![endif]-->', ''));
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