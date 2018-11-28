<?php
	namespace starcache\helpers ;
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
		
		}
		
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
		private function _removeScripts( $doc ) {
			
			$regex = $this->_prepExclude((array) $this->params->get('scripts', array()));
			$matched = array();
			$regexinclude = $this->params->get('include', false);
			
			
			
			foreach ($regex as $r){
				
				$match = preg_grep('/' . $r . '/', array_keys($doc->_scripts));
				$matched = array_merge($matched, $match);
				
			}#END FOREACH
			
			
			
			
			foreach ($doc->_scripts as $src => $attribs){
				
				if (!$regexinclude)
				{
					if (!in_array($src, $matched))
					{
						$this->_scripts[$src] = $attribs;
						unset($doc->_scripts[$src]);
					}#END IF
					continue;
				}#END IF
				
				if (in_array($src, $matched))
				{
					
					$attribs['defer'] = 1 ;
					$attribs['options']['defer'] = 1 ;
					
					
					$this->_scripts[$src] = $attribs;
					unset($doc->_scripts[$src]);
					
				}#END IF
				
			}#END FOREACH
			
		}#END FUN
		
		
		
		
		
	} 