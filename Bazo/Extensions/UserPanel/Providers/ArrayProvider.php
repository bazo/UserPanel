<?php
namespace Bazo\Extensions\UserPanel\Providers;
/**
 * ArrayProvider
 *
 * @author Martin
 */
class ArrayProvider implements CredentialsProvider, \IteratorAggregate
{
	private
		/** @var array */	
		$array
	;
	
	function __construct($array)
	{
		$this->array = $array;
	}
	
	public function getIterator()
	{
		return new \ArrayIterator($this->array);
	}

}
