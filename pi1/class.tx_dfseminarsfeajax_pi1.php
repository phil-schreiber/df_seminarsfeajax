<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Philipp Schreiber <schreiber@denkfabrik-group.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

// require_once(PATH_tslib . 'class.tslib_pibase.php');

/**
 * Plugin 'seminarsfeajax' for the 'df_seminarsfeajax' extension.
 *
 * @author	Philipp Schreiber <schreiber@denkfabrik-group.com>
 * @package	TYPO3
 * @subpackage	tx_dfseminarsfeajax
 */
class tx_dfseminarsfeajax_pi1 extends tslib_pibase {
	public $prefixId      = 'tx_dfseminarsfeajax_pi1';		// Same as class name
	public $scriptRelPath = 'pi1/class.tx_dfseminarsfeajax_pi1.php';	// Path to this script relative to the extension dir.
	public $extKey        = 'df_seminarsfeajax';	// The extension key.
	public $pi_checkCHash = TRUE;
	
	/**
	 * The main method of the Plugin.
	 *
	 * @param string $content The Plugin content
	 * @param array $conf The Plugin configuration
	 * @return string The content that is displayed on the website
	 */
	public function main($content, array $conf) {
                $this->init($conf);
                $template['script']=$this->cObj->getSubpart($this->templateFile,'###SCRIPT###');
                if(is_array($this->seminarTypesArray)){
                    $seminarTypes=implode(',',$this->seminarTypesArray);
                }else{
                    $seminarTypes=$this->seminarTypesArray;
                }
                
                if(is_array($this->seminarCategoriesArray)){
                    $categoryTypes="&seminarcategories=".implode(',',$this->seminarCategoriesArray);
                }else{
                    
                    $categoryTypes=$this->seminarCategoriesArray!='' ? "&seminarcategories=".$this->seminarCategoriesArray :$this->seminarCategoriesArray;
                }
                $topics=$this->getTopics($seminarTypes);
                $categories=$this->getCategories($seminarTypes);
                $scriptMarkerArray=array(
                  '###FE_ID###' =>$GLOBALS['TSFE']->id,
                    '###CONFIRMED-ONLY###'=>isset($this->conf['showConfirmedOnly']) ? $this->conf['showConfirmedOnly']:0,
                    '###SEMINAR_TYPES###'=>$seminarTypes,
                    '###SEMINAR_CATEGORIES###'=>$categoryTypes,
                    '###SHOWTOPCATS###'=>count($topics) >1 ? 1 : 0
                );
                $script=$this->cObj->substituteMarkerArrayCached($template['script'],$scriptMarkerArray,array(),array());
                $GLOBALS['TSFE']->additionalHeaderData[$this->pObj->prefixId] = $script;
                
		$template['total']=$this->cObj->getSubpart($this->templateFile,'###LIST_HEADER###');
                $template['selector']=$this->cObj->getSubpart($this->templateFile,'###SELECTOR_WIDGET###');
                $template['topics']=$this->cObj->getSubpart($template['selector'],'###TOPICS###');
                $template['topics2']=$this->cObj->getSubpart($template['selector'],'###TOPICS2###');
                 
                $template['categories']=$this->cObj->getSubpart($template['topics'],'###CATEGORIES###');
                $template['cities']=$this->cObj->getSubpart($template['selector'],'###CITIES###');
                $template['years']=$this->cObj->getSubpart($template['selector'],'###YEARS###');
                
                
                
                foreach($topics as $typeId => $topicTitle){
                    $cats='';
                    foreach($categories[$typeId] as $key => $cData){
                        $markerArray=array(
                          '###CATEGORY-ID###'  =>$cData['cid'],
                            '###CATEGORY###'=>$cData['ctitle']
                        );
                        
                        $cats.=$this->cObj->substituteMarkerArrayCached($template['categories'],$markerArray,array(),array());
                    }
                    $markerArray=array(
                     '###TOPIC###' => $topicTitle,
                        '###TOPIC-ID###'=>$typeId
                    );
                    $subpartTopicsArray['###CATEGORIES###']=$cats;
                    $topics.=$this->cObj->substituteMarkerArrayCached($template['topics'],$markerArray,$subpartTopicsArray,array());
                    $topics2.=$this->cObj->substituteMarkerArrayCached($template['topics2'],$markerArray,array(),array());
                }
                
                $currentYear=date("Y");
                for($i=$currentYear; $i<=($currentYear+1);$i++){
                    $markerArray=array(
                      '###YEAR###' => $i
                    );
                    $years.=$this->cObj->substituteMarkerArrayCached($template['years'],$markerArray,array(),array());
                }
                
                $cityData=$this->getCities($seminarTypes);
                foreach($cityData as $cid => $cTitle){
                    $markerArray=array(
                        '###CITY-ID###'=>$cid,
                        '###CITY###'=>$cTitle
                    );
                    $cities.=$this->cObj->substituteMarkerArrayCached($template['cities'],$markerArray,array(),array());
                }
                $subpartArray=array(
                    '###TOPICS###'=>$topics,
                    '###TOPICS2###'=>$topics2,
                    '###YEARS###'=>$years,
                    '###CITIES###'=>$cities
                );
                $selector=$this->cObj->substituteMarkerArrayCached($template['selector'],array(),$subpartArray,array());
                
                $markerArray=array(
                    '###SELECTOR###'=>$selector
                );
                
                $content=$this->cObj->substituteMarkerArrayCached($template['total'],$markerArray,array(),array());
                
                
                
	
		return $this->pi_wrapInBaseClass($content);
	}
        private function getCities($seminarTypes){
			$resultArray=array();
			
            $query=$GLOBALS['TYPO3_DB']->sql_query(
                    "SELECT sites.uid AS siteId, sites.title AS stitle FROM tx_seminars_sites AS sites LEFT JOIN tx_seminars_seminars_place_mm ON sites.uid = tx_seminars_seminars_place_mm.uid_foreign LEFT JOIN tx_seminars_seminars AS seminars ON seminars.uid=tx_seminars_seminars_place_mm.uid_local LEFT JOIN tx_seminars_seminars AS typetable ON seminars.topic=typetable.uid WHERE typetable.event_type IN (".$seminarTypes.") AND seminars.begin_date > ".time()." GROUP BY sites.uid ORDER BY sites.title ASC"
             );
            
            while($queryRow=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($query)){
                $resultArray[$queryRow['siteId']]=$queryRow['stitle'];
            }
            return $resultArray;
            
        }
        private function getTopics($seminarTypes){
			
            $query=$GLOBALS['TYPO3_DB']->exec_SELECTquery(
                    'uid,title',
                    'tx_seminars_event_types',
                    'deleted=0 AND uid IN ('.$seminarTypes.')'
             );
            while($queryRow=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($query)){
                $title=  str_replace("®", "", $queryRow['title']);
                $resultArray[$queryRow['uid']]=$title;
            }
            
            return $resultArray;
        }
        
