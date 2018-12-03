<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  System.cache
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
	
	use starcache\helpers\scripts;
	use starcache\helpers\css;
	
	defined('_JEXEC') or die('Restricted access');
jimport('joomla.plugin.plugin');
$PATH_autoload = JPATH_PLUGINS . "/system/starcache/vendor/autoload.php" ;
require_once  $PATH_autoload ;



//require 'vendor/autoload.php';


/**
 * Joomla! Page Cache Plugin.
 *
 * @since  3.7
 */
class PlgSystemStarcache extends \JPlugin
{
	/**
	 * The name of the plugin
	 *
	 * @var    string
	 * @since  1.5
	 */
	public $_name = null;
	
	/**
	 * The plugin type
	 *
	 * @var    string
	 * @since  1.5
	 */
	public $_type = null;
	
	
	/**
	 * Cache instance.
	 *
	 * @var    JCache
	 * @since  3.7
	 */
	public $_cache;

	/**
	 * Cache key
	 *
	 * @var    string
	 * @since  3.0
	 */
	public $_cache_key;

	/**
	 * Application object.
	 *
	 * @var    \JApplicationCms
	 * @since  3.8.0
	 */
	protected $app;
	
	
	public $_scripts = [];
	public $_script = '';
	public $_ScrptInBody = [];
	public $_mediaVersion;
	
	
	/**
	 *  * Constructor.
	 *
	 * @param   object  &$subject The object to observe.
	 * @param   array    $config  An optional associative array of configuration settings.
	 *
	 * @throws \Exception
	 * @since   3.7
	 *
	 *
	 */
	public function __construct(&$subject, $config)
	{
		
		parent::__construct($subject, $config);
        
        // Set the language in the class.
		$options = array(
			'defaultgroup' => 'page', 
			'browsercache' => $this->params->get('browsercache', false),
			'caching'      => false, 
		);
		
		// Получите приложение, если это не сделано JPlugin. Это может произойти во время обновлений от Joomla 2.5.
		if (!$this->app)
		{
			$this->app = JFactory::getApplication();
		}
        $this->_cache     = JCache::getInstance('page', $options);
		
		
		// echo'<pre>';print_r( $this->_cache );echo'</pre>'.__FILE__.' '.__LINE__;
	}#END FN
    
    /**
     * Перед созданием HEAD
     *
     * @since 3.8
     *
     */
	public function onBeforeCompileHead ()
	{
		if ( $this->app->isAdmin() ) return;
		
		$doc = JFactory::getDocument();
		
		# Перенос скриптов вниз
		if ($this->params->get( 'downJsScript', false ) ){
			# вырезать все Scripts - Записать  в $this->_scripts
			scripts::_removeScripts( $this, $doc );
		}#END IF
		
		# Переносить Декларации JS вниз
		if ($this->params->get('downJsDeclarations', false))
		{
			$this->_script = $doc->_script['text/javascript'];
			unset($doc->_script['text/javascript']);
		}#END IF
		
		
		$css = new css();
		$CriticalCss = $css::getCriticalCss();
		
		
	}#END FN
	
	
	/**
	 * После создания тела страницы
	 *
	 * @throws Exception
	 * @author    Gartes
	 *
	 * @since     3.8
	 * @copyright 01.12.18
	 */
    public function onAfterRender(){
		if (JFactory::getApplication()->isAdmin()){	return;  }
		
		$downJsDeclarations = $this->params->get( 'downJsDeclarations', false ) ;
		$downJsSherchBody = $this->params->get( 'downJsSherchBody', false ) ;
		
		# Перенос тегов <script /> из тела старницы в низ
	    # Вырезать и сохранить в переменной public $_ScrptInBody
	    if ( $downJsDeclarations &&  $downJsSherchBody )
	    {
		     scripts::_excludeScriptInBody($this);
	    }#END IF
		
		# Если есть скрипты для переноса
	    if (count($this->_scripts))
	    {
		    scripts::_moveScripts($this);
	    }#END IF
	    
	    
	    
	    # Если установлено переносить декларативы из HEAD вниз страницы
	    if ( $downJsDeclarations )
	    {
		   scripts::_moveScript($this);
		    if ( $downJsSherchBody )
		    {
			    scripts::_includeScriptInBody($this);
				   
		    }#END IF
	    }#END IF
	
	    
	    
	    
	    
    }#END FN
	
