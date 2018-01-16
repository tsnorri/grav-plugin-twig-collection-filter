<?php
/*
 * Copyright (c) 2018 Tuukka Norri
 * This code is licensed under MIT license (see LICENSE for details).
 */

namespace Grav\Plugin;

require_once(__DIR__ . '/classes/TwigCollectionFilterExtension.php');

use \Grav\Common\Plugin;
use \Grav\Plugin\TwigCollectionFilterExtension;


class TwigCollectionFilterPlugin extends Plugin
{
	public static function getSubscribedEvents()
	{
		return [
			'onPluginsInitialized' => ['onPluginsInitialized', 0]
		];
	}

	public function onPluginsInitialized()
	{
		// Don't proceed if we are in the admin plugin.
		if ($this->isAdmin())
		{
			return;
		}

		$this->enable([
			'onTwigExtensions' => ['onTwigExtensions', 0]
		]);
	}

	public function onTwigExtensions()
	{
		$this->grav['twig']->twig->addExtension(new TwigCollectionFilterExtension\FilterExtension());
	}
}

?>
