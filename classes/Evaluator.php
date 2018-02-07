<?php
/*
 * Copyright (c) 2018 Tuukka Norri
 * This code is licensed under MIT license (see LICENSE for details).
 */

namespace Grav\Plugin\TwigCollectionFilterExtension;


class Evaluator
{
	// Check whether $arr has numeric keys only.
	private function isNumericArray($arr)
	{
		if (!is_array($arr))
			return false;
		
		return array_values($arr) === $arr;
	}
	
	
	// Return true iff each value in $a1 occurs in $a2 considering duplicate values. Use === for comparison without casting values.
	// None of the PHP array functions seemed to suit our needs.
	private function isArraySubset($a1, $a2)
	{
		if (! (count($a1) <= count($a2)))
			return false;
		
		// Resort to O(nm) comparisons since the arrays may contain values of different types.
		$a2copy = $a2;
		foreach ($a1 as $key1 => $val1)
		{
			$foundKey = null;
			foreach ($a2copy as $key2 => $val2)
			{
				if ($val1 === $val2)
				{
					$foundKey = $key2;
					goto found_key;
				}
			}
			
			// Did not find $val1 in $a2copy.
			return false;
			
		found_key:
			unset($a2copy[$foundKey]);
		}
		
		return true;
	}
	
	
	// Get a property value specified by $key.
	private static function getPropertyValueForKey($obj, $key)
	{
		// Check if the given object is an array.
		if (is_array($obj))
		{
			// If the given key exists, return the pointed value.
			if (array_key_exists($key, $obj))
				return [true, $obj[$key]];
			
			return [false, null];
		}
		
		// Check if the property is a method.
		if (method_exists($obj, $key))
			return [true, $obj->{$key}()];

		// Verify that $key is a property in $obj.
		if (!property_exists($obj, $key))
			return [false, null];
		
		// Return the value of the instance variable.
		return [true, $obj->{$key}];
	}
	
	
	// Get a property value specified by $keyPath.
	public static function getPropertyValue($rootObject, $keyPath)
	{
		// Split the key path.
		$keys = explode('.', $keyPath);
		
		$obj = $rootObject;
		foreach ($keys as $key)
		{
			list ($shouldContinue, $nextObj) = self::getPropertyValueForKey($obj, $key);
			if (!$shouldContinue)
				return [false, null];
			
			$obj = $nextObj;
		}
		
		return [true, $obj];
	}
	
	
	// Check an 'in' expression by fetching the value of the key from the object and comparing with the given literals.
	private function visitIn($obj, $predicateEnd)
	{
		if (2 != count($predicateEnd))
			throw new \InvalidArgumentException(sprintf("Expected 'in' predicate to have exactly least two arguments but got %d.", count($predicateEnd)));
		
		// Names refer to lhs ⊆ rhs.
		list ($lhsKeypath, $rhs) = $predicateEnd;
		if (!is_string($lhsKeypath))
			throw new \InvalidArgumentException(sprintf("Expected the first argument of 'in' predicate to be a string but got %s.", gettype($lhsKeypath)));
		
		// Allow lhs to be null in which case false is returned.
		list ($ok, $lhsValue) = self::getPropertyValue($obj, $lhsKeypath);
		if (!$ok || is_null($lhsValue))
			return false;
		
		// If lhs is not null, require it to be an array.
		if (!is_array($lhsValue))
			throw new \InvalidArgumentException(sprintf("Expected the first keypath argument of 'in' predicate to evaluate to an array but got %s.", gettype($lhsValue)));
		
		// Rhs may be either a keypath or an array.
		$rhsValue = $rhs;
		if (is_string($rhs))
		{
			// Same as with lhs.
			list ($ok, $rhsValue) = self::getPropertyValue($obj, $rhs);
			if (!$ok || is_null($rhsValue))
				return false;
			
			if (!is_array($rhsValue))
				throw new \InvalidArgumentException(sprintf("Expected the second keypath argument of 'in' predicate to evaluate to an array but got %s.", gettype($rhsValue)));
		}
		else if (!is_array($rhs))
		{
			throw new \InvalidArgumentException(sprintf("Expected the second argument of 'in' predicate to be either a string or an array but got %s.", gettype($rhs)));
		}
		
		return $this->isArraySubset($lhsValues, $rhsValues);
	}
	
	
	// Check a 'contains' expression by fetching the value of the key from the object and comparing with the given literal.
	private function visitContains($obj, $predicateEnd)
	{
		$count = count($predicateEnd);
		if (! (2 == $count || 3 == $count))
			throw new \InvalidArgumentException(sprintf("Expected 'contains' predicate to have exactly two or three arguments but got %d.", $count));
		
		// Names refer to lhs ⊆ rhs.
		$aggregateOp = 'all';
		$rhsKeypath = null;
		$lhsValues = null;
		if (2 == $count)
			list ($rhsKeypath, $lhsValues) = $predicateEnd;
		else if (3 == $count)
			list ($aggregateOp, $rhsKeyPath, $lhsValues) = $predicateEnd;
		
		if (!is_string($rhsKeypath))
			throw new \InvalidArgumentException(sprintf("Expected the first argument of 'contains' to be a string but got %s.", gettype($rhsKeypath)));
		
		if (!is_array($lhsValues))
			throw new \InvalidArgumentException(sprintf("Expected the second argument of 'contains' to be an array but got %s.", gettype($lhsValues)));
		
		// Allow rhs to be null in which case false is returned.
		list ($ok, $rhsValues) = self::getPropertyValue($obj, $rhsKeypath);
		if (!$ok || is_null($rhsValues))
			return false;
		
		if (!is_array($rhsValues))
			throw new \InvalidArgumentException(sprintf("Expected the keypath argument of 'contains' predicate to evaluate to an array but got %s.", gettype($rhsValues)));
		
		switch ($aggregateOp)
		{
			case 'all':
				return $this->isArraySubset($lhsValues, $rhsValues);

			case 'any':
			{
				foreach ($lhsValues as $val)
				{
					if (in_array($val, $rhsValues, true))
						return true;
				}
				
				return false;
			}
			
			default:
				throw new \InvalidArgumentException(sprintf("Unexpected aggregate operator '%s'.", count($aggregateOp)));
		}
	}
	
	
	// Check for null. Return true if getPropertyValue() returns [false, null].
	private function visitIsNull($obj, $predicateEnd)
	{
		$count = count($predicateEnd);
		if (1 != $count)
			throw new \InvalidArgumentException(sprintf("Expected 'is_null' predicate to have exactly one argument but got %d.", $count));
		
		$keypath = $predicateEnd[0];
		list ($ok, $objVal) = self::getPropertyValue($obj, $keypath);
		if (!$ok)
			return true;
		
		return is_null($objVal);
	}
	
	
	// Compare $val1 to $val2 with $op.
	private function compare($op, $val1, $val2)
	{
		switch ($op)
		{
			case '==':
				return $val1 == $val2;
			case '===':
				return $val1 === $val2;
			case '!=':
				return $val1 != $val2;
			case '!==':
				return $val1 !== $val2;
			case '<':
				return $val1 < $val2;
			case '>':
				return $val1 > $val2;
			case '<=':
				return $val1 <= $val2;
			case '>=':
				return $val1 >= $val2;
			default:
				throw new \InvalidArgumentException(sprintf("Unexpected comparison operator '%s'.", count($op)));
		}
	}
	
	
	// Evaluate the expression represented by an associative array with logical and.
	private function visitComparisonAssocAnd($obj, $op, $predicateEnd)
	{
		foreach ($predicateEnd as $assocExp)
		{
			foreach ($assocExp as $keypath => $val)
			{
				list ($ok, $objVal) = self::getPropertyValue($obj, $keypath);
				if (! ($ok && $this->compare($op, $val, $objVal)))
					return false;
			}
		}
		
		return true;
	}
	
	
	// Evaluate the expression represented by an associative array with logical or.
	private function visitComparisonAssocOr($obj, $op, $predicateEnd)
	{
		foreach ($predicateEnd as $assocExp)
		{
			foreach ($assocExp as $keypath => $val)
			{
				list ($ok, $objVal) = self::getPropertyValue($obj, $keypath);
				if ($ok && $this->compare($op, $val, $objVal))
					return true;
			}
		}
		
		return false;
	}
	
	
	// Check the expression in a comparison predicate.
	private function visitComparison($obj, $op, $predicateEnd)
	{
		// Check for two keypaths. Default to false if either does not exist.
		if (2 == count($predicateEnd) && is_string($predicateEnd[0]) && is_string($predicateEnd[1]))
		{
			list ($ok, $val1) = self::getPropertyValue($obj, $predicateEnd[0]);
			if (!$ok)
				return false;
			
			list ($ok, $val2) = self::getPropertyValue($obj, $predicateEnd[1]);
			if (!$ok)
				return false;
			
			return $this->compare($op, $val1, $val2);
		}
		
		// Treat the expressions as associative arrays.
		$logicalOp = 'and';
		if (is_string($predicateEnd[0]))
			$logicalOp = array_shift($predicateEnd);
		
		switch ($logicalOp)
		{
			case 'and':
				return $this->visitComparisonAssocAnd($obj, $op, $predicateEnd);
			
			case 'or':
				return $this->visitComparisonAssocOr($obj, $op, $predicateEnd);
			
			default:
				throw new \InvalidArgumentException(sprintf("Unexpected logical operator '%s'.", count($logicalOp)));
		}
	}
	
	
	// Check the expression in a 'not' predicate.
	private function visitNot($obj, $predicateEnd)
	{
		if (1 != count($predicateEnd))
			throw new \InvalidArgumentException(sprintf("Expected 'not' predicate to have exactly one argument but got %d.", count($predicateEnd)));
		
		return !$this->evaluate($obj, $predicateEnd[0]);
	}
	
	
	// Check each of the expressions in an 'or' predicate and return if one evaluates to true.
	private function visitOr($obj, $predicateEnd)
	{
		foreach ($predicateEnd as $exp)
		{
			if ($this->evaluate($obj, $exp))
				return true;
		}
		
		return false;
	}
	
	
	// Check each of the expressions in an 'and' predicate and return if one evaluates to false.
	private function visitAnd($obj, $predicateEnd)
	{
		foreach ($predicateEnd as $exp)
		{
			if (!$this->evaluate($obj, $exp))
				return false;
		}
		
		return true;
	}
	
	
	// Check the predicate type (and, or, not, in, …) and visit accordingly.
	private function visitNumericArray($obj, $pred)
	{
		if (! (1 < count($pred)))
			throw new \InvalidArgumentException(sprintf("Expected predicate to have more than one argument but got %d.", count($pred)));
		
		$op = $pred[0];
		$rem = array_slice($pred, 1);
		
		if ('and' == $op)
			return $this->visitAnd($obj, $rem);
		
		if ('or' == $op)
			return $this->visitOr($obj, $rem);
		
		if ('not' == $op)
			return $this->visitNot($obj, $rem);
		
		if ('in' == $op)
			return $this->visitIn($obj, $rem);
		
		if ('contains' == $op)
			return $this->visitContains($obj, $rem);
		
		if ('is_null' == $op)
			return $this->visitIsNull($obj, $rem);
		
		if (in_array($op, ['==', '===', '!=', '!==', '<', '>', '<=', '>=']))
			return $this->visitComparison($obj, $op, $rem);
		
		throw new \InvalidArgumentException(sprintf("Unexpected operator name '%s'", $op));
	}
	
	
	// Evaluate a predicate. See README.md for the accepted formats.
	public function evaluate($obj, $pred)
	{
		if (!is_array($pred))
			throw new \InvalidArgumentException(sprintf("Expected predicate to be an array, got %s instead.", gettype($pred)));
		
		if (!$this->isNumericArray($pred))
			throw new \InvalidArgumentException(sprintf("Expected predicate to be a numeric array, got (%s) instead.", implode(", ", array_keys($pred))));
			
		return $this->visitNumericArray($obj, $pred);
	}
}

?>
