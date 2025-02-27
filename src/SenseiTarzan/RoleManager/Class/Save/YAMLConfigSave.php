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
use Hoa\Math\Util;
use JsonException;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use SenseiTarzan\Path\PathScanner;
use SenseiTarzan\RoleManager\Class\Exception\SaveDataException;
use SenseiTarzan\RoleManager\Class\Role\Role;
use SenseiTarzan\RoleManager\Class\Role\RolePlayer;
use SenseiTarzan\RoleManager\Class\Role\RolePlayerOffline;
use SenseiTarzan\RoleManager\Component\RoleManager;
use SenseiTarzan\RoleManager\Main;
use SenseiTarzan\RoleManager\Utils\Utils;
use SOFe\AwaitGenerator\Await;
use Symfony\Component\Filesystem\Path;
use function array_diff;
use function array_filter;
use function array_merge;
use function array_values;
use function in_array;
use function is_string;
use function strtolower;

class YAMLConfigSave extends IConfigSaveRole
{

    /**
     * @var array<string, Config>
     */
	private array $configs = [];
    private string $roleFolder;
    public function __construct(string $dataFolder)
    {
        parent::__construct($dataFolder);
        $this->roleFolder =Path::join($this->dataFolder, "roles");
    }


    public function newConfig(Role $role): Generator
    {
       return Await::promise(function($resolve) use ($role) {
           $name = $role->getName();
           $this->configs[$role->getId()] = $config = new Config(Path::join($this->roleFolder, "$name.role.yml"), Config::YAML);
           $config->setAll($role->jsonSerialize());
           $config->save();
           $resolve();
       });
    }

    public function loadConfig(): Generator
    {
        return Await::promise(function ($resolve, $reject): void {
            foreach (PathScanner::scanDirectoryToConfig($this->roleFolder, ["yml"]) as $_ => $config){
                $name = $config->get("name");
                if (!$name)
                    continue;
                RoleManager::getInstance()->addRole(new Role(
                    $id = Utils::roleStringToId($name = Utils::removeColorInRole($name)),
                    $config->get('name'),
                    $config->get('image', ""),
                    $config->get('default'),
                    $config->get('priority', 0),
                    array_map(fn (string $role) => Utils::roleStringToId($role), $config->get('heritages', [])),
                    $config->get('permissions', []),
                    $config->get('chatFormat', ""),
                    $config->get('nameTagFormat', ""),
                    $config->get('changeName')
                ));
                $this->configs[$id] = $config;
            }
            $resolve();
        });
    }

    public function getName() : string
	{
		return "YAML System";
	}
    public function update(string $id, string $type, mixed $data): mixed
    {
        return Await::promise(function ($resolve, $reject) use ($id, $type, $data) {
            if ($type === "create"){
                if(
                    $data instanceof Role
                ){
                    Await::g2c($this->newConfig($data), $resolve, $reject);
                }
                return;
            }
            if(!isset($this->configs[$id])) {
                $reject();
                return ;
            }
            $config = $this->configs[$id];
            $info = null;
            switch ($type) {
                case "delete":
                {
                    try {
                        unlink($config->getPath());
                    }catch (\Throwable $exception){
                        $reject($exception);
                    }
                    break;
                }
                case "image": {
                    $config->set("image", $data);
                    $info = $data;
                    break;
                }
                case "default": {
                    $config->set("default", $data);
                    $info = $data;
                    break;
                }
                case "priority": {
                    $config->set("priority", $data);;
                    $info = $data;
                    break;
                }
                case "chatFormat": {
                    $config->set("chatFormat", $data);
                    $info = $data;
                    break;
                }
                case "nameTagFormat": {
                    $config->set("nameTagFormat", $data);
                    $info = $data;
                    break;
                }
                case "set.heritages": {
                    $info = $data;
                    $config->set("heritages", $data);
                    break;
                }
                case "add.heritages": {
                    $info = array_merge($data, $config->get("heritages", []));
                    $config->set("heritages", $info);
                    break;
                }
                case "sub.heritages": {
                    $info = array_diff( $config->get("heritages", []), $data);
                    $config->set("heritages", $info);
                    break;
                }
                case "set.permissions": {
                    $config->set("permissions", $data);
                    $info = $data;
                    break;
                }
                case "add.permissions": {
                    $info = array_merge($data, $config->get("permissions", []));
                    $config->set("permissions", $info);
                    break;
                }
                case "sub.permissions": {
                    $info = array_diff( $config->get("permissions", []), $data);
                    $config->set("permissions", $info);
                    break;
                }
            }
            $config->save();
            $resolve($info);
        });
    }

    public function createConfigRole(string $name): Generator
    {
        // TODO: Implement createConfigRole() method.
    }
}
