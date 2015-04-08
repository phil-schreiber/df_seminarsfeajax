<?php

require_once(PATH_tslib.'class.tslib_pibase.php');
class tx_seminarsfeajax_ajaxcontroller extends tslib_pibase{
    public $cObj;
    public $prefixId      = 'tx_dfxtooladmin_pi1';		// Same as class name
    public $extKey        = 'df_xtooladmin';	// The extension key.
    public $scriptRelPath = 'class.tx_seminarsfeajax_ajaxcontroller.php';
    
    public function main() {        
        $this->cObj = t3lib_div::makeInstance('tslib_cObj');
        $this->initTSFE();        
        $this->init();
        
    }
     private function init(){
             
         
            $this->pi_setPiVarDefaults();
            $this->pi_loadLL();
            $this->pi_USER_INT_obj = 1;	
            $this->pi_initPIflexForm();
            $seminarTypes=explode(',',$_GET['seminartypes']);
            foreach($seminarTypes as $seminarType){
                $cleanTypes.=intval($seminarType).',';
            }
            
            $cleanCats='';
            if($_GET['seminarcategories']){
            $seminarCategories=explode(',',$_GET['seminarcategories']);
            
            
            
            foreach($seminarCategories as $seminarCat){
                $cleanCats.=intval($seminarCat).',';
            }
            $cleanCats=substr($cleanCats,0,-1);
            }
            $cleanTypes=substr($cleanTypes,0,-1);
            
            $data=$this->getData($cleanTypes,$cleanCats);
             
            
            echo(json_encode($data));
            
        }
        
