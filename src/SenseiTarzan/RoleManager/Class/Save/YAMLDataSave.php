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

namespace SenseiTarzan\RoleManager\Class\Save;

use Generator;
use JsonException;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use SenseiTarzan\RoleManager\Class\Exception\SaveDataException;
use SenseiTarzan\RoleManager\Class\Role\RolePlayer;
use SenseiTarzan\RoleManager\Class\Role\RolePlayerOffline;
use SenseiTarzan\RoleManager\Component\RoleManager;
use SOFe\AwaitGenerator\Await;
use Symfony\Component\Filesystem\Path;
use function array_diff;
use function array_filter;
use function array_merge;
use function array_values;
use function in_array;
use function is_string;
use function strtolower;

class YAMLDataSave extends IDataSaveRoleManager
{

	private Config $config;

	public function __construct(string $dataFolder)
	{
		$this->config = new Config(Path::join($dataFolder, "data.yml"), Config::YAML);
	}

	public function getName() : string
	{
		return "YAML System";
	}

	protected function createPromiseLoadDataPlayer(Player|string $player) : Generator
	{
		return Await::promise(function ($resolve, $reject) use ($player) {
			Await::f2c(function () use ($player) : Generator {
				if (!$this->config->exists($name = strtolower($player->getName()), true)) {
					$rolePlayer = new RolePlayer($player, prefix: "", suffix: "", role: RoleManager::getInstance()->getDefaultRole()->getId(), subRoles: [], nameRoleCustom: null);
					yield from $this->createPromiseSaveDataPlayer($rolePlayer);
					return $rolePlayer;
				}
				$infoPlayer = $this->config->get($name);
				return new RolePlayer($player, $infoPlayer['prefix'] ?? "", $infoPlayer['suffix'] ?? "", $infoPlayer['role'] ?? RoleManager::getInstance()->getDefaultRole()->getId(), $infoPlayer['subRoles'] ?? [], $infoPlayer['nameRoleCustom'] ?? null, $infoPlayer['permissions'] ?? []);
			}, $resolve, $reject);
		});
	}

	protected function createPromiseSaveDataPlayer(RolePlayer $rolePlayer) : Generator
	{
		return Await::promise(function ($resolve, $reject) use ($rolePlayer) {
			try {
				$this->config->set($rolePlayer->getId(), $rolePlayer->jsonSerialize());
				$this->config->save();
				$resolve();
			} catch (JsonException) {
				$reject(new SaveDataException("Error save data player {$rolePlayer->getName()}"));
			}
		});
	}

	public function createPromiseUpdateOffline(string $id, string $type, mixed $data) : Generator
	{
		return Await::promise(function ($resolve, $reject) use ($id, $type, $data) {
			try {
				if (!$this->config->exists($id, true)) {
					$rolePlayer = new RolePlayerOffline($id, prefix: "", suffix: "", role: RoleManager::getInstance()->getDefaultRole()->getId(), subRoles: [], nameRoleCustom: null);
					$this->config->set($rolePlayer->getId(), $rolePlayer->jsonSerialize());
					unset($rolePlayer);
				}
				$this->config->setNested($search = (strtolower($id) . "." . (match ($type) {
						"addPermissions", "removePermissions", "setPermissions" => 'permissions',
						"addSubRoles", "removeSubRoles", "setSubRoles" => 'subRoles',
						default => $type
					})), match ($type) {
					"addPermissions", "addSubRoles" => array_merge($dataInSave = $this->config->getNested($search), array_filter((is_string($data) ? [$data] : $data), fn(string $value) => (($type !== "addSubRoles") || RoleManager::getInstance()->existRole($value)) && !in_array($value, $dataInSave, true))),
					"removePermissions", "removeSubRoles" => array_values(array_diff($this->config->getNested($search), (is_string($data) ? [$data] : $data))),
					"setPermissions", "setSubRoles" => is_string($data) ? [$data] : $data,
					default => $data
				});
				$this->config->save();
				$resolve();
			} catch (JsonException) {
				$reject(new SaveDataException("Error save data player offline {$id}"));
			}
		});
	}

	public function createPromiseUpdateOnline(string $id, string $type, mixed $data) : Generator
	{
		return Await::promise(function ($resolve, $reject) use ($id, $type, $data) {
			try {
				$this->config->setNested($id . ".$type", $data);
				$this->config->save();
				$resolve();
			} catch (JsonException) {
				$reject(new SaveDataException("Error save data player {$id}"));
			}
		});
	}
}
