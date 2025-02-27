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
namespace SenseiTarzan\RoleManager\Component;

use Generator;
use InvalidArgumentException;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\ModalForm;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use SenseiTarzan\DataBase\Component\DataManager;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;
use SenseiTarzan\Path\PathScanner;
use SenseiTarzan\RoleManager\Class\Exception\CancelEventException;
use SenseiTarzan\RoleManager\Class\Role\Role;
use SenseiTarzan\RoleManager\Class\Role\RolePlayer;
use SenseiTarzan\RoleManager\Class\Save\IConfigSaveRole;
use SenseiTarzan\RoleManager\Class\Save\ResultUpdate;
use SenseiTarzan\RoleManager\Commands\args\RoleArgument;
use SenseiTarzan\RoleManager\Main;
use SenseiTarzan\RoleManager\Utils\CustomKnownTranslationFactory;
use SenseiTarzan\RoleManager\Utils\Utils;
use SOFe\AwaitGenerator\Await;
use Symfony\Component\Filesystem\Path;
use function array_diff;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_values;
use function explode;
use function is_array;
use function is_string;
use function mb_strtolower;
use function strtolower;
use function unlink;

class RoleManager
{
	use SingletonTrait;

	/** @var Role[] */
	private array $roles = [];

	private PluginBase $plugin;

	private Server $server;
	private Config $config;
	private Role $defaultRole;
	/** @var array|array[]|false[]|null[]|string[]|string[][] */
	private array $listExcludeName;

	public function __construct(Main $pl)
	{
		self::setInstance($this);
		$this->plugin = $pl;
		$this->config = $pl->getConfig();
        $this->loadRoles();
		$this->listExcludeName = Utils::rolesStringToIdArray(array_merge($this->config->get("exclude-name-role", []), $this->getRoles(true, true)));
	}

	public function loadRoles() : void
	{
		$this->roles = [];
		unset($this->defaultRole);
		RoleArgument::$VALUES = [];
        Await::f2c(function () {
            yield from $this->plugin->getConfigManager()->getConfigSystem()?->loadConfig();
        }, function () {
            foreach ($this->roles as $_ => $role) {
                RoleArgument::$VALUES[strtolower($role->getName())] = $role->getId();
                if ($role->isDefault())
                    $this->defaultRole = $role;
            }
        });
	}

	public function createRole(string $name, string $image, bool $default, float $priority, array $heritages, array $permissions, string $chatFormat, string $nameTagFormat, bool $changeName) : Generator
	{


		return Await::promise(function ($resolve, $reject) use($name, $image, $default, $priority, $heritages, $permissions, $chatFormat, $nameTagFormat, $changeName) : void {
            Await::f2c(function ()  use($name, $image, $default, $priority, $heritages, $permissions, $chatFormat, $nameTagFormat, $changeName): Generator{
                $role = Role::create(
                    $name,
                    $image,
                    $default,
                    $priority,
                    Utils::rolesStringToIdArray($heritages),
                    $permissions,
                    $chatFormat,
                    $nameTagFormat,
                    $changeName
                );
                $configSystem = $this->plugin->getConfigManager()->getConfigSystem();
                if ($configSystem instanceof IConfigSaveRole)
                    yield from $configSystem->newConfig($role);
                yield from $this->addRole($role, true);
                return $role;
            }, $resolve, $reject);
        });
	}

	private function getPermissionInString() : array
	{
		return array_map(fn (Permission $value) => $value->getName(), PermissionManager::getInstance()->getPermissions());
	}

	public function addRole(Role $role, bool $overwrite = false) : Generator
	{
		return Await::promise(function ($resolve, $reject) use($role, $overwrite) : void {
            Await::f2c(function () use ($role, $overwrite, $resolve, $reject) : Generator {
                if (array_key_exists($role->getId(), $this->getRoles())) {
                    return ;
                }
                if ($role->isDefault() && (!isset($this->defaultRole) || $overwrite)) {
                    RoleArgument::$VALUES['default'] = $role->getId();
                    yield from $this->setDefaultRole($role);
                }
                RoleArgument::$VALUES[strtolower($role->getName())] = $role->getId();
                $this->roles[$role->getId()] = $role;
            }, $resolve, $reject);
        });
	}

