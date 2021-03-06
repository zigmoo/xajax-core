<?php

namespace Xajax\Request;

use Xajax\Xajax;

/*
	File: Request.php

	Contains the Request class

	Title: Request class

	Please see <copyright.php> for a detailed description, copyright
	and license information.
*/

/*
	@package Xajax
	@version $Id: Request.php 362 2007-05-29 15:32:24Z calltoconstruct $
	@copyright Copyright (c) 2005-2007 by Jared White & J. Max Wilson
	@copyright Copyright (c) 2008-2010 by Joseph Woolley, Steffen Konerow, Jared White  & J. Max Wilson
	@license http://www.xajaxproject.org/bsd_license.txt BSD License
*/

/*
	Class: Request
	
	Used to store and generate the client script necessary to invoke
	a xajax request from the browser to the server script.
	
	This object is typically generated by the <xajax->register> method
	and can be used to quickly generate the javascript that is used
	to initiate a xajax request to the registered function, object, event
	or other xajax call.
*/
class Request
{
	use \Xajax\Utils\ContainerTrait;

	/*
		String: sName
		
		The name of the function.
	*/
	private $sName;
	
	/*
		String: sType
		
		The type of the request. Can be one of callable or event.
	*/
	private $sType;
	
	/*
		String: sQuoteCharacter
		
		A string containing either a single or a double quote character
		that will be used during the generation of the javascript for
		this function.  This can be set prior to calling <Request->getScript>
	*/
	private $sQuoteCharacter;
	
	/*
		Array: aParameters
	
		An array of parameters that will be used to populate the argument list
		for this function when the javascript is output in <Request->getScript>	
	*/
	private $aParameters;
	
	/*
		Integer: nPageNumberIndex
	
		The index of the Xajax::PAGE_NUMBER parameter in the array.
	*/
	private $nPageNumberIndex;
	
	/*
		Function: Request
		
		Construct and initialize this request.
		
		sName - (string):  The name of this request.
	*/
	public function __construct($sName, $sType)
	{
		$this->aParameters = array();
		$this->nPageNumberIndex = -1;
		$this->sQuoteCharacter = '"';
		$this->sName = $sName;
		$this->sType = $sType;
	}
	
	/*
		Function: useSingleQuote
		
		Call this to instruct the request to use single quotes when generating
		the javascript.
	*/
	public function useSingleQuote()
	{
		$this->sQuoteCharacter = "'";
	}
	
	/*
		Function: useDoubleQuote
		
		Call this to instruct the request to use double quotes while generating
		the javascript.
	*/
	public function useDoubleQuote()
	{
		$this->sQuoteCharacter = '"';
	}
	
	/*
		Function: clearParameters
		
		Clears the parameter list associated with this request.
	*/
	public function clearParameters()
	{
		$this->aParameters = array();
	}
	
	/*
		Function: hasPageNumber
		
		Returns true if the request has a parameter of type Xajax::PAGE_NUMBER.
	*/
	public function hasPageNumber()
	{
		return ($this->nPageNumberIndex >= 0);
	}
	
	/*
		Function: setPageNumber
		
		Set the current value of the Xajax::PAGE_NUMBER parameter.
	*/
	public function setPageNumber($nPageNumber)
	{
		// Set the value of the Xajax::PAGE_NUMBER parameter
		$nPageNumber = intval($nPageNumber);
		if($this->nPageNumberIndex >= 0 && $nPageNumber > 0)
		{
			$this->aParameters[$this->nPageNumberIndex] = $nPageNumber;
		}
		return $this;
	}
	
	/*
		Function: addParameter
		
		Adds a parameter value to the parameter list for this request.
		
		sType - (string): The type of the value to be used.
		sValue - (string: The value to be used.
		
		See Also:
		See <Request->setParameter> for details.
	*/
	public function addParameter($sType, $sValue)
	{
		$this->setParameter(count($this->aParameters), $sType, $sValue);
	}
	
	/*
		Function: setParameter
		
		Sets a specific parameter value.
		
		Parameters:
		
		nParameter - (number): The index of the parameter to set
		sType - (string): The type of value
		sValue - (string): The value as it relates to the specified type
		
		Note:
		
		Types should be one of the following <Xajax::FORM_VALUES>, <Xajax::QUOTED_VALUE>, <Xajax::NUMERIC_VALUE>,
		<Xajax::JS_VALUE>, <Xajax::INPUT_VALUE>, <Xajax::CHECKED_VALUE>, <Xajax::PAGE_NUMBER>.  
		The value should be as follows:
			<Xajax::FORM_VALUES> - Use the ID of the form you want to process.
			<Xajax::QUOTED_VALUE> - The string data to be passed.
			<Xajax::JS_VALUE> - A string containing valid javascript (either a javascript
				variable name that will be in scope at the time of the call or a 
				javascript function call whose return value will become the parameter.
				
	*/
	public function setParameter($nParameter, $sType, $sValue)
	{
		switch($sType)
		{
		case Xajax::FORM_VALUES:
			$sFormID = $sValue;
			$this->aParameters[$nParameter] = "xajax.getFormValues(" . $this->sQuoteCharacter 
				. $sFormID . $this->sQuoteCharacter . ")";
			break;
		case Xajax::INPUT_VALUE:
			$sInputID = $sValue;
			$this->aParameters[$nParameter] =  "xajax.$("  . $this->sQuoteCharacter 
				. $sInputID . $this->sQuoteCharacter  . ").value";
			break;
		case Xajax::CHECKED_VALUE:
			$sCheckedID = $sValue;
			$this->aParameters[$nParameter] =  "xajax.$("  . $this->sQuoteCharacter 
				. $sCheckedID  . $this->sQuoteCharacter . ").checked";
			break;
		case Xajax::ELEMENT_INNERHTML:
			$sElementID = $sValue;
			$this->aParameters[$nParameter] = "xajax.$(" . $this->sQuoteCharacter 
				. $sElementID . $this->sQuoteCharacter . ").innerHTML";
			break;
		case Xajax::QUOTED_VALUE:
			$this->aParameters[$nParameter] = $this->sQuoteCharacter . addslashes($sValue) . $this->sQuoteCharacter;
			break;
		case Xajax::PAGE_NUMBER:
			$this->nPageNumberIndex = $nParameter;
			$this->aParameters[$nParameter] = $sValue;
			break;
		case Xajax::NUMERIC_VALUE:
		case Xajax::JS_VALUE:
			$this->aParameters[$nParameter] = $sValue;
			break;
		}
	}

	/*
		Function: getScript
		
		Parameters:

		Returns a string representation of the script output (javascript) from this request object.
	*/
	public function getScript()
	{
		$sXajaxPrefix = $this->getOption('core.prefix.' . $this->sType);
		return $sXajaxPrefix . $this->sName . '(' . implode(', ', $this->aParameters) . ')';
	}

	/*
		Function: getScript
		
		Parameters:

		Prints a string representation of the script output (javascript) from this request object.
	*/
	public function printScript()
	{
		print $this->getScript();
	}

	/*
		Function: __toString
		
		Parameters:

		Convert this request object to string.
	*/
	public function __toString()
	{
		return $this->getScript();
	}
}
