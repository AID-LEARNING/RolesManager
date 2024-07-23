<?php

/*
 *
 *            _____ _____         _      ______          _____  _   _ _____ _   _  _____
 *      /\   |_   _|  __ \       | |    |  ____|   /\   |  __ \| \ | |_   _| \ | |/ ____|
 *     /  \    | | | |  | |______| |    | |__     /  \  | |__) |  \| | | | |  \| | |  __
 *    / /\ \   | | | |  | |______| |    |  __|   / /\ \ |  _  /| . ` | | | | . ` | | |_ |
 *   / ____ \ _| |_| |__| |      | |____| |____ / ____ \| | \ \| |\  |_| |_| |\  | |__| |
 *  /_/    \_\_____|_____/       |______|______/_/    \_\_|  \_\_| \_|_____|_| \_|\_____|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author AID-LEARNING
 * @link https://github.com/AID-LEARNING
 *
 */

declare(strict_types=1);

namespace SenseiTarzan\RoleManager;

use CortexPE\Commando\PacketHooker;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use SenseiTarzan\DataBase\Component\DataManager;
use SenseiTarzan\ExtraEvent\Component\EventLoader;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;
use SenseiTarzan\Middleware\Component\MiddlewareManager;
use SenseiTarzan\Path\PathScanner;
use SenseiTarzan\RoleManager\Class\Middleware\RoleMiddleware;
use SenseiTarzan\RoleManager\Class\Save\JSONSave;
use SenseiTarzan\RoleManager\Class\Save\YAMLSave;
use SenseiTarzan\RoleManager\Commands\RoleCommands;
use SenseiTarzan\RoleManager\Component\RoleManager;
use SenseiTarzan\RoleManager\Component\TextAttributeManager;
use SenseiTarzan\RoleManager\Listener\PlayerListener;
use SenseiTarzan\RoleManager\Task\NameTagTask;
use SOFe\AwaitStd\AwaitStd;
use Symfony\Component\Filesystem\Path;
use function dirname;
use function file_exists;
use function str_replace;
use function strtolower;

class Main extends PluginBase
{

	use SingletonTrait;

	private AwaitStd $awaitStd;

	public function onLoad() : void
	{
		self::setInstance($this);
		if (!file_exists(Path::join($this->getDataFolder(), "config.yml"))) {
			foreach (PathScanner::scanDirectoryGenerator($search = Path::join(dirname(__DIR__, 3), "resources")) as $file) {
				@$this->saveResource(str_replace($search, "", $file));
			}
		}
		new LanguageManager($this);
		new RoleManager($this);
		DataManager::getInstance()->setDataSystem(match (strtolower($this->getConfig()->get("data-type", "json"))) {
			"yml", "yaml" => new YAMLSave($this->getDataFolder()),
			"json" => new JSONSave($this->getDataFolder()),
			default => null
		});
		new TextAttributeManager();
		$this->awaitStd = AwaitStd::init($this);
	}

	protected function onEnable() : void
	{
		if (!PacketHooker::isRegistered()) {
			PacketHooker::register($this);
		}
		LanguageManager::getInstance()->loadCommands("role");

		$hasMiddleware = $this->getServer()->getPluginManager()->getPlugin("Middleware") !== null;
		if ($hasMiddleware)
			MiddlewareManager::getInstance()->addMiddleware(new RoleMiddleware());
		EventLoader::loadEventWithClass($this, new PlayerListener($hasMiddleware));

		if ($this->getConfig()->get("nametag-task-tick", 20)) {
			$this->getScheduler()->scheduleRepeatingTask(new NameTagTask(), 20);
		}

		$this->getServer()->getCommandMap()->register("rolemanager", new RoleCommands($this, "role", "Role Command", ["group"]));
	}

	public function getAwaitStd() : AwaitStd
	{
		return $this->awaitStd;
	}
}