	/**
	 * @throws Exception
	 * @author    Gartes
	 * @since     3.8
	 * @copyright 28.11.18
	 */
	public function onAfterRoute(){
		
        $doc = JFactory::getDocument();
         
        #Режим USER ?
	    $user_view_param = $this->params->get( 'ukcpu_lib_view', false );
        #ID Медиа версии
	    $mediaVersion = $this->params->get( 'mediaVersion', false );
		
		#Если включен режим ращработчика
		#или mediaVersion - не установлена == 0
		// TODO  ( !$user_view_param  && 1 ) - 1 == Admin IP Address  in array this params
		if ( ($user_view_param && !$mediaVersion ) || ( !$user_view_param  && 1 )     )
		{
			#Обновить в параметрах пллагина  MediaVersion
			$mediaVersion = scripts::updateMediaVersion($this);
		
		}#END IF
		
		#Установить ID Медиа версии
		$doc->setMediaVersion( $mediaVersion );
        
		
		
        # config class ukcpuDocument
        $ukcpuDoc_options = array (
        
        
       
        
                    // developer  ||  user
        
        'view'          =>      ( $user_view_param ? 'user' :'developer' )       ,
        'mediaVersion'  =>      $mediaVersion , 
        'cache'         =>      true                , // Кешировать результаты true || false
        /**
         * пергрузка кеша true || false
         * Используеться при @param view == user
         * Всегда если @param view == developer
         * DEF - false  
         */ 
        'cacheReload'   =>  false       , 
        'comment'       =>  true        ,
        /**
         *  НЕ ОБЯЗАТЕЛЬНЫЙ - 
         *  глобальная настройка для объекта ukcpuDocument метода addScriptDeclaration
         *  можно передать при вызове самого метода 
         *  $option = array ( 
         *          'compressorCss' => true ,  
         *  
         *  );
         *  $uDoc->addStyleDeclaration ($styleArr , $option ) ;
         * 
         */ 
        //'addScriptDeclaration'=> array (
            /**
             * Сжатие CSS true || false
             * DEF - true 
             */ 
           // 'compressorCss'    =>  false    , 
       // ),
        );    
      //  $uDoc = ukcpuDocument::getDocument( $ukcpuDoc_options );
    
    }#END FN
    
	
	
	
    
	
	
	/**
	 * Get a cache key for the current page based on the url and possible other factors.
	 * Получите ключ кеша для текущей страницы на основе URL-адреса и возможных других факторов.
	 * @return  string
	 *
	 * @throws Exception
	 * @since   3.7
	 */
	protected function getCacheKey()
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
	 * Converting the site URL to fit to the HTTP request.
	 *
	 * @return  void
	 *
	 * @throws Exception
	 * @since   1.5
	 */
	public function onAfterInitialise()
	{
		$app  = $this->app;
        $user = JFactory::getUser();
		
        // Кеш отключен в настройках плагина
        if (  !$this->params->get('cache_on', false) ){
           return;
        }#END IF
		
		if ($app->isClient('administrator'))
		{
			return;
		}
		
		if (count($app->getMessageQueue()))
		{
			return;
		}
		
		// Если какие-либо плагины pagecache возвращают false для onPageCacheSetCaching, не используется кеш.
		JPluginHelper::importPlugin('pagecache');
		$results = JEventDispatcher::getInstance()->trigger('onPageCacheSetCaching');
		 
		
		
		
		
        // только именно false - строгое соответствие
        $caching = !in_array(false, $results, true);
		
		 
		
		if ($caching && $user->get('guest') && $app->input->getMethod() == 'GET')
		{
			$this->_cache->setCaching(true);
		}
		
		
		
		$data = $this->_cache->get($this->getCacheKey());
		
		if ($data !== false)
		{
			// Set cached body.
			$app->setBody($data);
			
			$r = $app->toString() ;
			echo $r ;
			
			if (JDEBUG)
			{
				JProfiler::getInstance('Application')->mark('afterCache');
			}

			$app->close();
		}
		
		
	}
	
	
	/**
	 *      https://issues.joomla.org/tracker/joomla-cms/8890
	 *
	 * After render.
	 *
	 * @return   void
	 *
	 * @throws Exception
	 * @since   1.5
	 */
	public function onAfterRespond ()
	{
		$app = $this->app;
		
		// Кеш отключен в настройках плагина
		if ( !$this->params->get( 'cache_on', false ) )
		{
			return;
		}#END IF
		
		if ( $app->isClient( 'administrator' ) )
		{
			return;
		}#END IF
		
		if ( count( $app->getMessageQueue() ) )
		{
			return;
		}#END IF
		
		$user = JFactory::getUser();
		
		if ( $user->get( 'guest' ) && !$this->isExcluded() )
		{
			#We need to check again here, because auto-login plugins have not been fired before the first aid check.
			$this->_cache->store( null, $this->getCacheKey() );
		}#END IF
		
		
		
	}#END FN

