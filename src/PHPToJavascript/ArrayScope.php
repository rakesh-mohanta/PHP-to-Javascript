<?php

namespace PHPToJavascript;

define('ARRAY_ELEMENT_START_MAGIC', '/*ARRAY_ELEMENT_START_MAGIC*/');


class ArrayScope extends CodeScope{

	var $parensCount = 0;
	var $keyCount = 0;

	var	$arrayElementStarted = false;

	var $doubleArrayUsed = FALSE;

	/**
	 * @param $variableName
	 * @param $isClassVariable - whether the variable was prefixed by $this
	 * @return mixed
	 *
	 * For a given variable name, try to find the variable in the current scope.
	 */
	function    getScopedVariableForScope($variableName, $isClassVariable) {
		//Array scopes don't contain variables.
		return NULL;
	}

	function	pushParens(){
		$this->parensCount += 1;
	}

	function	popParens(){
		$this->parensCount -= 1;
		if($this->parensCount <= 0){
			return TRUE;
		}
		return FALSE;
	}

	function getType(){
		return CODE_SCOPE_ARRAY;
	}

	function	getJS(){

		$js = parent::getJS();

		$firstOpenParens = strpos($js, "(");
		$lastCloseParens = strrpos($js, ")");

		if($firstOpenParens !== FALSE){
			$js = substr_replace($js, '{', $firstOpenParens, 1);
		}
		if($lastCloseParens !== FALSE){
			$js = substr_replace($js, '}', $lastCloseParens, 1);
		}

		return $js;
	}

	function	fixupArrayIndex(){
		$replace = '';

		if($this->doubleArrayUsed == false){
			$replace = "".$this->keyCount." : ";
			$this->keyCount++;
		}

		//Find the array element start position and replace it.
		for($x=count($this->jsElements) - 1 ; $x >= 0 ; $x--){
			if($this->jsElements[$x] == ARRAY_ELEMENT_START_MAGIC){
				$this->jsElements[$x] = $replace;
				break;
			}
		}

		$this->doubleArrayUsed = false;
		$this->arrayElementStarted = false;
	}

	//Contains hacks
	function	preStateMagic($name, $value){
		parent::preStateMagic($name, $value);

		if($this->arrayElementStarted == false){
			if ($name == 'T_LNUMBER' ||
				$name == 'T_VARIABLE' ||
				$name == 'T_CONSTANT_ENCAPSED_STRING'){
				$this->addJS(ARRAY_ELEMENT_START_MAGIC);
				$this->arrayElementStarted = true;
			}

			//TODO - this needs to happen when the double arrow is encountered.
//			if ($name == 'T_LNUMBER'){
//				// If someone is mixing automatic with numbered arrays, attempt to support it
//				// by continuing the automatic key after their numbered key
//				$this->keyCount = intval($value) + 1;
//			}
		}

		if($name == 'T_DOUBLE_ARROW'){
			$this->doubleArrayUsed = TRUE;
		}

		if ($name == ',' ||
			$name == ')'){
			//Array has ended.
			$this->fixupArrayIndex();

		}
	}

	//Contains hacks
	function	postStateMagic($name, $value){
		parent::postStateMagic($name, $value);
	}

}


?>