	public function getDefaultRole() : Role
	{
		return $this->defaultRole;
	}

	public function setDefaultRole(Role $defaultRole) : Generator
	{
        return Await::promise(function ($resolve, $reject) use ($defaultRole) {
            Await::f2c(function () use ($defaultRole) {
                if (isset($this->defaultRole)) {
                    if($this->defaultRole === $defaultRole)
                        throw new \Exception();
                    yield from $this->defaultRole->setDefault(false);
                    if (!$defaultRole->isDefault()) {
                        yield from  $defaultRole->setDefault(true);
                    }
                }elseif (!$defaultRole->isDefault()) {
                    yield from  $defaultRole->setDefault(true);
                }
            }, function () use ($defaultRole, $resolve) {
                $this->defaultRole = $defaultRole;
                $resolve();
            }, $reject);
        });
	}

	public function getExcludeNameRole() : array
	{
		return $this->listExcludeName;
	}

	/**
	 * @param string $role id|name
	 */
	public function getRole(string $role) : Role
	{
		return $this->roles[Utils::roleStringToId($role)] ?? $this->getDefaultRole();
	}

	public function existRole(string $role) : bool
	{
		return isset($this->roles[Utils::roleStringToId($role)]);
	}

	public function getSubRolesPlayer(Player $player) : array
	{

		return $player->isConnected() ? RolePlayerManager::getInstance()->getPlayer($player)->getSubRoles() : [];
	}

	/**
	 * @param array|string|Role|Role[] $roles
	 * @throws CancelEventException
	 */
	public function setSubRolesPlayer(Player|string $player, array|string|Role $roles) : Generator
	{
		return $this->updateDataPlayer($player, $roles, 'setSubRoles');
	}

	/**
	 * @param array|string|Role|Role[] $roles
	 * @throws CancelEventException
	 */
	public function addSubRolesPlayer(Player|string $player, array|string|Role $roles) : Generator
	{
		return $this->updateDataPlayer($player, $roles, 'addSubRoles');
	}

	/**
	 * @param array|string|Role|Role[] $roles
	 * @throws CancelEventException
	 */
	public function removeSubRolesPlayer(Player|string $player, array|string|Role $roles) : Generator
	{
		return $this->updateDataPlayer($player, $roles, 'removeSubRoles');
	}

	/**
	 * @param string $role id|name
	 */
	public function getRoleNullable(string $role) : ?Role
	{
		return $this->roles[Utils::roleStringToId($role)] ?? null;
	}

	public function getPermissionRole(string $role) : array
	{
		return $this->getRole($role)->getPermissions();
	}

	public function getPermissionHeritage(string $role) : array
	{
		return $this->getRole($role)->getHeritagesPermissions();
	}

	public function getHeritages(string $role) : array
	{
		return $this->getRole($role)->getAllHeritages();
	}

	public function addPermissions(RolePlayer $rolePlayer, array $permissions) : void
	{
		$attachment = $rolePlayer->getAttachment();
		$attachment?->clearPermissions();
		$attachment?->setPermissions($permissions);
	}

	public function getPriorityRole(string $role) : float
	{
		return $this->getRole($role)->getPriority();
	}

	/**
	 * @return Role[]|string[]
	 */
	public function getRoles(bool $keys = false, bool $name = false) : array
	{
		return $keys ? ($name ? array_map(fn (Role $role) => $role->getName(), $this->roles) : array_keys($this->roles)) : $this->roles;
	}

