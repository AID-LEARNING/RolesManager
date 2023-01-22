<?php
namespace SenseiTarzan\RoleManager\Component;


use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\ModalForm;
use jojoe77777\FormAPI\SimpleForm;
use JsonException;
use MongoDB\Client;
use MongoDB\Exception\Exception;
use pocketmine\item\ItemIds;
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
use SenseiTarzan\IconUtils\IconForm;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;
use SenseiTarzan\Path\PathScanner;
use SenseiTarzan\RoleManager\Class\Role\Role;
use SenseiTarzan\RoleManager\Class\Save\JSONSave;
use SenseiTarzan\RoleManager\Class\Save\YAMLSave;
use SenseiTarzan\RoleManager\Commands\args\RoleArgument;
use SenseiTarzan\RoleManager\Event\EventChangeRole;
use SenseiTarzan\RoleManager\Utils\CustomKnownTranslationFactory;
use SenseiTarzan\RoleManager\Utils\CustomKnownTranslationKeys;
use SenseiTarzan\RoleManager\Utils\Utils;
use Symfony\Component\Filesystem\Path;


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
        $this->config = $pl->getConfig();

        $this->server = Server::getInstance();
        $this->LoadRoles();
        $this->loadPermission();
    }


    public function LoadRoles(): void
    {

        foreach (PathScanner::scanDirectoryToConfig(Path::join($this->plugin->getDataFolder(), "roles/"), ['yml']) as $directoryFile => $info_role) {
            $this->addRole(Role::create(
                $this->plugin,
                $info_role->get('name'),
                IconForm::create($info_role->get('image', "")),
                $info_role->get('default', false),
                $info_role->get('priority', 0),
                array_map(fn(string $role) => Utils::roleStringToId($role), $info_role->get('heritages', [])),
                $info_role->get('permissions', []),
                $info_role->get('chatFormat', ""),
                $info_role->get('nameTagFormat', ""),
                $info_role->get('changeName', false),
                $info_role
            ));
        }
    }

    /**
     * @param string $name
     * @param IconForm $image
     * @param bool $default
     * @param float $priority
     * @param array $heritages
     * @param array $permissions
     * @param string $chatFormat
     * @param string $nameTagFormat
     * @param bool $changeName
     * @return void
     */
    public function createRole(string $name, IconForm $image, bool $default, float $priority, array $heritages, array $permissions, string $chatFormat, string $nameTagFormat, bool $changeName): Role
    {
        $role = Role::create(
            $this->plugin,
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
        $this->addRole($role, true);
        return $role;
    }


    public function addRole(Role $role, bool $overwrite = false): void
    {
        if (array_key_exists($role->getId(), $this->getRoles())) return;
        if ($role->isDefault() && (!isset($this->defaultRole) || $overwrite)) {
            RoleArgument::$VALUES['default'] = $role->getId();
            $this->setDefaultRole($role);
        }
        RoleArgument::$VALUES[strtolower($role->getName())] = $role->getId();
        $this->roles[$role->getId()] = $role;
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
            if (!$defaultRole->isDefault()) $defaultRole->setDefault(true);
        }
        $this->defaultRole = $defaultRole;
    }

    public function getExcludeNameRole(): array
    {
        return $this->config->get("exclude-name-role", $this->getRoles(true));
    }


    /**
     * @param string $role id|name
     * @return Role
     */
    public function getRole(string $role): Role
    {
        return $this->roles[Utils::roleStringToId($role)] ?? $this->getDefaultRole();
    }

    /**
     * @param string $role id|name
     * @return Role
     */
    public function getRoleNullable(string $role): ?Role
    {
        return $this->roles[Utils::roleStringToId($role)] ?? null;
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
    public function getPermissionHeritage(string $role): array
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
        if (is_array($heritages)) {
            $heritages = array_filter($heritages, fn($name) => $name !== $role && $this->isRole($name));
            if (empty($heritages)) return;
        } elseif ($heritages === $role && $this->isRole($heritages)) return;

        $this->getRole($role)->addHeritages($heritages);

    }

    private function isRole(string $role): bool
    {
        return isset($this->roles[$role]);
    }


    /**
     * @param string $role
     * @param array|string $permission
     */
    public function addPermissionRole(string $role, array|string $permission): void
    {
        $this->getRole($role)->addPermission($permission);
    }

    /**
     * @param string $role
     * @param array|string $permission
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
     * @param Role|string $role
     */
    public function setRolePlayer(Player|string $player, Role|string $role): void
    {

        if (is_string($role)) {
            $role = $this->getRole($role);
        }
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
     * @param array|string $permission
     */
    public function removePermissionPlayer(Player|string $player, array|string $permission): void
    {
        $player = $player instanceof Player ? $player->getName() : $player;
        $this->updateDataPlayer($player, $permission, "removePermissions");
    }


    /**
     * @param string|Player $player
     * @param array|string|Role $data
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
        if (is_string($player)) {
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

    public function createRoleUI(Player $player): void
    {
        $ui = new CustomForm(function (Player $player, ?array $args): void {
            if (!$args) {
                return;
            }
            $name = $args[0];
            $image = IconForm::create($args[2]);
            $default = $args[3];
            $priority = $args[5];
            $heritages = array_values(array_filter(explode(";", $args[7]), fn($heritage) => $heritage !== ""));
            $permissions = array_values(array_filter(explode(";", $args[9]), fn($permission) => $permission !== ""));
            $chatFormat = $args[10];
            $nameTagFormat = $args[11];
            $changeName = $args[12];

            $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player,
                CustomKnownTranslationFactory::message_create_role(
                    $this->createRole($name, $image, $default, $priority, $heritages, $permissions, $chatFormat, $nameTagFormat, $changeName)->getName())
            )
            );
        });
        $ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_create_role()));
        $ui->addInput("name Role", "King");// 0
        $ui->addLabel(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::exemple_image_label())); // 1
        $ui->addInput("image", "path/tete", "");// 2
        $ui->addToggle("default", false); // 3
        $ui->addLabel(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::exemple_priority_label()));// 4
        $ui->addInput("Priority", 0, 0);// 5
        $ui->addLabel(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::exemple_heritages_label())); // 6
        $ui->addInput("Heritages", "", "");// 7
        $ui->addLabel(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::exemple_permissions_label())); // 8
        $ui->addInput("Permissions", "", "");// 9
        $ui->addInput("Chat Format", "§7[§r{&prefix}§7]§r[§6{&role}§r]{&playerName}§7[{&suffix}§7]§r: §r{&message}", "§7[§r{&prefix}§7]§r[§6{&role}§r]{&playerName}§7[{&suffix}§7]§r: §r{&message}");// 10
        $ui->addInput("NameTag Format", "[§6{&role}§r]{&playerName}", "[§6{&role}§r]{&playerName}");// 11
        $ui->addToggle("changeName", false); // 12
        $player->sendForm($ui);
    }

    public function modifiedRoleSelectUI(Player $player): void
    {
        $ui = new SimpleForm(function (Player $player, ?string $roleId): void {
            if (!$roleId) return;
            $role = $this->getRoleNullable($roleId);
            if ($role === null) return;
            $this->modifiedRoleIndexUI($player, $role);
        });
        $ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_select_role()));
        foreach ($this->getRoles() as $role) {
            $ui->addButton($role->getName(), $role->getImage()->getType(), $role->getImage()->getPath(), $role->getId());
        }
        $player->sendForm($ui);
    }

    private function modifiedRoleIndexUI(Player $player, Role $role): void
    {
        $ui = new SimpleForm(function (Player $player, ?int $button) use ($role): void {
            if ($button === false) return;
            match ($button) {
                0 => $this->modifiedRoleGeneralUI($player, $role),
                1 => $this->modifiedRoleDefaultUI($player, $role),
                4 => $this->removeRoleUI($player, $role),
                default => null
            };
        });

        $ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_select_type()));
        $ui->addButton(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::button_modified_general()));
        $ui->addButton(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::button_modified_default()));
        $ui->addButton(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::button_modified_permissions()));
        $ui->addButton(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::button_modified_heritages()));
        $ui->addButton(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::button_modified_remove()));
        $player->sendForm($ui);

    }

    private function modifiedRoleGeneralUI(Player $player, Role $role): void
    {
        $ui = new CustomForm(function (Player $player, ?array $data) use ($role): void {
            if (!$data) return;
            list($changeName, $image, $priority, $chatFormat, $nameTagFormat) = $data;
            if ($changeName !== $role->isChangeName()) $role->setChangeName($changeName);
            if ($image !== $role->getImage()->getPath()) $role->setImage($image);
            if ($priority !== $role->getPriority()) $role->setPriority($priority);
            if ($chatFormat !== $role->getChatFormat()) $role->setChatFormat($chatFormat);
            if ($nameTagFormat !== $role->getNameTagFormat()) $role->setNameTagFormat($nameTagFormat);

        });
        $ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_modified_general()));
        $ui->addToggle("changeName", $role->isChangeName()); //0
        $ui->addInput("Image", $role->getImage()->getPath(), $role->getImage()->getPath()); //1
        $ui->addInput("Priority", $role->getPriority(), $role->getPriority()); // 2
        $ui->addInput("Chat Format", $role->getChatFormat(), $role->getChatFormat());// 3
        $ui->addInput("NameTag Format", $role->getNameTagFormat(), $role->getNameTagFormat());// 4
        $player->sendForm($ui);
    }

    private function modifiedRoleDefaultUI(Player $player, Role $role): void
    {
        $ui = new ModalForm(function (Player $player, ?bool $default) use ($role): void {
            if ($default === null) return;
            if ($role->getId() === $this->getDefaultRole()->getId() || $default === false) {
                $this->modifiedRoleIndexUI($player, $role);
                return;
            }
            $this->setDefaultRole($role);
            $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::set_default_role_sender($role->getName())));
        });
        $ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_modified_default()));
        $ui->setContent(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::description_modified_default()));
        $ui->setButton1(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_accept()));
        $ui->setButton2(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_denied()));
        $player->sendForm($ui);
    }

    private function removeRoleUI(Player $player, Role $role): void
    {
        $ui = new ModalForm(function (Player $player, ?bool $remove) use ($role): void {
            if ($remove === null) return;
            if ($role->getId() === $this->getDefaultRole()->getId() || $remove === false) {
                $this->modifiedRoleIndexUI($player, $role);
                return;
            }
            if (@unlink($role->getConfig()->getPath())) {
                unset($this->roles[$role->getId()]);
                $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::remove_role($role->getName())));
            }
        });
        $ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_modified_remove()));
        $ui->setContent(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::description_modified_remove()));
        $ui->setButton1(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_accept()));
        $ui->setButton2(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_denied()));
        $player->sendForm($ui);
    }
}
