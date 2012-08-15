<?php

/*
********************************************************
TinyButStrong Plug-in: HTML stuffs
Version 1.00, on 2006-05-02, by Skrol29
********************************************************
*/

define('TBS_HTML','clsTbsPlugInHtml');
$GLOBALS['_TBS_AutoInstallPlugIns'][] = TBS_HTML; // Auto-install

class clsTbsPlugInHtml {

	function OnInstall() {
		return array('OnOperation');
	}

	function OnOperation($FieldName,&$Value,&$PrmLst,&$Source,&$PosBeg,&$PosEnd,&$Loc) {
		if ($PrmLst['ope']!=='html') return;
		if (isset($PrmLst['select'])) {
			$Source = substr_replace($Source,'',$PosBeg,$PosEnd-$PosBeg+1); // We delete the current TBS tag
			tbs_Html_MergeItems($Source,$Value,$PrmLst,$PosBeg);
			return false; // Return false to avoid TBS merging the current field
		} elseif (isset($PrmLst['look'])) {
			if (tbs_Html_IsHtml($Value)) {
				$PrmLst['look'] = '1';
				$Loc->ConvMode = false; // no conversion
			} else {
				$PrmLst['look'] = '0';
				$Loc->ConvMode = 1; // conversion to HTML
			}
		}
	}

}

function tbs_Html_InsertAttribute(&$Txt,&$Attr,$Pos) {
	// Check for XHTML end characters
	if ($Txt[$Pos-1]==='/') {
		$Pos--;
		if ($Txt[$Pos-1]===' ') $Pos--;
	}
	// Insert the parameter
	$Txt = substr_replace($Txt,$Attr,$Pos,0);
}