	public function addHeritageRole(string $role, array|string $heritages) : void
	{
		if (is_array($heritages)) {
			$heritages = array_filter($heritages, fn ($name) => $name !== $role && $this->isRole($name));
			if (empty($heritages)) {
				return;
			}
		} elseif ($heritages === $role && $this->isRole($heritages)) {
			return;
		}

		$this->getRole($role)->addHeritages($heritages);

	}

	private function isRole(string $role) : bool
	{
		return isset($this->roles[$role]);
	}

	public function addPermissionRole(string $role, array|string $permission) : void
	{
		$this->getRole($role)->addPermission($permission);
	}

	public function removePermissionRole(string $role, array|string $permission) : void
	{
		$this->getRole($role)->removePermission($permission);
	}

	/**
	 * @return Player[]
	 */
	public function getPlayerInServer() : array
	{
		return array_values($this->server->getOnlinePlayers());
	}

	/**
	 * @throws CancelEventException
	 */
	public function setRolePlayer(Player|string $player, Role|string $role) : Generator
	{

		if (is_string($role)) {
			$role = $this->getRole($role);
		}
		return $this->updateDataPlayer($player, $role);
	}

	public function setPrefix(Player $player, string $prefix) : Generator
	{
		return RolePlayerManager::getInstance()->getPlayer($player)->setPrefix($prefix);
	}

	/**
	 * @throws CancelEventException
	 */
	public function setNameRoleCustom(Player $player, string $roleNameCustom) : Generator
	{
		return RolePlayerManager::getInstance()->getPlayer($player)->setRoleNameCustom($roleNameCustom);
	}

	public function setSuffix(Player $player, string $suffix) : Generator
	{
		return RolePlayerManager::getInstance()->getPlayer($player)->setSuffix($suffix);
	}

	public function addPermissionPlayer(Player|string $player, array|string $permission) : Generator
	{
		return $this->updateDataPlayer($player, $permission, "addPermissions");
	}

	public function setPermissionPlayer(Player|string $player, array|string $permission) : Generator
	{
		return $this->updateDataPlayer($player, $permission, "setPermissions");
	}

	/**
	 * @throws CancelEventException
	 */
	public function removePermissionPlayer(Player|string $player, array|string $permission) : Generator
	{
		return $this->updateDataPlayer($player, $permission, "removePermissions");
	}

	/**
	 * @throws CancelEventException
	 */
	private function updateDataPlayer(Player|string $player, array|string|Role $raw, string $type = "role") : Generator
	{
		return Await::promise(function ($resolve, $reject) use ($player, $raw, $type) {
			if (is_string($player)) {
				$player = Server::getInstance()->getPlayerExact($player) ?? $player;
			}
			Await::f2c(function () use ($player, $raw, $type) : Generator {
				$target = RolePlayerManager::getInstance()->getPlayer($player);
				if ($target === null) {
					$data = $raw;
					if ($data instanceof Role) {
						$data = $data->getId();
					} elseif (is_array($data)) {
						$data = array_values(array_map(fn (Role|string $value) => ($value instanceof Role ? $value->getId() : $value), $data));
					}
					yield from DataManager::getInstance()->getDataSystem()->updateOffline($player, $type, $data);
					return new ResultUpdate(false, $raw);
				}
				$online = $player instanceof Player && $player->isConnected();
				switch ($type) {
					case "role":
						if (!($raw instanceof Role)) {
							throw new InvalidArgumentException("The data must be a role");
						}
						return new ResultUpdate($online, yield from $target->setRole($raw), true);
					case "addPermissions":
						return new ResultUpdate($online, yield from $target->addPermissions($raw), true);
					case "removePermissions":
						return new ResultUpdate($online, yield from $target->removePermissions($raw), true);
					case "setPermissions":
						return new ResultUpdate($online, yield from $target->setPermissions($raw), true);
					case "addSubRoles":
						return new ResultUpdate($online, yield from $target->addSubRole($raw), true);
					case "removeSubRoles":
						return new ResultUpdate($online, yield from $target->removeSubRole($raw), true);
					case "setSubRoles":
						return new ResultUpdate($online, yield from $target->setSubRoles($raw), true);
				}
			}, function (ResultUpdate $data) use ($player, $resolve) {
				if ($data->online && $data->updatePermission) {
					RolePlayerManager::getInstance()->loadPermissions(RolePlayerManager::getInstance()->getPlayer($player));
				}
				$resolve($data);
			}, $reject);
		});
	}

