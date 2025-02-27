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

use Generator;
use JsonException;
use JsonSerializable;
use pocketmine\plugin\Plugin;
use pocketmine\utils\Config;
use SenseiTarzan\IconUtils\IconForm;
use SenseiTarzan\RoleManager\Component\RoleManager;
use SenseiTarzan\RoleManager\Main;
use SenseiTarzan\RoleManager\Utils\Utils;
use SOFe\AwaitGenerator\Await;
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
		private bool $changeName
	)
	{
		$this->chatFormat = str_replace('\n', "\n", $chatFormat);
		$this->nameTagFormat = str_replace('\n', "\n", $nameTagFormat);
	}

	public static function create(string $name, string $image,bool $default,float $priority,array $heritages,array $permissions, string $chatFormat, string $nameTagFormat,bool $changeName) : Role
	{
		return new Role(
            Utils::roleStringToId($name = Utils::removeColorInRole($name)),
            $name,
            IconForm::create($image),
            $default,
            $priority,
            $heritages,
            $permissions,
            $chatFormat,
            $nameTagFormat,
            $changeName
        );
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

	public function setImage(string $image) : Generator
	{
        return Await::promise(function ($resolve, $reject) use($image){
            Await::g2c(Main::getInstance()->getConfigManager()->getConfigSystem()?->update($this->id, "image", $image), function (string $data) use ($resolve) {
                $this->image = IconForm::create($data);
                $resolve();
            }, $reject);
        });
	}

	public function isDefault() : bool
	{
		return $this->default;
	}

	public function setDefault(bool $default) : Generator
	{
        return Await::promise(function ($resolve, $reject) use($default) {
            Await::g2c(Main::getInstance()->getConfigManager()->getConfigSystem()?->update($this->id, "default", $default), function (bool $data) use ($resolve) {
                $this->default = $data;
                $resolve();
            }, $reject);
        });
	}

	public function getPriority() : float
	{
		return $this->priority;
	}

	public function setPriority(int $priority) : Generator{
        return Await::promise(function ($resolve, $reject) use($priority){
            Await::g2c(Main::getInstance()->getConfigManager()->getConfigSystem()?->update($this->id, "priority", $priority), function (int $data) use ($resolve) {
                $this->priority = $data;
                $resolve();
            }, $reject);
        });

        /*
		$this->config->set("priority", $priority);
		$this->config->save();
         */
	}

	public function getHeritages() : array
	{
		return $this->heritages;
	}

	public function setHeritages(array $heritages) : Generator{
        return Await::promise(function ($resolve, $reject) use($heritages){
            Await::g2c(Main::getInstance()->getConfigManager()->getConfigSystem()?->update($this->id, "set.heritages", $this->heritages), function (array $data) use ($resolve) {
                $this->heritages = $data;
                $resolve();
            }, $reject);
        });
        /*
		$this->config->set("heritages", $this->getHeritages());
		$this->config->save();*/
	}
	public function addHeritages(array|string $heritages) : Generator{
        return Await::promise(function ($resolve, $reject) use($heritages){
            Await::g2c(Main::getInstance()->getConfigManager()->getConfigSystem()?->update($this->id, "add.heritages", $heritages), function (array $data) use ($resolve) {
                $this->heritages = $data;
                $resolve();
            }, $reject);
        });
	}
	public function removeHeritages(array|string $heritages) : Generator{
        return Await::promise(function ($resolve, $reject) use($heritages){
            Await::g2c(Main::getInstance()->getConfigManager()->getConfigSystem()?->update($this->id, "sub.heritages", $heritages), function (array $data) use ($resolve) {
                $this->heritages = $data;
                $resolve();
            }, $reject);
        });
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

	public function setPermissions(array $permissions) : Generator{
        return Await::promise(function ($resolve, $reject) use($permissions){
            Await::g2c(Main::getInstance()->getConfigManager()->getConfigSystem()?->update($this->id, "set.permissions", array_values($permissions)), function (array $data) use ($resolve) {
                $this->permissions = $data;
                $resolve();
            }, $reject);
	    });
    }

	public function addPermission(array|string $permission) : Generator{
        return Await::promise(function ($resolve, $reject) use($permission){
            Await::g2c(Main::getInstance()->getConfigManager()->getConfigSystem()?->update($this->id, "add.permissions", (is_array($permission) ? $permission: [$permission])), function (array $data) use ($resolve) {
                $this->permissions = $data;
                $resolve();
            }, $reject);
        });
	}

	public function removePermission(array|string $permission) : Generator{
        return Await::promise(function ($resolve, $reject) use($permission){
            Await::g2c(Main::getInstance()->getConfigManager()->getConfigSystem()?->update($this->id, "sub.permissions", (is_array($permission) ? $permission: [$permission])), function (array $data) use ($resolve) {
                $this->permissions = $data;
                $resolve();
            }, $reject);
        });
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

	public function setChatFormat(string $chatFormat) : Generator
	{
        return Await::promise(function ($resolve, $reject) use($chatFormat){
            Await::g2c(Main::getInstance()->getConfigManager()->getConfigSystem()?->update($this->id, "chatFormat", $chatFormat), function (string $data) use ($resolve) {
                $this->chatFormat = str_replace('\n', "\n", $data);
                $resolve();
            }, $reject);
        });
	}

	public function getNameTagFormat() : string
	{
		return $this->nameTagFormat;
	}

	public function setNameTagFormat(string $nameTagFormat) : Generator
	{
        return Await::promise(function ($resolve, $reject) use($nameTagFormat){
            Await::g2c(Main::getInstance()->getConfigManager()->getConfigSystem()?->update($this->id, "nameTagFormat", $nameTagFormat), function (string $data) use ($resolve) {
                $this->nameTagFormat = str_replace('\n', "\n", $data);
                $resolve();
            }, $reject);
        });
	}

	public function isChangeName() : bool
	{
		return $this->changeName;
	}

	/**
	 * @throws JsonException
	 */
	public function setChangeName(bool $changeName = false) : Generator
	{
        return Await::promise(function ($resolve, $reject) use($changeName){
            Await::g2c(Main::getInstance()->getConfigManager()->getConfigSystem()?->update($this->id, "changeName", $changeName), function (bool $data) use ($resolve) {
                $this->changeName = $data;
                $resolve();
            }, $reject);
        });
	}

	public function remove(): Generator
    {
        return Await::promise(function ($resolve, $reject) {
            Await::g2c(Main::getInstance()->getConfigManager()->getConfigSystem()?->update($this->id, "delete", null), $resolve, $reject);
        });
    }

	public function jsonSerialize() : array
	{
		return ["name" => $this->getName(), "image" => $this->getImage()->getPath(), "priority" => $this->getPriority(), "default" => $this->isDefault(), "changeName" => $this->isChangeName(), "heritage" => $this->getHeritages(), "chatFormat" => $this->getChatFormat(), "nameTagFormat" => $this->getNameTagFormat(), "permissions" => $this->getPermissions()];
	}
}
