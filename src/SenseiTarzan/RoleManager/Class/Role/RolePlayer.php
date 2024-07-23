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

use Error;
use Generator;
use JsonException;
use JsonSerializable;
use pocketmine\permission\PermissionAttachment;
use pocketmine\player\Player;
use pocketmine\Server;
use SenseiTarzan\DataBase\Component\DataManager;
use SenseiTarzan\RoleManager\Class\Exception\CancelEventException;
use SenseiTarzan\RoleManager\Class\Exception\RoleFilteredNameCustomException;
use SenseiTarzan\RoleManager\Class\Exception\RoleNoNameCustomException;
use SenseiTarzan\RoleManager\Component\RoleManager;
use SenseiTarzan\RoleManager\Event\EventChangeNameCustom;
use SenseiTarzan\RoleManager\Event\EventChangePrefix;
use SenseiTarzan\RoleManager\Event\EventChangeRole;
use SenseiTarzan\RoleManager\Event\EventChangeSuffix;
use SenseiTarzan\RoleManager\Utils\Utils;
use SOFe\AwaitGenerator\Await;
use function array_diff;
use function array_merge;
use function array_values;
use function in_array;
use function is_array;
use function is_string;
use function mb_strtolower;
use function strtolower;

class RolePlayer implements JsonSerializable
{

	private string $id;
	private ?PermissionAttachment $attachment = null;

	/**
	 * RolePlayer constructor.
	 * @param string[] $subRoles
	 * @throws JsonException
	 */
	public function __construct(private Player $player, private string $prefix, private string $suffix, private string $role, private array $subRoles, private string|null $nameRoleCustom, private array $permissions = [])
	{
		$this->id = strtolower($this->player->getName());
	}

	public function getId() : string
	{
		return $this->id;
	}

	public function getName() : string
	{
		return $this->player->getName();
	}

	public function getPrefix() : string
	{
		return $this->prefix;
	}

	public function setPrefix(string $prefix) : Generator
	{
		return Await::promise(function ($resolve, $reject) use ($prefix) {
			$event = new EventChangePrefix(Server::getInstance()->getPlayerExact($this->getName()), $this->getPrefix(), $prefix);
			$event->call();
			if ($event->isCancelled()) {
				$reject(new CancelEventException());
				return;
			}
			Await::f2c(function () use ($event) : Generator {
				yield from DataManager::getInstance()->getDataSystem()->updateOnline($this->getId(), "prefix", $prefix = $event->getNewPrefix());
				return $prefix;
			},  function (string $prefix) use ($resolve){
				$this->prefix = $prefix;
				$resolve($prefix);
			}, $reject);
		});

	}

	public function getSuffix() : string
	{
		return $this->suffix;
	}

	public function setSuffix(string $suffix) : Generator
	{

		return Await::promise(function ($resolve, $reject) use ($suffix) {

			$event = new EventChangeSuffix(Server::getInstance()->getPlayerExact($this->getName()), $this->getSuffix(), $suffix);
			$event->call();
			if ($event->isCancelled()){
				$reject(new CancelEventException());
				return;
			}
			Await::f2c(function () use($event) : Generator{
				yield from DataManager::getInstance()->getDataSystem()->updateOnline($this->getId(), "suffix", $suffix = $event->getNewSuffix());
				return $suffix;
			}, function (string $suffix) use ($resolve){
				$this->suffix = $suffix;
				$resolve($suffix);
			}, $reject);
		});
	}

	public function getRole() : Role
	{
		return RoleManager::getInstance()->getRole($this->role);
	}

	public function getSubRoles() : array
	{
		return $this->subRoles;
	}

	public function hasSubRole(string $role) : bool
	{
		return in_array($role, $this->subRoles, true) || $this->role === $role || in_array($role, $this->getRole()->getAllHeritages(), true);
	}

	public function filterNoHasSubRoles(array $roles) : array
	{
		$list = [];
		foreach ($roles as $role) {
			if ($role instanceof Role) {
				if (!$this->hasSubRole($role->getId())){
					$list[] = $role->getId();
				}
				continue;
			}
			if (!$this->hasSubRole($role)){
				$list[] = $role;
			}

		}
		return $list;
	}

	/**
	 * @return Generator<string>
	 */
	public function addSubRole(array|string|Role $roles) : Generator
	{
		return $this->setSubRoles(array_merge($this->subRoles, $this->filterNoHasSubRoles(is_array($roles) ? $roles : [$roles])));
	}

	/**
	 * @return Generator<string[]>
	 */
	public function removeSubRole(array|string|Role $roles) : Generator
	{
		return $this->setSubRoles(array_diff($this->subRoles, (is_array($roles) ? $roles : [$roles])));
	}

	public function setSubRoles(array|string|Role $roles) : Generator
	{
		return Await::promise(function ($resolve, $reject) use ($roles) {
			if (is_string($roles)) {
				$roles = [$roles];
			} elseif ($roles instanceof Role) {
				$roles = [$roles->getId()];
			}
			$roles = array_values($roles);
			Await::f2c(function () use ($roles) : Generator {
				yield from DataManager::getInstance()->getDataSystem()->updateOnline($this->getId(), "subRoles", $roles);
				return $roles;
			}, function (array $roles) use ($resolve){
				$this->subRoles = $roles;
				$resolve($roles);
			}, $reject);
		});

	}

