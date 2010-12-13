<?php

/* Versionning
Skrol29, 2010-12-13: add the isBug() feature
*/

define('TBS_TEST_NEVERFIXEDBUG','<99');

// override unit test case class to simplify tinyButStrong test cases
class TBSUnitTestCase extends UnitTestCase {

	/**
	 * Test TBS class with one function.
	 * @param string $source         source of template
	 * @param array $vars            associative array of name/value to pass to MergeField
	 * @param string $result         merge result to compare
	 * @param string $message        message to display (optional)
	 * @param string $bugOnVersion   false, or the range where the feature is supposed to fail. Example: '<3.5.6'
	 * @return boolean               True on pass
	 */
	function assertEqualMergeFieldStrings($source, $vars = null, $result, $message='%s', $bugOnVersion=false) {
		$tbs = new clsTinyButStrong;
		$tbs->Source = $source;
		if (is_array($vars))
			foreach ($vars as $name => $value)
				$tbs->MergeField($name, $value);
		$tbs->Show(TBS_NOTHING);
		if ($this->isBug($tbs->Version,$bugOnVersion)) {
			return $this->assertNotEqual($tbs->Source, $result, $message);
		} else {
			return $this->assertEqual($tbs->Source, $result, $message);
		}
	}

	/**
	 * Test TBS class with one function.
	 * @param string $source       source of template
	 * @param array $vars          associative array of name/value to pass to MergeBlock
	 * @param string $result       merge result to compare
	 * @param string $message      message to display (optional)
	 * @return boolean             True on pass
	 */
	function assertEqualMergeBlockStrings($source, $vars = null, $result, $message='%s') {
		$tbs = new clsTinyButStrong;
		$tbs->Source = $source;
		if (is_array($vars))
			foreach ($vars as $name => $value)
				$tbs->MergeBlock($name, $value);
		$tbs->Show(TBS_NOTHING);
		return $this->assertEqual($tbs->Source, $result, $message);
	}


	/**
	 * Returns directory of HTML files to compare.
	 */
	function getTemplateDir() {
		return dirname(dirname(__FILE__)).'/template/';
	}

	/**
	 * Test TBS class with one function.
	 * @param string $sourceFilename  fine name of template source
	 * @param array $vars             associative array of name/value to pass to MergeBlock
	 * @param string $resultFilename  file name of merge result to compare
	 * @param string $message         message to display (optional)
	 * @return boolean                True on pass
	 */
	function assertEqualMergeFieldFiles($sourceFilename, $vars = null, $resultFilename, $message='%s') {
		$tbs = new clsTinyButStrong;
		$tbs->LoadTemplate($this->getTemplateDir().$sourceFilename);
		if (is_array($vars))
			foreach ($vars as $name => $value)
				$tbs->MergeField($name, $value);
		$tbs->Show(TBS_NOTHING);
		return $this->assertEqual($tbs->Source, file_get_contents($this->getTemplateDir().$resultFilename), $message);
	}

	/**
	 * Test TBS class with one function.
	 * @param string $sourceFilename  fine name of template source
	 * @param array $vars             associative array of name/value to pass to MergeBlock
	 * @param string $resultFilename  file name of merge result to compare
	 * @param string $message         message to display (optional)
	 * @return boolean                True on pass
	 */
	function assertEqualMergeBlockFiles($sourceFilename, $vars = null, $resultFilename, $message='%s') {
		$tbs = new clsTinyButStrong;
		$tbs->LoadTemplate($this->getTemplateDir().$sourceFilename);
		if (is_array($vars))
			foreach ($vars as $name => $value)
				$tbs->MergeBlock($name, $value);
		$tbs->Show(TBS_NOTHING);
		return $this->assertEqual($tbs->Source, file_get_contents($this->getTemplateDir().$resultFilename), $message);
	}
	
	/**
	 * return true if the feature is supposed to fail for the current TBS version .
	 * @param string $currVersion  the current version of TBS
	 * @param string $bugOnVersion false, or the range where the feature is supposed to fail. Example: '<3.5.6'
	 * @return boolean             True if it's a bug for the current TBS version
	 */
	function isBug($currVersion, $bugOnVersion) {
		if ($bugOnVersion===false) {
			return false;
		} else {
			$p = ( ($bugOnVersion[1]=='=') || ($bugOnVersion[1]=='>') ) ? 2 : 1;
			$ope = substr($bugOnVersion, 0, $p);
			$version = substr($bugOnVersion, $p);
			return version_compare($currVersion,$version,$ope);
		}
	}
	
}

?>