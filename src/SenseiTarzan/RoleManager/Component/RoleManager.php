<?php
namespace SenseiTarzan\RoleManager\Component;


use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use JsonException;
use MongoDB\Client;
use MongoDB\Exception\Exception;
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;
use SenseiTarzan\Path\PathScanner;
use SenseiTarzan\RoleManager\Class\Role\Role;
use SenseiTarzan\RoleManager\Class\Save\JSONSave;
use SenseiTarzan\RoleManager\Class\Save\YAMLSave;
use SenseiTarzan\RoleManager\Commands\args\RoleArgument;
use SenseiTarzan\RoleManager\Event\EventChangeRole;
use SenseiTarzan\RoleManager\Utils\CustomKnownTranslationFactory;
use SenseiTarzan\RoleManager\Utils\CustomKnownTranslationKeys;
use Webmozart\PathUtil\Path;


class RoleManager
{
    use SingletonTrait;

    /**
     * @var Role[]
     */
    public array $roles = [];

    private PluginBase $plugin;

    private Server $server;
    private Config $config;
    private Role $defaultRole;

    public function __construct(PluginBase $pl)
    {
        self::setInstance($this);
        $this->plugin = $pl;
        $this->config = new Config($pl->getDataFolder() . 'config.yml', Config::YAML);
        DataManager::getInstance()->setSaveDataSystem(match (strtolower($this->config->get("data-type", "json"))){
            "yml", "yaml" => new YAMLSave($pl->getDataFolder()),
            "json" => new JSONSave($pl->getDataFolder()),
            default => null
        });
        $this->server = Server::getInstance();
        $this->LoadRoles();
        $this->loadPermission();
    }


    public function LoadRoles(): void
    {

        foreach (PathScanner::scanDirectoryToConfig($this->plugin->getDataFolder() . "roles/", ['yml']) as $directoryFile => $info_role) {
            $this->roles[$id = str_replace(" ", "_", strtolower($name = str_replace(array_values(TextFormat::COLORS), "", $info_role->get('name'))))] = $role = new Role(
                $id,
                $name,
                $info_role->get('default', false),
                $info_role->get('priority', 0),
                $info_role->get('heritages', []),
                $info_role->get('permissions', []),
                $info_role->get('chatFormat', ""),
                $info_role->get('nameTagFormat', ""),
                $info_role->get('changeName', false),
                $info_role
            );
            RoleArgument::$VALUES[strtolower($name)] = $id;
            if ($role->isDefault() && !isset($this->defaultRole)) {
                RoleArgument::$VALUES['default'] = $id;
                $this->setDefaultRole($role);
            }
        }
        unset($chat);
    }

    public function getDefaultRole(): Role
    {
        return $this->defaultRole;
    }

    /**
     * @param Role $defaultRole
     */
    public function setDefaultRole(Role $defaultRole): void
    {
        if (isset($this->defaultRole)) {
            $this->defaultRole->setDefault(false);
            $defaultRole->setDefault(true);
        }
        $this->defaultRole = $defaultRole;
    }

    public function getExcludeNameRole(): array
    {
        return $this->config->get("excludeNameRole", $this->getRoles(true));
    }


    /**
     * @param string $role
     * @return Role
     */
    public function getRole(string $role): Role
    {
        return $this->roles[$role] ?? $this->getDefaultRole();
    }

    public function loadPermission(): void
    {
        $op = PermissionManager::getInstance()->getPermission(DefaultPermissions::ROOT_OPERATOR);
        foreach ($this->roles as $role => $info) {
            foreach ($info->getPermissions() as $permission) {
                if (PermissionManager::getInstance()->getPermission($permission) === null) {
                    PermissionManager::getInstance()->addPermission(new Permission($permission, "$role Role permission"));
                    $op->addChild($permission, true);
                }
            }
        }
    }

    /**
     * @param string $role
     * @return array
     */
    public function getPermissionRole(string $role): array
    {
        return $this->getRole($role)->getPermissions();
    }

    /**
     * @param string $role
     * @return array
     */
    public function  getPermissionHeritage(string $role): array
    {
        return $this->getRole($role)->getHeritagesPermissions();
    }

    public function addPermissions(Player $player, array $permissions): void
    {
        if ($player->isConnected()) {
            $attache = $player->addAttachment($this->plugin);
            $attache->clearPermissions();
            $attache->setPermissions($permissions);
        }
    }

    /**
     * @param string $role
     * @return float
     */
    public function getPriorityRole(string $role): float
    {
        return $this->getRole($role)->getPriority();
    }

    /**
     * @return Role[]|string[]
     */
    public function getRoles(bool $keys = false): array
    {
        return $keys ? array_keys($this->roles) : $this->roles;
    }

    /**
     * @param string $role
     * @param array|string $heritages
     */
    public function addHeritageRole(string $role, array|string $heritages): void
    {
        if (is_array($heritages)){
            $heritages = array_filter($heritages, fn ($name) => $name !== $role &&$this->isRole($name));
            if (empty($heritages)) return;
        }elseif($heritages === $role && $this->isRole($heritages)) return;

        $this->getRole($role)->addHeritages($heritages);

    }

    private function isRole(string $role): bool
    {
        return isset($this->roles[$role]);
    }