	public function clearSubRoles() : void
	{
		$this->setSubRoles([]);
	}

	/**
	 * @return Generator<Role>
	 * @throws CancelEventException
	 */
	public function setRole(string|Role $role) : Generator
	{
		return Await::promise(function ($resolve, $reject) use ($role) {
			$event = new EventChangeRole(Server::getInstance()->getPlayerExact($this->getName()), $this->getRole(), $role instanceof Role ? $role : RoleManager::getInstance()->getRole($role));
			$event->call();
			if ($event->isCancelled()) {
				$reject(new CancelEventException());
				return;
			}
			Await::f2c(function () use ($event, $role) : Generator {
				yield from DataManager::getInstance()->getDataSystem()->updateOnline($this->getId(), "role", (($role = $event->getNewRole())->getId()));
				return $role;
			}, function (Role $role) use ($resolve){
				$this->role = $role->getId();
				$resolve($role);
			}, $reject);
		});
	}

	public function getNameRoleCustom() : ?string
	{
		return $this->nameRoleCustom;
	}

	/**
	 * @return Generator<string>
	 */
	public function setRoleNameCustom(?string $role = null) : Generator
	{

		return Await::promise(function ($resolve, $reject) use ($role) {
			if (!$this->getRole()->isChangeName()) {
				$reject(new RoleNoNameCustomException());
				return;
			}
			if (empty($role) || $role === "{&originalName}") {
				$role = $this->getRole()->getName();
			}
			$newName = mb_strtolower(Utils::removeColorInRole($role));
			$checkEqualRole = $newName === $this->getRole()->getId();
			if (!$checkEqualRole && in_array($newName, RoleManager::getInstance()->getExcludeNameRole(), true)) {
				$reject(new RoleFilteredNameCustomException());
				return;
			}
			$event = new EventChangeNameCustom(Server::getInstance()->getPlayerExact($this->getName()), $this->getRoleName(), $checkEqualRole ? $this->getRole()->getName() : $role);
			$event->call();
			if ($event->isCancelled()) {
				$reject(new CancelEventException());
				return;
			}

			Await::f2c(function () use ($event, $role) : Generator {
				yield from DataManager::getInstance()->getDataSystem()->updateOnline($this->getId(), "nameRoleCustom", $newName = $event->getNewNameCustom());
				return $newName;
			}, function (string $newName) use ($resolve) {
				$this->nameRoleCustom = $newName;
				$resolve($newName);
			}, $reject);
		});
	}

	public function getRoleName() : string
	{
		return ($this->getRole()->isChangeName() ? $this->getNameRoleCustom() : null) ?? $this->getRole()->getName();
	}

	public function getPermissions() : array
	{
		return $this->permissions;
	}

	public function getPermissionsSubRoles() : array
	{
		if (empty($this->getSubRoles())) return [];
		$permissions = [];
		$roleManager = RoleManager::getInstance();
		foreach ($this->getSubRoles() as $role) {
			if (!$roleManager->existRole($role)) continue;
			$permissions = array_merge($permissions, $roleManager->getRole($role)->getPermissions());
		}
		return $permissions;
	}

	/**
	 * @return Generator<string[]>
	 */
	public function setPermissions(array|string $permissions) : Generator
	{
		return Await::promise(function ($resolve, $reject) use ($permissions) {
			Await::f2c(function () use ($permissions) : Generator {
				if (is_string($permissions)) {
					$permissions = [$permissions];
				}
				$permissions = array_values($permissions);
				yield from DataManager::getInstance()->getDataSystem()->updateOnline($this->getId(), "permissions", $permissions);
				return $permissions;
			}, function (array $permissions) use ($resolve){
				$this->permissions = $permissions;
				$resolve($permissions);
			}, $reject);
		});
	}

	/**
	 * @return Generator<string[]>
	 */
	public function addPermissions(array|string $permissions) : Generator
	{
		return $this->setPermissions(array_merge($this->getPermissions(), (is_array($permissions) ? $permissions : [$permissions])));
	}

	/**
	 * @return Generator<string[]>
	 */
	public function removePermissions(array|string $permissions) : Generator
	{
		return $this->setPermissions(array_diff($this->getPermissions(), (is_array($permissions) ? $permissions : [$permissions])));
	}

	public function jsonSerialize() : array
	{
		return ["prefix" => $this->getPrefix(), "suffix" => $this->getSuffix(), "role" => $this->getRole()->getId(), "subRoles" => $this->getSubRoles(), "nameRoleCustom" => $this->getNameRoleCustom(), "permissions" => $this->getPermissions()];
	}

	public function setAttachment(PermissionAttachment $addAttachment) : void
	{
		$this->attachment = $addAttachment;
	}

	public function getAttachment() : PermissionAttachment
	{
		return $this->attachment ?? throw new Error("Attachment not found");
	}
}
