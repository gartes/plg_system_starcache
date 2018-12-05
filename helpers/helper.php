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