<?php

namespace SenseiTarzan\RoleManager\Class\Save;

use pocketmine\player\Player;
use pocketmine\utils\Config;
use SenseiTarzan\RoleManager\Class\Role\Role;
use SenseiTarzan\RoleManager\Class\Role\RolePlayer;
use SenseiTarzan\RoleManager\Component\RoleManager;
use SenseiTarzan\RoleManager\Component\RolePlayerManager;
use Symfony\Component\Filesystem\Path;

class YAMLSave implements IDataSave
{

    private Config $config;

    public function __construct(string $dataFolder)
    {
        $this->config = new Config(Path::join($dataFolder, "data.yml"), Config::YAML);
    }

    public function getName(): string
    {
        return "Yaml System";
    }

    public function loadDataPlayer(Player $player): void
    {
        if (!$this->config->exists($name = $player->getName(), true)) {
            RolePlayerManager::getInstance()->loadPlayer($player, $rolePlayer = new RolePlayer($name, prefix: "", suffix: "", role: RoleManager::getInstance()->getDefaultRole()->getId(), nameRoleCustom: null));
            $this->config->set($rolePlayer->getId(), $rolePlayer->jsonSerialize());
            $this->config->save();
            return;
        }
        $infoPlayer = $this->config->get(strtolower($name));
        RolePlayerManager::getInstance()->loadPlayer($player, new RolePlayer($name, $infoPlayer['prefix'] ?? "", $infoPlayer['suffix'] ?? "", $infoPlayer['role'] ?? RoleManager::getInstance()->getDefaultRole()->getId(),$infoPlayer['nameRoleCustom'] ?? null, $infoPlayer['permissions'] ?? []));

    }


    /**
     * @param string $id
     * @param string $type 'role' | 'suffix' | 'prefix' | 'permissions' | 'nameRoleCustom'
     * @param mixed $data
     * @return void
     * @throws \JsonException
     */
    public function updateOnline(string $id, string $type, mixed $data): void
    {
        $this->config->setNested($id. ".$type", $data);
        $this->config->save();
    }


    /**
     * @param string $id
     * @param string $type 'role' | 'addPermission' | 'removePermission' | 'setPermission'
     * @param mixed $data
     * @return void
     * @throws \JsonException
     */
    public function updateOffline(string $id, string $type, mixed $data): void
    {
        if (!$this->config->exists($id, true)){
            $this->config->set(strtolower($id), (new RolePlayer($id, prefix: "", suffix: "", role: RoleManager::getInstance()->getDefaultRole()->getId(), nameRoleCustom: null))->jsonSerialize());
        }
        $this->config->setNested($search = (strtolower($id) . "." . (match ($type) {
                "addPermissions", "removePermissions", "setPermissions" => 'permissions',
                default => $type
            })), match ($type) {
            "addPermissions" => array_merge($this->config->getNested($search) ,(is_string($data) ? [$data] : $data)) ,
            "removePermissions" => array_values(array_diff($this->config->getNested($search), (is_string($data) ? [$data] : $data))),
            "setPermissions" => is_string($data) ? [$data] : $data,
            default => $data
        });
        $this->config->save();
    }
}