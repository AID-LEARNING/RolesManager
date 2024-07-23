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

namespace SenseiTarzan\RoleManager\Class\Role;

use JsonException;
use JsonSerializable;
use pocketmine\plugin\Plugin;
use pocketmine\utils\Config;
use SenseiTarzan\IconUtils\IconForm;
use SenseiTarzan\RoleManager\Component\RoleManager;
use SenseiTarzan\RoleManager\Utils\Utils;
use Symfony\Component\Filesystem\Path;
use function array_diff;
use function array_merge;
use function array_values;
use function is_array;
use function str_replace;

class Role  implements  JsonSerializable
{
	private string $chatFormat, $nameTagFormat;

	public function __construct(
		private string $id,
		private string $name,
		private IconForm $image,
		private bool $default,
		private float $priority,
		private array $heritages,
		private array $permissions,
		string $chatFormat,
		string $nameTagFormat,
		private bool $changeName,
		private Config $config
	)
	{
		$this->chatFormat = str_replace('\n', "\n", $chatFormat);
		$this->nameTagFormat = str_replace('\n', "\n", $nameTagFormat);
	}

	public static function create(Plugin $plugin, string $name, string $image,bool $default,float $priority,array $heritages,array $permissions, string $chatFormat, string $nameTagFormat,bool $changeName, ?Config $config = null) : Role
	{
		$role = new Role(
			Utils::roleStringToId($name = Utils::removeColorInRole($name)),
			$name,
			IconForm::create($image),
			$default,
			$priority,
			$heritages,
			$permissions,
			$chatFormat,
			$nameTagFormat,
			$changeName,
			$config ??= new Config(Path::join($plugin->getDataFolder(), "roles/", "$name.role.yml"))
		);
		if (empty($config->getAll())) {
			$config->setAll($role->jsonSerialize());
			$config->save();
		}
		return $role;

	}

	public function getId() : string
	{
		return $this->id;
	}

	public function getName() : string
	{
		return $this->name;
	}

	public function getImage() : IconForm
	{
		return $this->image;
	}

	public function setImage(string $image) : void
	{
		$this->image = IconForm::create($image);
		$this->config->set("image", $image);
		$this->config->save();
	}

	public function isDefault() : bool
	{
		return $this->default;
	}

	public function setDefault(bool $default) : void
	{
		$this->default = $default;
		$this->config->set("default", $default);
		$this->config->save();
	}

	public function getPriority() : float
	{
		return $this->priority;
	}

	public function setPriority(int $priority) : void{
		$this->priority = $priority;
		$this->config->set("priority", $priority);
		$this->config->save();
	}

	public function getHeritages() : array
	{
		return $this->heritages;
	}

	public function setHeritages(array $heritages) : void{
		$this->heritages = array_values($heritages);
		$this->config->set("heritages", $this->getHeritages());
		$this->config->save();
	}
	public function addHeritages(array|string $heritages) : void{
		$this->setHeritages(array_merge($this->heritages, (is_array($heritages) ? $heritages: [$heritages])));
	}
	public function removeHeritages(array|string $heritages) : void{
		$this->setHeritages(array_diff($this->heritages, (is_array($heritages) ? $heritages: [$heritages])));
	}

	public function getAllHeritages() : array
	{
		$heritages = [];
		foreach ($this->heritages as $heritage){
			$heritages = array_merge($heritages, RoleManager::getInstance()->getHeritages($heritage));

		}
		return $heritages;
	}

	public function getPermissions() : array
	{
		return $this->permissions;
	}

	public function setPermissions(array $permissions) : void{
		$this->permissions = array_values($permissions);
		$this->config->set("permissions", $this->getPermissions());
		$this->config->save();
	}

	public function addPermission(array|string $permission) : void{
		$this->setPermissions(array_merge($this->permissions , (is_array($permission) ? $permission: [$permission])));
	}

	public function removePermission(array|string $permission) : void{
		$this->setPermissions(array_diff($this->permissions, (is_array($permission) ? $permission: [$permission])));
	}

	public function getHeritagesPermissions() : array
	{
		$permissions = [];
		foreach ($this->heritages as $heritage){
			if (is_array($permissionsRole = RoleManager::getInstance()->getPermissionRole($heritage))){
				$permissions = array_merge($permissions, $permissionsRole);
			}
		}
		return $permissions;
	}

	public function getAllPermissions() : array{
		return array_merge($this->permissions, $this->getHeritagesPermissions());
	}

	public function getChatFormat() : string
	{
		return $this->chatFormat;
	}

	public function setChatFormat(string $chatFormat) : void
	{
		$this->chatFormat = str_replace('\n', "\n", $chatFormat);
		$this->config->set("chatFormat", $this->getChatFormat());
		$this->config->save();
	}

	public function getNameTagFormat() : string
	{
		return $this->nameTagFormat;
	}

	public function setNameTagFormat(string $nameTagFormat) : void
	{
		$this->nameTagFormat = str_replace('\n', "\n", $nameTagFormat);
		$this->config->set("nameTagFormat", $this->getNameTagFormat());
		$this->config->save();
	}

	public function isChangeName() : bool
	{
		return $this->changeName;
	}

	/**
	 * @throws JsonException
	 */
	public function setChangeName(bool $changeName = false) : void
	{
		$this->changeName = $changeName;
		$this->config->set("changeName", $changeName);
		$this->config->save();
	}

	public function getConfig() : Config
	{
		return $this->config;
	}

	public function jsonSerialize() : array
	{
		return ["name" => $this->getName(), "image" => $this->getImage()->getPath(), "priority" => $this->getPriority(), "default" => $this->isDefault(), "changeName" => $this->isChangeName(), "heritage" => $this->getHeritages(), "chatFormat" => $this->getChatFormat(), "nameTagFormat" => $this->getNameTagFormat(), "permissions" => $this->getPermissions()];
	}
}