    /**
     * @param string $role
     * @param string $permission
     */
    public function addPermissionRole(string $role, array|string $permission): void
    {
        $this->getRole($role)->addPermission($permission);
    }

    /**
     * @param string $role
     * @param string $permission
     */
    public function removePermissionRole(string $role, array|string $permission): void
    {
        $this->getRole($role)->removePermission($permission);
    }

    /**
     * @return Player[]
     */
    public function getPlayerInServer(): array
    {
        return array_values($this->server->getOnlinePlayers());
    }

    /**
     * @param string|Player $player
     * @param string $role
     */
    public function setRolePlayer(Player|string $player, Role $role): void
    {

        if (!is_string($player)) {
            $event = new EventChangeRole($player, RolePlayerManager::getInstance()->getPlayer($player)->getRole(), $role);
            $event->call();
            if ($event->isCancelled()) return;
            $role = $event->getNewRole();
        }
        $this->updateDataPlayer($player, $role);
    }

    /**
     * @param Player $player
     * @param string $prefix
     */
    public function setPrefix(Player $player, string $prefix): void
    {
        $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::set_prefix_target($prefix)));
        RolePlayerManager::getInstance()->getPlayer($player)->setPrefix($prefix);
    }

    /**
     * @param Player $player
     * @param string $roleNameCustom
     */
    public function setNameRoleCustom(Player $player, string $roleNameCustom): void
    {
        $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::set_name_role_custom_target($roleNameCustom)));
        RolePlayerManager::getInstance()->getPlayer($player)->setRoleNameCustom($roleNameCustom);
    }

    /**
     * @param Player $player
     * @param string $suffix
     */
    public function setSuffix(Player $player, string $suffix): void
    {
        $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::set_suffix_target($suffix)));
        RolePlayerManager::getInstance()->getPlayer($player)->setSuffix($suffix);
    }

    /**
     * @param string|Player $player
     * @param array|string $permission
     */
    public function addPermissionPlayer(Player|string $player, array|string $permission): void
    {
        $this->updateDataPlayer($player, $permission, "addPermissions");
    }

    /**
     * @param string|Player $player
     * @param array|string $permission
     */
    public function setPermissionPlayer(Player|string $player, array|string $permission): void
    {
        $this->updateDataPlayer($player, $permission, "setPermissions");
    }

    /**
     * @param string|Player $player
     * @param string $permission
     */
    public function removePermissionPlayer(Player|string $player, array|string $permission): void
    {
        $player = $player instanceof Player ? $player->getName() : $player;
        $this->updateDataPlayer($player, $permission, "removePermissions");
    }


    /**
     * @param string|Player $player
     * @param array|string $data
     * @param string $type
     */
    private function updateDataPlayer(Player|string $player, array|string|Role $data, string $type = "role"): void
    {
        $target = RolePlayerManager::getInstance()->getPlayer($player);
        $isPlayer = $player instanceof Player;

        $isArray = is_array($data);
        if ($target === null && !$isPlayer) {
            DataManager::getInstance()->getDataSystem()->updateOffline($player, $type, $data);
            return;
        }
        if (is_string($player)){
            $player = Server::getInstance()->getPlayerExact($player) ?? $player;
            $isPlayer = $player instanceof Player;
        }
        if (!$isPlayer) return;
        if ($player->isConnected()) {
            switch ($type) {
                case "role":
                    if (!($data instanceof Role)) return;
                    $target->setRole($data->getId());
                    $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::set_role_target($data)));
                    break;
                case "addPermissions":
                    $target->addPermissions($data);
                    $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::add_permissions_target($data)));

                    break;
                case "removePermissions":
                    $target->removePermissions($data);
                    $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::remove_permissions_target($data)));

                    break;
                case "setPermissions":
                    $target->setPermissions($data);
                    $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::set_permissions_target($data)));
                    break;
            }

            RolePlayerManager::getInstance()->loadPermissions($player, $target);
        }
    }

    public function createRoleUI(Player $player): void{
        $ui = new CustomForm(function (Player $player, ?array $args){

        });
        $ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_create_role()));
        $ui->addInput("name Role", "King");// 0
        $ui->addLabel(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::exemple_heritages_label())); //1
        $ui->addInput("Chat Format","§7[§r{&prefix}§7]§r[§6{&role}§r]{&playerName}§7[{&suffix}§7]§r: §r{&message}",  "§7[§r{&prefix}§7]§r[§6{&role}§r]{&playerName}§7[{&suffix}§7]§r: §r{&message}");//2
        $ui->addLabel(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::exemple_heritages_label())); //1
        $ui->addInput("NameTag Format","[§6{&role}§r]{&playerName}",  "[§6{&role}§r]{&playerName}");//2
        $ui->addLabel(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::exemple_heritages_label())); //1
        $ui->addInput("Heritages","");//2
        $ui->addLabel(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::exemple_heritages_label())); //1
        $ui->addInput("Heritages","");//2
        $ui->addLabel(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::exemple_heritages_label()));//3
        $ui->addInput("Permissions",DefaultPermissionNames::COMMAND_ME . ";" . DefaultPermissionNames::COMMAND_TELL);//4
        $ui->addLabel(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::exemple_heritages_label()));//3
        $ui->addInput("Priority",DefaultPermissionNames::COMMAND_ME . ";" . DefaultPermissionNames::COMMAND_TELL);//4
    }

}
