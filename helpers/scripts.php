<?php
	namespace starcache\helpers ;
	use JUserHelper;
	
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
		public function __construct( ){
		
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
		public static function updateMediaVersion ($jPlugin){
			
			
			$mediaVersion = JUserHelper::genRandomPassword( $length = 16 );
			$jPlugin->params->set('mediaVersion' , $mediaVersion );
			
			if ( !class_exists( 'Core\zazCore' ) )  require JPATH_LIBRARIES . '/zaz/Core/zazCore.php';
			
			$extensions= new \Core\extensions\zazExtensions();
			$PluginId = $extensions->getJoomlaPluginId($jPlugin) ;
			$extensions->updateExtensionParams ( $jPlugin->params->toString() , $PluginId ) ;
			
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
		public static function _removeScripts ( \PlgSystemStarcache &$Starcache ,  $doc )
		{
			
			
			
			
			$regex        = self::_prepExclude( (array) $Starcache->params->get( 'scripts', [] ) );
			$matched      = [];
			$regexinclude = $Starcache->params->get( 'include', false );
			
			
			foreach ( $regex as $r )
			{
				
				$match   = preg_grep( '/' . $r . '/', array_keys( $doc->_scripts ) );
				$matched = array_merge( $matched, $match );
				
			}#END FOREACH
			
			
			foreach ( $doc->_scripts as $src => $attribs )
			{
				
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
			
		}#END FUN
		
		
		private static function _prepExclude($a) {
			return array_values(array_map(function($i)
			{
				return $i->regex;
			}, $a));
		}#END FN
		
		
		
	}#END CLASS