    private function getData($cleanTypes,$cleanCats){
        $aColumns = array( 'title', 'description', 'date', 'city', 'price' );
        $aColumnsSelect=array( 'seminars.title AS title', 'typetable.teaser AS description', 'seminars.begin_date AS date', 'tx_seminars_sites.title AS city', 'typetable.price_regular AS price, seminars.end_date AS enddate' );
        $aColumnsFilter=array( 'seminars.title', 'typetable.teaser', 'seminars.begin_date', 'tx_seminars_sites.title', 'typetable.price_regular');
	$time=time();
	/* Indexed column (used for fast and accurate table cardinality) */
	$sIndexColumn = "seminars.uid";
	
	/* DB table to use */
	$sTable = "tx_seminars_seminars AS seminars LEFT JOIN tx_seminars_seminars_place_mm ON seminars.uid = tx_seminars_seminars_place_mm.uid_local LEFT JOIN tx_seminars_sites ON tx_seminars_sites.uid = tx_seminars_seminars_place_mm.uid_foreign LEFT JOIN tx_seminars_seminars AS typetable ON seminars.topic=typetable.uid LEFT JOIN tx_seminars_seminars_categories_mm ON typetable.uid=tx_seminars_seminars_categories_mm.uid_local LEFT JOIN tx_seminars_categories AS categories ON categories.uid=tx_seminars_seminars_categories_mm.uid_foreign";
        /* 
	 * Paging
	 */
	$sLimit = "";
	if ( isset( $_POST['iDisplayStart'] ) && $_POST['iDisplayLength'] != '-1' )
	{
		$sLimit = "LIMIT ".intval( $_POST['iDisplayStart'] ).", ".
			intval( $_POST['iDisplayLength'] );
	}
	
	
	/*
	 * Ordering
	 */
	$sOrder = "";
	if ( isset( $_POST['iSortCol_0'] ) )
	{
		/*$sOrder = "ORDER BY  ";*/
		for ( $i=0 ; $i<intval( $_POST['iSortingCols'] ) ; $i++ )
		{
			if ( $_POST[ 'bSortable_'.intval($_POST['iSortCol_'.$i]) ] == "true" )
			{
				$sOrder .= "`".$aColumns[ intval( $_POST['iSortCol_'.$i] ) ]."` ".
					($_POST['sSortDir_'.$i]==='asc' ? 'asc' : 'desc') .", ";
			}
		}
		
		$sOrder = substr_replace( $sOrder, "", -2 );
		if ( $sOrder == "" )
		{
			$sOrder = "ORDER BY seminars.begin_date asc";
                }else{
                    $sOrder='ORDER BY seminars.begin_date ASC,'.$sOrder;
                }
	}
	
	
	/* 
	 * Filtering
	 * NOTE this does not match the built-in DataTables filtering which does it
	 * word by word on any field. It's possible to do here, but concerned about efficiency
	 * on very large tables, and MySQL's regex functionality is very limited
	 */
        if(!empty($_POST['topics'])){
            $cleanTypes='';
            foreach($_POST['topics'] as $topic){
                $cleanTypes.=intval($topic).',';
            }
            $cleanTypes=substr($cleanTypes,0,-1);
        }
        
	$sWhere = "WHERE typetable.event_type IN (".$cleanTypes.") AND seminars.begin_date >= ".$time;
	if ( isset($_POST['sSearch']) && $_POST['sSearch'] != "" )
	{
		$sWhere .= " AND (";
		for ( $i=0 ; $i<count($aColumns) ; $i++ )
		{
			$sWhere .= "".$aColumnsFilter[$i]." LIKE '%".mysql_real_escape_string( $_POST['sSearch'] )."%' OR ";
		}
		$sWhere = substr_replace( $sWhere, "", -3 );
		$sWhere .= ')';
	}
	
	/* Individual column filtering */
	for ( $i=0 ; $i<count($aColumns) ; $i++ )
	{
		if ( isset($_POST['bSearchable_'.$i]) && $_POST['bSearchable_'.$i] == "true" && $_POST['sSearch_'.$i] != '' )
		{
			if ( $sWhere == "" )
			{
				$sWhere = "WHERE ";
			}
			else
			{
				$sWhere .= " AND ";
			}
                        
			$sWhere .= "".$aColumnsFilter[$i]." LIKE '%".mysql_real_escape_string($_POST['sSearch_'.$i])."%' ";
		}
	}
        
        if(isset($_POST['category']) || $cleanCats !=''){
            "" ? $sWhere="WHERE " : $sWhere .= " AND ";
            if(isset($_POST['category'])){
            $catCleanArray=array();
            
            foreach($_POST['category'] as $key => $value){
                $catCleanArray[]=intval($value);
            }
            $cleanCats=implode(',',$catCleanArray);
            }
            $sWhere.="categories.uid IN (".$cleanCats.")";    
        }
        
        if(isset($_POST['cities'])){
            $catCleanArray=array();
            "" ? $sWhere="WHERE " : $sWhere .= " AND ";
            foreach($_POST['cities'] as $key => $value){
                $catCleanArray[]=intval($value);
            }
            $cities=implode(',',$catCleanArray);
            $sWhere.="tx_seminars_sites.uid IN (".$cities.")";
            
        }
        
         if(isset($_POST['month']) && $_POST['month']!=''){
            "" ? $sWhere="WHERE " : $sWhere .= " AND ";
            
            $sWhere.="MONTH(FROM_UNIXTIME(seminars.begin_date)) = ".intval($_POST['month']);
            
        }
        
        if(isset($_POST['year']) && $_POST['year']!=''){
            "" ? $sWhere="WHERE " : $sWhere .= " AND ";
            
            $sWhere.="YEAR(FROM_UNIXTIME(seminars.begin_date)) = ".intval($_POST['year']);
            
        }
        
        if(isset($_POST['showconfirmedonly']) && $_POST['showconfirmedonly']==1){
             "" ? $sWhere="WHERE " : $sWhere .= " AND ";
             $sWhere.=" seminars.cancelled = 2";
        }
	
	
	/*
	 * SQL queries
	 * Get data to display
	 */
	$sQuery = "
		SELECT SQL_CALC_FOUND_ROWS ".str_replace(" , ", " ", implode(", ", $aColumnsSelect)).", seminars.uid AS sid, categories.title AS ctitle, seminars.cancelled AS guaranteed
		FROM  $sTable 
		$sWhere
		$sOrder
		$sLimit
		";
        
	$rResult = $GLOBALS['TYPO3_DB']->sql_query( $sQuery);
	
	/* Data set length after filtering */
	$sQuery = "
		SELECT FOUND_ROWS()
	";
	$rResultFilterTotal = $GLOBALS['TYPO3_DB']->sql_query( $sQuery);
	$aResultFilterTotal = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($rResultFilterTotal);
        
	$iFilteredTotal = $aResultFilterTotal['FOUND_ROWS()'];
	
	/* Total data set length */
	$sQuery = "
		SELECT COUNT(".$sIndexColumn.")
		FROM   $sTable
                WHERE typetable.event_type IN ($cleanTypes) AND seminars.begin_date >= $time    
	";
	$rResultTotal = $GLOBALS['TYPO3_DB']->sql_query( $sQuery);        
	$aResultTotal = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($rResultTotal);
        
	$iTotal = $aResultTotal['COUNT(seminars.uid)'];
	
	
	/*
	 * Output
	 */
	$output = array(
		"sEcho" => intval($_POST['sEcho']),
		"iTotalRecords" => $iTotal,
		"iTotalDisplayRecords" => $iFilteredTotal,
		"aaData" => array()
	);
	$seminarsArray=array();
        setlocale(LC_MONETARY, 'de_DE');
	while ( $aRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc( $rResult ) )
	{
            
            if(!in_array($aRow['sid'], $seminarsArray)){
                array_push($seminarsArray,$aRow['sid']);
		$row = array();
		for ( $i=0 ; $i<count($aColumns) ; $i++ )
		{
			if ( $aColumns[$i] == "title" )
			{
				/* Special output formatting for 'version' column */
                                $special='';
                                if($aRow['guaranteed']==2){
                                   $special= ' <img src="fileadmin/templates/img/icons/garantie.png" alt="Garantierter Termin" title="Garantierter Termin" />';
                                }
                                
				$row[] = '<a href="schulungen/detailansicht/?tx_seminars_pi1%5BshowUid%5D='.$aRow[ 'sid' ].'">'.$aRow[ $aColumns[$i] ].'</a>'.$special;
                                
			}
                        else if($aColumns[$i] == 'date'){
                            $row[] = date('d.m.Y',$aRow[ $aColumns[$i] ]).' - '.date('d.m.Y',$aRow['enddate']);
                        }
                        else if($aColumns[$i] == 'price'){
                            
                            $row[]='€ '.money_format('%!.2n', $aRow[ $aColumns[$i] ]);
                            /*$price=substr($aRow[ $aColumns[$i] ],0,-1);
                            $row[]='€ '.str_replace('.', ',', $price);*/
                        }
			else if ( $aColumns[$i] != ' ' )
			{
				/* General output */
				$row[] = $aRow[ $aColumns[$i] ];
			}
		}
            
		$output['aaData'][] = $row;
             }
	}
	
	return  $output;
        
    }
    
    protected function initTSFE() { 
        $GLOBALS['TSFE'] = t3lib_div::makeInstance('tslib_fe', $GLOBALS['TYPO3_CONF_VARS'], (int)t3lib_div::_GP('id'), (int)t3lib_div::_GP('type'));
        $GLOBALS['TSFE']->connectToDB(); 
        $GLOBALS['TSFE']->initFEuser(); 
        
        $GLOBALS['TSFE']->checkAlternativeIdMethods(); 
        $GLOBALS['TSFE']->determineId(); 
        $GLOBALS['TSFE']->getCompressedTCarray(); 
        $GLOBALS['TSFE']->initTemplate(); 
        $GLOBALS['TSFE']->getConfigArray(); 

       
    } 
        
    
    
}    

$output = new tx_seminarsfeajax_ajaxcontroller();
echo $output->main();
?>