        private function getCategories($seminarTypes){
            $query=$GLOBALS['TYPO3_DB']->sql_query(
             "SELECT categories.title AS ctitle, categories.uid AS cid, seminars.event_type AS etype FROM tx_seminars_categories AS categories LEFT JOIN tx_seminars_seminars_categories_mm ON categories.uid = tx_seminars_seminars_categories_mm.uid_foreign LEFT JOIN tx_seminars_seminars AS seminars ON seminars.uid=tx_seminars_seminars_categories_mm.uid_local WHERE object_type=1 AND seminars.event_type IN (".$seminarTypes.") GROUP BY categories.uid"
                    );
            
            while($queryRow =$GLOBALS['TYPO3_DB']->sql_fetch_assoc($query)){
                $resultArray[$queryRow['etype']][]=$queryRow;
            }
            
            return $resultArray;
        }
        
         private function init($conf){
             $this->conf = $conf;
            $this->pi_setPiVarDefaults();
            $this->pi_loadLL();
            $this->pi_USER_INT_obj = 1;	
            $this->pi_initPIflexForm();
            $this->path=$this->pi_getPageLink($GLOBALS['TSFE']->id);
            if ($this->conf['templateFile']) {
		$this->conf['templateFile']=$this->conf['templateFile'];	                
            } else if($this->pi_getFFvalue($this->cObj->data['pi_flexform'],'templateFile', 'sDEF')){
		$this->conf['templateFile'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'],'templateFile', 'sDEF');	
            } else {
		$this->conf['templateFile'] = 'typo3conf/ext/df_seminarsfeajax/res/template.html';
            }
            
           $this->seminarTypesArray=$this->pi_getFFvalue($this->cObj->data['pi_flexform'],'limitListViewToEventTypes', 's_listView');
		   
           $this->seminarCategoriesArray=$this->pi_getFFvalue($this->cObj->data['pi_flexform'],'limitListViewToCategories', 's_listView');
           
            
            $this->templateFile=$this->cObj->fileResource($this->conf['templateFile']);
            
            
        }
}



if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/df_seminarsfeajax/pi1/class.tx_dfseminarsfeajax_pi1.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/df_seminarsfeajax/pi1/class.tx_dfseminarsfeajax_pi1.php']);
}

?>