function tbs_Html_MergeItems(&$Txt,&$Value,&$PrmLst,&$PosBeg) {
// Select items of a list, or radio or check buttons.

	if ($PrmLst['select']===true) { // Means set with no value
		$IsList = true;
		$MainTag = 'SELECT';
		$ItemTag = 'OPTION';
		$ItemPrm = 'selected';
	} else {
		$IsList = false;
		$MainTag = 'FORM';
		$ItemTag = 'INPUT';
		$ItemPrm = 'checked';
	}
	$IsArray = is_array($Value);
	if (isset($PrmLst['selbounds'])) $MainTag = $PrmLst['selbounds'];
	$ItemPrmZ = ' '.$ItemPrm.'="'.$ItemPrm.'"';

	$TagO = tbs_Html_FindTag($Txt,$MainTag,true,$PosBeg-1,false,0,false);

	if ($TagO!==false) {

		$TagC = tbs_Html_FindTag($Txt,$MainTag,false,$PosBeg,true,0,false);
		if ($TagC!==false) {

			// We get the main block without the main tags
			$MainSrc = substr($Txt,$TagO->PosEnd+1,$TagC->PosBeg - $TagO->PosEnd -1);

			if ($IsList) {
				// Information about the item that was used for the TBS field
				$Item0Beg = $PosBeg - ($TagO->PosEnd+1);
				$Item0Src = '';
				$Item0Ok = false;
			}

			// Now, we going to scan all of the item tags
			$Pos = 0;
			$SelNbr = 0;
			while ($ItemLoc = tbs_Html_FindTag($MainSrc,$ItemTag,true,$Pos,true,0,true)) {

				// we get the value of the item
				$ItemValue = false;

				if ($IsList) {
					// Look for the end of the item
					$OptCPos = strpos($MainSrc,'<',$ItemLoc->PosEnd+1);
					if ($OptCPos===false) $OptCPos = strlen($MainSrc);
					if (($Item0Ok===false) and ($ItemLoc->PosBeg<$Item0Beg) and ($Item0Beg<=$OptCPos)) {
						// If it's the original item, we save it and take it off.
						if (($OptCPos+1<strlen($MainSrc)) and ($MainSrc[$OptCPos+1]==='/')) {
							$OptCPos = strpos($MainSrc,'>',$OptCPos);
							if ($OptCPos===false) {
								$OptCPos = strlen($MainSrc);
							} else {
								$OptCPos++;
							}
						}
						$Item0Src = substr($MainSrc,$ItemLoc->PosBeg,$OptCPos-$ItemLoc->PosBeg);
						$MainSrc = substr_replace($MainSrc,'',$ItemLoc->PosBeg,strlen($Item0Src));
						if (!isset($ItemLoc->PrmLst[$ItemPrm])) tbs_Html_InsertAttribute($Item0Src,$ItemPrmZ,$ItemLoc->PosEnd-$ItemLoc->PosBeg);
						$OptCPos = min($ItemLoc->PosBeg,strlen($MainSrc)-1);
						$Select = false;
						$Item0Ok = true;
					} else {
						if (isset($ItemLoc->PrmLst['value'])) {
							$ItemValue = $ItemLoc->PrmLst['value'];
						} else { // The value of the option is its caption.
							$ItemValue = substr($MainSrc,$ItemLoc->PosEnd+1,$OptCPos - $ItemLoc->PosEnd - 1);
							$ItemValue = str_replace(chr(9),' ',$ItemValue);
							$ItemValue = str_replace(chr(10),' ',$ItemValue);
							$ItemValue = str_replace(chr(13),' ',$ItemValue);
							$ItemValue = trim($ItemValue);
						}
					}
					$Pos = $OptCPos;
				} else {
					if ((isset($ItemLoc->PrmLst['name'])) and (isset($ItemLoc->PrmLst['value']))) {
						if (strcasecmp($PrmLst['select'],$ItemLoc->PrmLst['name'])==0) {
							$ItemValue = $ItemLoc->PrmLst['value'];
						}
					}
					$Pos = $ItemLoc->PosEnd;
				}

				if ($ItemValue!==false) {
					// we look if we select the item
					$Select = false;
					if ($IsArray) {
						if (array_search($ItemValue,$Value,false)!==false) $Select = true;
					} else {
						if (strcasecmp($ItemValue,$Value)==0) {
							if ($SelNbr==0) $Select = true;
						}
					}
					// Select the item
					if ($Select) {
						if (!isset($ItemLoc->PrmLst[$ItemPrm])) {
							tbs_Html_InsertAttribute($MainSrc,$ItemPrmZ,$ItemLoc->PosEnd);
							$Pos = $Pos + strlen($ItemPrmZ);
							if ($IsList and ($ItemLoc->PosBeg<$Item0Beg)) $Item0Beg = $Item0Beg + strlen($ItemPrmZ);
						}
						$SelNbr++;
					}
				}

			} //--> while ($ItemLoc = ... ) {

			if ($IsList) {
				// Add the original item if it's not found
				if ((!$IsArray) and ($SelNbr==0)) $MainSrc = $MainSrc.$Item0Src;
			}

			$Txt = substr_replace($Txt,$MainSrc,$TagO->PosEnd+1,$TagC->PosBeg-$TagO->PosEnd-1);

		} //--> if ($TagC!==false) {
	} //--> if ($TagO!==false) {


}

function tbs_Html_IsHtml(&$Txt) {
// This function returns True if the text seems to have some HTML tags.

	// Search for opening and closing tags
	$pos = strpos($Txt,'<');
	if ( ($pos!==false) and ($pos<strlen($Txt)-1) ) {
		$pos = strpos($Txt,'>',$pos + 1);
		if ( ($pos!==false) and ($pos<strlen($Txt)-1) ) {
			$pos = strpos($Txt,'</',$pos + 1);
			if ( ($pos!==false)and ($pos<strlen($Txt)-1) ) {
				$pos = strpos($Txt,'>',$pos + 1);
				if ($pos!==false) return true;
			}
		}
	}

	// Search for special char
	$pos = strpos($Txt,'&');
	if ( ($pos!==false) and ($pos<strlen($Txt)-1) ) {
		$pos2 = strpos($Txt,';',$pos+1);
		if ($pos2!==false) {
			$x = substr($Txt,$pos+1,$pos2-$pos-1); // We extract the found text between the couple of tags
			if (strlen($x)<=10) {
				if (strpos($x,' ')===false) return true;
			}
		}
	}

	// Look for a simple tag
	$Loc1 = tbs_Html_FindTag($Txt,'BR',true,0,true,0,false); // line break
	if ($Loc1!==false) return true;
	$Loc1 = tbs_Html_FindTag($Txt,'HR',true,0,true,0,false); // horizontal line
	if ($Loc1!==false) return true;

	return false;

}

?>