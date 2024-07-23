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

use JsonSerializable;
use SenseiTarzan\RoleManager\Component\RoleManager;
use function array_merge;
use function strtolower;

class RolePlayerOffline implements JsonSerializable
{

	private string $id;

	/**
	 * RolePlayer constructor.
	 * @param string[] $subRoles
	 */
	public function __construct(private readonly string $name, private string $prefix, private string $suffix, private string $role, private array $subRoles, private string|null $nameRoleCustom, private array $permissions = [])
	{
		$this->id = strtolower($this->name);
	}

	public function getId() : string
	{
		return $this->id;
	}

	public function getName() : string
	{
		return $this->name;
	}

	public function getPrefix() : string
	{
		return $this->prefix;
	}

	public function getSuffix() : string
	{
		return $this->suffix;
	}

	public function getRole() : Role
	{
		return RoleManager::getInstance()->getRole($this->role);
	}

	public function getSubRoles() : array
	{
		return $this->subRoles;
	}
	public function getNameRoleCustom() : ?string
	{
		return $this->nameRoleCustom;
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

	public function jsonSerialize() : array
	{
		return [
			"prefix" => $this->getPrefix(),
			"suffix" => $this->getSuffix(),
			"role" => $this->getRole()->getId(),
			"subRoles" => $this->getSubRoles(),
			"nameRoleCustom" => $this->getNameRoleCustom(),
			"permissions" => $this->getPermissions()
		];
	}
}
