<?php

namespace SenseiTarzan\RoleManager\Class\Save;

use pocketmine\player\Player;
use pocketmine\utils\Config;
use SenseiTarzan\DataBase\Class\IDataSave;
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

    /**
     * @throws \JsonException
     */
    public function loadDataPlayer(Player|string $player): void
    {
        if (!$this->config->exists($name = $player->getName(), true)) {
            RolePlayerManager::getInstance()->loadPlayer($player, $rolePlayer = new RolePlayer($name, prefix: "", suffix: "", role: RoleManager::getInstance()->getDefaultRole()->getId(), subRoles: [], nameRoleCustom: null));
            $this->config->set($rolePlayer->getId(), $rolePlayer->jsonSerialize());
            $this->config->save();
            return;
        }
        $infoPlayer = $this->config->get(strtolower($name));
        RolePlayerManager::getInstance()->loadPlayer($player, new RolePlayer($name, $infoPlayer['prefix'] ?? "", $infoPlayer['suffix'] ?? "", $infoPlayer['role'] ?? RoleManager::getInstance()->getDefaultRole()->getId(),$infoPlayer['subRoles'] ?? [],$infoPlayer['nameRoleCustom'] ?? null, array_values($infoPlayer['permissions'] ?? [])));
    }


    /**
     * @param string $id
     * @param string $type 'role' | 'suffix' | 'prefix' | 'permissions' | 'nameRoleCustom' | 'SubRoles'
     * @param mixed $data
     * @return mixed
     * @throws \JsonException
     */
    public function updateOnline(string $id, string $type, mixed $data): bool
    {
        $this->config->setNested($id. ".$type", $data);
        $this->config->save();
        return false;
    }


    /**
     * @param string $id
     * @param string $type 'role' | 'addPermission' | 'removePermission' | 'setPermission' | 'addSubRoles' | 'removeSubRoles' | 'setSubRoles'
     * @param mixed $data
     * @return mixed
     * @throws \JsonException
     */
    public function updateOffline(string $id, string $type, mixed $data): bool
    {
        if (!$this->config->exists($id, true)){
            $this->config->set(strtolower($id), (new RolePlayer($id, prefix: "", suffix: "", role: RoleManager::getInstance()->getDefaultRole()->getId(), subRoles: [], nameRoleCustom: null))->jsonSerialize());
        }
        $this->config->setNested($search = (strtolower($id) . "." . (match ($type) {
                "addPermissions", "removePermissions", "setPermissions" => 'permissions',
                "addSubRoles", "removeSubRoles", "setSubRoles" => 'subRoles',
                default => $type
            })), match ($type) {
            "addPermissions", "addSubRoles" => array_merge($dataInSave = $this->config->getNested($search), array_filter((is_string($data) ? [$data] : $data), fn(string $value) => (($type !== "addSubRoles") || RoleManager::getInstance()->existRole($value)) && !in_array($value, $dataInSave))) ,
            "removePermissions", "removeSubRoles" => array_values(array_diff($this->config->getNested($search), (is_string($data) ? [$data] : $data))),
            "setPermissions", "setSubRoles" => is_string($data) ? [$data] : $data,
            default => $data
        });
        $this->config->save();
        return false;
    }
}