<?php
/*
 * Copyright (c) 2018 Tuukka Norri
 * This code is licensed under MIT license (see LICENSE for details).
 */

namespace Grav\Plugin\TwigCollectionFilterExtension;

require_once(__DIR__ . '/Evaluator.php');

use \Grav\Common\Page\Page;
use \Grav\Common\Page\Collection;


class FilterExtension extends \Twig_Extension
{
	public function getName()
	{
		return 'TwigCollectionFilterExtension';
	}
	
	
	private function handleCollection($collection, $evaluator, $pred, &$matching, $recurse)
	{
		foreach ($collection as $page)
			$this->handlePage($page, $evaluator, $pred, $matching, $recurse);
	}
	
	
	private function handlePage($page, $evaluator, $pred, &$matching, $recurse)
	{
		$res = $evaluator->evaluate($page, $pred);
		if ($res)
			array_push($matching, $page);
		
		if ($recurse)
			$this->handleCollection($page->children(), $evaluator, $pred, $matching, $recurse);
	}
	
	
	public function getFilters()
	{
		return [
			'test_predicate' => new  \Twig_SimpleFilter('test_predicate', function ($page, $pred) {
				$evaluator = new Evaluator();
				
				if ($page instanceof Page)
					return $evaluator->evaluate($page, $pred);
				
				$typename = gettype($page);
				if ('object' === $typename)
					$typename = get_class($page);
				
				throw new \InvalidArgumentException(sprintf("Expected a page, got '%s'", $typename));
			}),
			
			'filter_collection' => new \Twig_SimpleFilter('filter_collection', function ($pageOrCollection, $pred, $recurse = true) {
				if (!$pageOrCollection)
					return null;
				
				$matching = [];
				$evaluator = new Evaluator();
				
				if ($pageOrCollection instanceof Page)
					$this->handlePage($pageOrCollection, $evaluator, $pred, $matching, $recurse);
				else if ($pageOrCollection instanceof Collection)
					$this->handleCollection($pageOrCollection, $evaluator, $pred, $matching, $recurse);
				else
				{
					$typename = gettype($pageOrCollection);
					if ('object' === $typename)
						$typename = get_class($pageOrCollection);
					
					throw new \InvalidArgumentException(sprintf("Expected a page or a collection, got '%s'", $typename));
				}
				
				return $matching;
			})
		];
	}
}

?>