	/**
	 * Проверьте, исключена ли страница из кеша или нет.
	 *
	 * @return   boolean  True if the page is excluded else false
	 *
	 * @since    3.5
	 */
	protected function isExcluded ()
	{
		// Check if menu items have been excluded
		if ( $exclusions = $this->params->get( 'exclude_menu_items', [] ) )
		{
			// Get the current menu item
			$active = $this->app->getMenu()->getActive();
			
			if ( $active && $active->id && in_array( $active->id, (array) $exclusions, true ) )
			{
				return true;
			}
		}
		
		// Check if regular expressions are being used
		if ( $exclusions = $this->params->get( 'exclude', '' ) )
		{
			// Normalize line endings
			$exclusions = str_replace( [ "\r\n", "\r" ], "\n", $exclusions );
			
			// Split them
			$exclusions = explode( "\n", $exclusions );
			
			// Get current path to match against
			$path = JUri::getInstance()->toString( [ 'path', 'query', 'fragment' ] );
			
			// Loop through each pattern
			if ( $exclusions )
			{
				foreach ( $exclusions as $exclusion )
				{
					// Make sure the exclusion has some content
					if ( $exclusion !== '' )
					{
						if ( preg_match( '/' . $exclusion . '/is', $path, $match ) )
						{
							return true;
						}
					}
				}
			}
		}
		
		// If any pagecache plugins return true for onPageCacheIsExcluded, exclude.
		JPluginHelper::importPlugin( 'pagecache' );
		
		$results = JEventDispatcher::getInstance()->trigger( 'onPageCacheIsExcluded' );
		
		if ( in_array( true, $results, true ) )
		{
			return true;
		}
		
		return false;
	}#END FN
	
	
	
	
	/**
	 *  Получение данных Ajax
	 * для изменения настроек плагина
	 *
	 * @since 3.8
	 *
	 */
	public function onAjaxStarcache (){
		
		
		
		
		
		jimport('ukcpu.extensions.extensions');
		
		$pluginData  = ukcpuExtensions::getItemByElement('starcache', 'plugin', 'system') ;
		$extensionsId = $pluginData ['extension_id'];
		
		$jinput = JFactory::getApplication()->input;
		$opt = $jinput->get('data' , null , 'array' ) ;
		
		
		if (!class_exists( 'CacheModelCache' ))
			require(JPATH_ROOT .'/administrator/components/com_cache/models/cache.php');
		$modelCache = new CacheModelCache();
		$allCleared = true ;
		$clients    = array(1, 0);
		
		foreach ($clients as $client)
		{
			$mCache    = $modelCache->getCache($client);
			$clientStr = JText::_($client ? 'JADMINISTRATOR' : 'JSITE') .' > ';
			
			foreach ($mCache->getAll() as $cache)
			{
				if ($mCache->clean($cache->group) === false)
				{
					$this->app->enqueueMessage(JText::sprintf('Ошибка очистки кеша ', $clientStr . $cache->group), 'error');
					$allCleared = false;
				}
			}
		}
		if ($allCleared)
		{
			
			$this->app->enqueueMessage(JText::_('<b>Кеш очищен</b>.') , 'message' );
			
		}
		else
		{
			$this->app->enqueueMessage(JText::_('<b>Кеш очищен не полностью</b>.'), 'warning');
		}
		
		switch ($opt['el']){
			
			// Переключение загрузки "user" - "developer"
			case 'lib_view' :
				try
				{
					$newMediaVersion = JUserHelper::genRandomPassword ($length = 8) ;
					
					$user_view_param = $this->params->get( 'ukcpu_lib_view', false );
					$this->params->set('ukcpu_lib_view' , ( $user_view_param ? 0 : 1 ) ) ;
					$this->params->set('cache_on' , ( $user_view_param ? 0 : 1 ) ) ;
					$this->params->set('mediaVersion' , $newMediaVersion ) ;
					
					
					$R = ukcpuExtensions::saveParametersExtensions( $extensionsId, $this->params ) ;
					$this->app->enqueueMessage('Медиа версия изменена - Media Version = <b>' . $newMediaVersion .'</b>'  );
					$this->app->enqueueMessage('JCahe <b>' . ( $user_view_param ? 'Отключен' : 'Включен' ) .'</b>.');
					$this->app->enqueueMessage('Включен режим <b>' . ( $user_view_param ? 'Dev.' : 'User' ).'</b>' );
					
					
				}catch(Exception $e){
					echo new JResponseJson($e);
				}
				
				break;
			case 'cache_on' :
				try
				{
					$cache_on = $this->params->get( 'cache_on', false );
					$this->params->set('cache_on' , ( $cache_on ? 0 : 1 ) ) ;
					$R = ukcpuExtensions::saveParametersExtensions( $extensionsId, $this->params ) ;
					$this->app->enqueueMessage('JCahe <b>' . (  $cache_on ? 'Отключен' : 'Включен' ) .'</b>.');
				}catch(Exception $e){
					echo new JResponseJson($e);
				}
				break;
		}
		echo new JResponseJson( json_decode( $this->params ) );
		jExit();
	}#END FN
	
	
	
	
	
	
}#END CLASS