	public function createRoleUI(Player $player) : void
	{
		$ui = new CustomForm(function (Player $player, ?array $args) : void {
			if (!$args) {
				return;
			}
			$name = $args[0];
			$image = $args[2];
			$default = $args[3];
			$priority = intval($args[5]);
			$heritages = array_values(array_filter(explode(";", $args[7]), fn ($heritage) => $heritage !== ""));
			$permissions = array_values(array_filter(explode(";", $args[9]), fn ($permission) => $permission !== ""));
			$chatFormat = $args[10];
			$nameTagFormat = $args[11];
			$changeName = $args[12];

            Await::g2c($this->createRole($name, $image, $default, $priority, $heritages, $permissions, $chatFormat, $nameTagFormat, $changeName), function (Role $role) use($player){
                $player->sendMessage(
                    LanguageManager::getInstance()->getTranslateWithTranslatable(
                        $player,
                        CustomKnownTranslationFactory::message_create_role(
                            $role->getName()
                    )
                ));
			    $this->listExcludeName = array_map(fn (string $name) => mb_strtolower($name), array_merge($this->config->get("exclude-name-role", []), $this->getRoles(true, true)));
            });
		});
		$ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_create_role()));
		$ui->addInput("name Role", "King");// 0
		$ui->addLabel(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::exemple_image_label())); // 1
		$ui->addInput("image", "path/tete", "");// 2
		$ui->addToggle("default", false); // 3
		$ui->addLabel(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::exemple_priority_label()));// 4
		$ui->addInput("Priority", "0", "0");// 5
		$ui->addLabel(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::exemple_heritages_label())); // 6
		$ui->addInput("Heritages", "", "");// 7
		$ui->addLabel(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::exemple_permissions_label())); // 8
		$ui->addInput("Permissions", "", "");// 9
		$ui->addInput("Chat Format", "§7[§r{&prefix}§7]§r[§6{&role}§r]{&playerName}§7[{&suffix}§7]§r: §r{&message}", "§7[§r{&prefix}§7]§r[§6{&role}§r]{&playerName}§7[{&suffix}§7]§r: §r{&message}");// 10
		$ui->addInput("NameTag Format", "[§6{&role}§r]{&playerName}", "[§6{&role}§r]{&playerName}");// 11
		$ui->addToggle("changeName", false); // 12
		$player->sendForm($ui);
	}

	public function modifiedRoleSelectUI(Player $player) : void
	{
		$ui = new SimpleForm(function (Player $player, ?string $roleId) : void {
			if (!$roleId) {
				return;
			}
			$role = $this->getRoleNullable($roleId);
			if ($role === null) {
				return;
			}
			$this->modifiedRoleIndexUI($player, $role);
		});
		$ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_select_role()));
		foreach ($this->getRoles() as $role) {
			$ui->addButton($role->getName(), ($roleImage = $role->getImage())->getType(), $roleImage->getPath(), $role->getId());
		}
		$player->sendForm($ui);
	}

	private function modifiedRoleIndexUI(Player $player, Role $role) : void
	{
		$ui = new SimpleForm(function (Player $player, ?int $button) use ($role) : void {
			if ($button === false) {
				return;
			}
			match ($button) {
				0 => $this->modifiedRoleGeneralUI($player, $role),
				1 => $this->modifiedRoleDefaultUI($player, $role),
				2 => $this->permissionsRoleIndexUI($player, $role),
				3 => $this->heritagesRoleIndexUI($player, $role),
				4 => $this->removeRoleUI($player, $role),
				default => null
			};
		});

		$ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_select_type($role->getName())));
		$ui->addButton(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::button_modified_general()));
		$ui->addButton(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::button_modified_default()));
		$ui->addButton(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::button_modified_permissions()));
		$ui->addButton(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::button_modified_heritages()));
		$ui->addButton(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::button_modified_remove()));
		$player->sendForm($ui);

	}

	private function permissionsRoleIndexUI(Player $player, Role $role) : void
	{
		$ui = new SimpleForm(function (Player $player, ?int $data) use ($role) : void {
			if ($data === null) {
				$this->modifiedRoleIndexUI($player, $role);
				return;
			}
			match ($data) {
				0 => $this->permissionsRoleAddUI($player, $role),
				1 => $this->permissionsRoleRemoveUI($player, $role)
			};
		});

		$ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_modified_permissions($role->getName())));
		$ui->addButton(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::button_permissions_add()));
		$ui->addButton(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::button_permissions_remove()));
		$player->sendForm($ui);
	}

	private function permissionsRoleAddUI(Player $player, Role $role) : void
	{
		$ui = new SimpleForm(function (Player $player, ?string $permissions) use ($role) : void {
			if ($permissions === null) {
				$this->permissionsRoleIndexUI($player, $role);
				return;
			}
			Await::g2c($role->addPermission($permissions), function () use ($player, $role) : void {
                $this->permissionsRoleAddUI($player, $role);
            });
		});

		$ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_permissions_add($role->getName())));
		foreach (array_diff($this->getPermissionInString(), $role->getAllPermissions()) as $permission) {
			$ui->addButton($permission, label: $permission);
		}
		$player->sendForm($ui);
	}

	private function permissionsRoleRemoveUI(Player $player, Role $role) : void
	{
		$ui = new SimpleForm(function (Player $player, ?string $permissions) use ($role) : void {
			if ($permissions === null) {
				$this->permissionsRoleIndexUI($player, $role);
				return;
			}
			Await::g2c($role->removePermission($permissions), function () use ($player, $role) : void {
                $this->permissionsRoleRemoveUI($player, $role);
            });
		});

		$ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_permissions_remove($role->getName())));
		foreach ($role->getPermissions() as $permission) {
			$ui->addButton($permission, label: $permission);
		}
		$player->sendForm($ui);
	}

	private function heritagesRoleIndexUI(Player $player, Role $role) : void
	{
		$ui = new SimpleForm(function (Player $player, ?int $data) use ($role) : void {
			if ($data === null) {
				$this->modifiedRoleIndexUI($player, $role);
				return;
			}
			match ($data) {
				0 => $this->heritagesRoleAddUI($player, $role),
				1 => $this->heritagesRoleRemoveUI($player, $role)
			};
		});

		$ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_modified_heritages($role->getName())));
		$ui->addButton(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::button_heritages_add()));
		$ui->addButton(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::button_heritages_remove()));
		$player->sendForm($ui);
	}

	private function heritagesRoleAddUI(Player $player, Role $role) : void
	{
		$ui = new SimpleForm(function (Player $player, ?string $permissions) use ($role) : void {
			if ($permissions === null) {
				$this->heritagesRoleIndexUI($player, $role);
				return;
			}
			Await::g2c($role->addHeritages($permissions), function () use ($player, $role) : void {
                $this->heritagesRoleAddUI($player, $role);
            }, function () {});
		});

		$ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_heritages_add($role->getName())));
		foreach (array_diff($this->getRoles(true), $role->getAllHeritages(), [$role->getId()]) as $heritageId) {
			$ui->addButton(($role = $this->getRole($heritageId))->getName(), ($roleImage = $role->getImage())->getType(), $roleImage->getPath(), $heritageId);
		}
		$player->sendForm($ui);
	}

	private function heritagesRoleRemoveUI(Player $player, Role $role) : void
	{
		$ui = new SimpleForm(function (Player $player, ?string $permissions) use ($role) : void {
			if ($permissions === null) {
				$this->heritagesRoleIndexUI($player, $role);
				return;
			}
			Await::g2c($role->removeHeritages($permissions), function () use ($player, $role) : void {
                $this->heritagesRoleRemoveUI($player, $role);
            }, function () {});
		});

		$ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_heritages_remove($role->getName())));
		foreach ($role->getHeritages() as $heritageId) {
			$ui->addButton(($role = $this->getRole($heritageId))->getName(), ($roleImage = $role->getImage())->getType(), $roleImage->getPath(), $heritageId);
		}
		$player->sendForm($ui);
	}

	private function modifiedRoleGeneralUI(Player $player, Role $role) : void
	{
		$ui = new CustomForm(function (Player $player, ?array $data) use ($role) : void {
			if (!$data) {
				return;
			}
			list($changeName, $image, $priority, $chatFormat, $nameTagFormat) = $data;
			if ($changeName !== $role->isChangeName()) {
                Await::g2c($role->setChangeName($changeName), null, function (){});
			}
			if ($image !== $role->getImage()->getPath()) {
				Await::g2c($role->setImage($image), null, function (){});
			}
			if ($priority !== $role->getPriority()) {
                Await::g2c($role->setPriority(intval($priority)), null, function (){});
			}
			if ($chatFormat !== $role->getChatFormat()) {
                Await::g2c($role->setChatFormat($chatFormat), null, function (){});
			}
			if ($nameTagFormat !== $role->getNameTagFormat()) {
                Await::g2c($role->setNameTagFormat($nameTagFormat), null, function (){});
			}
		});
		$ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_modified_general($role->getName())));
		$ui->addToggle("changeName", $role->isChangeName()); //0
		$ui->addInput("Image", $role->getImage()->getPath(), $role->getImage()->getPath()); //1
		$ui->addInput("Priority", (string)$role->getPriority(), (string)$role->getPriority()); // 2
		$ui->addInput("Chat Format", $role->getChatFormat(), $role->getChatFormat());// 3
		$ui->addInput("NameTag Format", $role->getNameTagFormat(), $role->getNameTagFormat());// 4
		$player->sendForm($ui);
	}

	private function modifiedRoleDefaultUI(Player $player, Role $role) : void
	{
		$ui = new ModalForm(function (Player $player, ?bool $default) use ($role) : void {
			if ($default === null) {
				return;
			}
			if ($role->getId() === $this->getDefaultRole()->getId() || $default === false) {
				$this->modifiedRoleIndexUI($player, $role);
				return;
			}
			Await::g2c($this->setDefaultRole($role), function () use($player, $role) : void {
                $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::set_default_role_sender($role->getName())));
            }, function (){});
		});
		$ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_modified_default($role->getName())));
		$ui->setContent(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::description_modified_default()));
		$ui->setButton1(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_accept()));
		$ui->setButton2(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_denied()));
		$player->sendForm($ui);
	}

	private function removeRoleUI(Player $player, Role $role) : void
	{
		$ui = new ModalForm(function (Player $player, ?bool $remove) use ($role) : void {
			if ($remove === null) {
				return;
			}
			if ($role->getId() === $this->getDefaultRole()->getId() || $remove === false) {
				$this->modifiedRoleIndexUI($player, $role);
				return;
			}
			Await::g2c($role->remove(), function () use($role, $player) : void {
                unset($this->roles[$role->getId()]);
                if ($player->isConnected())
                    $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::remove_role($role->getName())));
                $this->listExcludeName = array_map(fn (string $name) => mb_strtolower($name), array_merge($this->config->get("exclude-name-role", []), $this->getRoles(true, true)));
            }, function (){});
		});
		$ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_modified_remove($role->getName())));
		$ui->setContent(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::description_modified_remove()));
		$ui->setButton1(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_accept()));
		$ui->setButton2(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_denied()));
		$player->sendForm($ui);
	}
}
