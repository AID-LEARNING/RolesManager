<?php

namespace SenseiTarzan\RoleManager\Class\Save;

use JsonException;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use SenseiTarzan\RoleManager\Class\Role\RolePlayer;
use SenseiTarzan\RoleManager\Component\RoleManager;
use SenseiTarzan\RoleManager\Component\RolePlayerManager;
use Symfony\Component\Filesystem\Path;

class JSONSave implements IDataSave
{

    private Config $config;

    public function __construct(string $dataFolder)
    {
        $this->config = new Config(Path::join($dataFolder, "data.json"), Config::JSON);
    }

    public function getName(): string
    {
        return "Json System";
    }

    public function loadDataPlayer(Player $player): void
    {
        if (!$this->config->exists($name = $player->getName(), true)) {
            RolePlayerManager::getInstance()->loadPlayer($player, $rolePlayer = new RolePlayer($name, prefix: "", suffix: "", role: RoleManager::getInstance()->getDefaultRole()->getId(), nameRoleCustom: null));
            $this->config->set(strtolower($name), $rolePlayer->jsonSerialize());
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
     * @throws JsonException
     */
    public function updateOnline(string $id, string $type, mixed $data): void
    {
        $this->config->setNested($id. ".$type", $data);
        $this->config->save();
    }


    /**
     * @param string $id
     * @param string $type
     * @param mixed $data 'role' | 'addPermission' | 'removePermission' | 'setPermission'
     * @return void
     * @throws JsonException
     */
    public function updateOffline(string $id, string $type, mixed $data): void
    {
        if (!$this->config->exists($id, true)){
            $rolePlayer = new RolePlayer($id, prefix: "", suffix: "", role: RoleManager::getInstance()->getDefaultRole()->getId(), nameRoleCustom: null);
            $this->config->set($rolePlayer->getId(), $rolePlayer->jsonSerialize());
        }
        $this->config->setNested($search = (strtolower($id) . "." . (match ($type) {
                "addPermissions", "removePermissions", "setPermissions" => 'permissions',
                default => $type
            })), match ($type) {
            "addPermissions" => $this->config->getNested($search) + (is_string($data) ? [$data] : $data),
            "removePermissions" => array_diff($this->config->getNested($search), (is_string($data) ? [$data] : $data)),
            "setPermissions" => is_string($data) ? [$data] : $data,
            default => $data
        });
        $this->config->save();
    }
}