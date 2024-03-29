<?php
namespace SenseiTarzan\RoleManager\Component;


use Exception;
use Generator;
use InvalidArgumentException;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\ModalForm;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\permission\DefaultPermissions;
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
use SenseiTarzan\RoleManager\Class\Save\ResultUpdate;
use SenseiTarzan\RoleManager\Commands\args\RoleArgument;
use SenseiTarzan\RoleManager\Main;
use SenseiTarzan\RoleManager\Utils\CustomKnownTranslationFactory;
use SenseiTarzan\RoleManager\Utils\Utils;
use SOFe\AwaitGenerator\Await;
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
    /**
     * @var array|array[]|false[]|null[]|string[]|\string[][]
     */
    private array $listExcludeName;


    public function __construct(PluginBase $pl)
    {
        self::setInstance($this);
        $this->plugin = $pl;
        $this->config = $pl->getConfig();

        $this->server = Server::getInstance();
        $this->loadRoles();
        $this->listExcludeName = Utils::rolesStringToIdArray(array_merge($this->config->get("exclude-name-role", []), $this->getRoles(true, true)));
    }


    public function loadRoles(): void
    {
        $this->roles = [];
        unset($this->defaultRole);
        RoleArgument::$VALUES = [];
        foreach (PathScanner::scanDirectoryToConfig(Path::join($this->plugin->getDataFolder(), "roles/"), ['yml']) as $info_role) {
            $this->addRole(Role::create(
                $this->plugin,
                $info_role->get('name'),
                $info_role->get('image', ""),
                $info_role->get('default'),
                $info_role->get('priority', 0),
                array_map(fn(string $role) => Utils::roleStringToId($role), $info_role->get('heritages', [])),
                $info_role->get('permissions', []),
                $info_role->get('chatFormat', ""),
                $info_role->get('nameTagFormat', ""),
                $info_role->get('changeName'),
                $info_role
            ));
        }
    }

    /**
     * @param string $name
     * @param string $image
     * @param bool $default
     * @param float $priority
     * @param array $heritages
     * @param array $permissions
     * @param string $chatFormat
     * @param string $nameTagFormat
     * @param bool $changeName
     * @return Role
     */
    public function createRole(string $name, string $image, bool $default, float $priority, array $heritages, array $permissions, string $chatFormat, string $nameTagFormat, bool $changeName): Role
    {

        $this->addRole($role = Role::create(
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
        ), true);
        return $role;
    }

    /**
     * @return array
     */
    private function getPermissionInString(): array{
        return array_map(fn (Permission $value) => $value->getName(), PermissionManager::getInstance()->getPermissions());
    }

    /**
     * @param Role $role
     * @param bool $overwrite
     * @return void
     */
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

    /**
     * @return Role
     */
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

    /**
     * @return array
     */
    public function getExcludeNameRole(): array
    {
        return $this->listExcludeName;
    }


    /**
     * @param string $role id|name
     * @return Role
     */
    public function getRole(string $role): Role
    {
        return $this->roles[Utils::roleStringToId($role)] ?? $this->getDefaultRole();
    }

    public function existRole(string $role): bool
    {
        return isset($this->roles[Utils::roleStringToId($role)]);
    }

    public function getSubRolesPlayer(Player $player): array
    {

        return $player->isConnected() ? RolePlayerManager::getInstance()->getPlayer($player)->getSubRoles() : [];
    }

    /**
     * @param Player|string $player
     * @param array|string|Role|Role[] $roles
     * @return Generator
     * @throws CancelEventException
     */
    public function setSubRolesPlayer(Player|string $player, array|string|Role $roles): Generator
    {
        return $this->updateDataPlayer($player, $roles, 'setSubRoles');
    }

    /**
     * @param Player|string $player
     * @param array|string|Role|Role[] $roles
     * @return Generator
     * @throws CancelEventException
     */
    public function addSubRolesPlayer(Player|string $player, array|string|Role $roles): Generator
    {
       return $this->updateDataPlayer($player, $roles, 'addSubRoles');
    }

    /**
     * @param Player|string $player
     * @param array|string|Role|Role[] $roles
     * @return Generator
     * @throws CancelEventException
     */
    public function removeSubRolesPlayer(Player|string $player, array|string|Role $roles): Generator
    {
        return $this->updateDataPlayer($player, $roles, 'removeSubRoles');
    }

    /**
     * @param string $role id|name
     * @return Role|null
     */
    public function getRoleNullable(string $role): ?Role
    {
        return $this->roles[Utils::roleStringToId($role)] ?? null;
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

    /**
     * @param string $role
     * @return array
     */
    public function getHeritages(string $role): array
    {
        return $this->getRole($role)->getAllHeritages();
    }

    public function addPermissions(RolePlayer $rolePlayer, array $permissions): void
    {
        $attachment = $rolePlayer->getAttachment();
        $attachment?->clearPermissions();
        $attachment?->setPermissions($permissions);
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
    public function getRoles(bool $keys = false, bool $name = false): array
    {
        return $keys ? ($name ? array_map(fn(Role $role) => $role->getName(), $this->roles) : array_keys($this->roles)) : $this->roles;
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
     * @return Generator
     * @throws CancelEventException
     */
    public function setRolePlayer(Player|string $player, Role|string $role): Generator
    {

        if (is_string($role)) {
            $role = $this->getRole($role);
        }
        return $this->updateDataPlayer($player, $role);
    }

    /**
     * @param Player $player
     * @param string $prefix
     * @return Generator
     */
    public function setPrefix(Player $player, string $prefix): Generator
    {
        return RolePlayerManager::getInstance()->getPlayer($player)->setPrefix($prefix);
    }

    /**
     * @param Player $player
     * @param string $roleNameCustom
     * @return Generator
     * @throws CancelEventException
     */
    public function setNameRoleCustom(Player $player, string $roleNameCustom): Generator
    {
        return RolePlayerManager::getInstance()->getPlayer($player)->setRoleNameCustom($roleNameCustom);
    }

    /**
     * @param Player $player
     * @param string $suffix
     * @return Generator
     */
    public function setSuffix(Player $player, string $suffix): Generator
    {
        return RolePlayerManager::getInstance()->getPlayer($player)->setSuffix($suffix);
    }

    /**
     * @param string|Player $player
     * @param array|string $permission
     */
    public function addPermissionPlayer(Player|string $player, array|string $permission): Generator
    {
        return $this->updateDataPlayer($player, $permission, "addPermissions");
    }

    /**
     * @param string|Player $player
     * @param array|string $permission
     */
    public function setPermissionPlayer(Player|string $player, array|string $permission): Generator
    {
        return $this->updateDataPlayer($player, $permission, "setPermissions");
    }

    /**
     * @param string|Player $player
     * @param array|string $permission
     * @throws CancelEventException
     */
    public function removePermissionPlayer(Player|string $player, array|string $permission): Generator
    {
        return $this->updateDataPlayer($player, $permission, "removePermissions");
    }


    /**
     * @param string|Player $player
     * @param array|string|Role $raw
     * @param string $type
     * @return Generator
     * @throws CancelEventException
     */
    private function updateDataPlayer(Player|string $player, array|string|Role $raw, string $type = "role"): Generator
    {
        return Await::promise(function($resolve, $reject) use($player, $raw, $type){
            if (is_string($player)) {
                $player = Server::getInstance()->getPlayerExact($player) ?? $player;
            }
            Await::f2c(function () use ($player, $raw, $type): Generator {
                $target = RolePlayerManager::getInstance()->getPlayer($player);
                if ($target === null) {
                    $data = $raw;
                    if ($data instanceof Role) {
                        $data = $data->getId();
                    }else if (is_array($data)) {
                        $data = array_values(array_map(fn(Role|string $value) => ($value instanceof Role ? $value->getId() : $value), $data));
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
            }, function (ResultUpdate $data) use ($player,$resolve){
                if ($data->online && $data->updatePermission) {
                    RolePlayerManager::getInstance()->loadPermissions(RolePlayerManager::getInstance()->getPlayer($player));
                }
                $resolve($data);
            }, $reject);
        });
    }

    public function createRoleUI(Player $player): void
    {
        $ui = new CustomForm(function (Player $player, ?array $args): void {
            if (!$args) {
                return;
            }
            $name = $args[0];
            $image = $args[2];
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
            $this->listExcludeName = array_map(fn (string $name) => mb_strtolower($name),  array_merge($this->config->get("exclude-name-role", []), $this->getRoles(true, true)));
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
            $ui->addButton($role->getName(), ($roleImage = $role->getImage())->getType(), $roleImage->getPath(), $role->getId());
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

    private function permissionsRoleIndexUI(Player $player, Role $role): void{
        $ui = new SimpleForm(function (Player $player, ?int $data) use($role): void{
            if ($data === null){
                $this->modifiedRoleIndexUI($player, $role);
                return;
            }
            match ($data){
                0 => $this->permissionsRoleAddUI($player, $role),
                1 => $this->permissionsRoleRemoveUI($player, $role)
            };
        });

        $ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_modified_permissions($role->getName())));
        $ui->addButton(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::button_permissions_add()));
        $ui->addButton(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::button_permissions_remove()));
        $player->sendForm($ui);
    }

    private function permissionsRoleAddUI(Player $player, Role $role): void{
        $ui = new SimpleForm(function (Player $player, ?string $permissions) use($role): void{
            if ($permissions === null){
                $this->permissionsRoleIndexUI($player, $role);
                return;
            }
            $role->addPermission($permissions);
            $this->permissionsRoleAddUI($player, $role);
        });

        $ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_permissions_add($role->getName())));
        foreach (array_diff($this->getPermissionInString(), $role->getAllPermissions()) as $permission){
            $ui->addButton($permission, label: $permission);
        }
        $player->sendForm($ui);
    }

    private function permissionsRoleRemoveUI(Player $player, Role $role): void{
        $ui = new SimpleForm(function (Player $player, ?string $permissions) use($role): void{
            if ($permissions === null){
                $this->permissionsRoleIndexUI($player, $role);
                return;
            }
            $role->removePermission($permissions);
            $this->permissionsRoleRemoveUI($player, $role);
        });

        $ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_permissions_remove($role->getName())));
        foreach ($role->getPermissions() as $permission){
            $ui->addButton($permission, label: $permission);
        }
        $player->sendForm($ui);
    }

    private function heritagesRoleIndexUI(Player $player, Role $role): void{
        $ui = new SimpleForm(function (Player $player, ?int $data) use($role): void{
            if ($data === null){
                $this->modifiedRoleIndexUI($player, $role);
                return;
            }
            match ($data){
                0 => $this->heritagesRoleAddUI($player, $role),
                1 => $this->heritagesRoleRemoveUI($player, $role)
            };
        });

        $ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_modified_heritages($role->getName())));
        $ui->addButton(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::button_heritages_add()));
        $ui->addButton(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::button_heritages_remove()));
        $player->sendForm($ui);
    }

    private function heritagesRoleAddUI(Player $player, Role $role): void{
        $ui = new SimpleForm(function (Player $player, ?string $permissions) use($role): void{
            if ($permissions === null){
                $this->heritagesRoleIndexUI($player, $role);
                return;
            }
            $role->addHeritages($permissions);
            $this->heritagesRoleAddUI($player, $role);
        });

        $ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_heritages_add($role->getName())));
        foreach (array_diff($this->getRoles(true),$role->getAllHeritages() , [$role->getId()]) as $heritageId){
            $ui->addButton(($role = $this->getRole($heritageId))->getName(), ($roleImage = $role->getImage())->getType(), $roleImage->getPath(), $heritageId);
        }
        $player->sendForm($ui);
    }

    private function heritagesRoleRemoveUI(Player $player, Role $role): void{
        $ui = new SimpleForm(function (Player $player, ?string $permissions) use($role): void{
            if ($permissions === null){
                $this->heritagesRoleIndexUI($player, $role);
                return;
            }
            $role->removeHeritages($permissions);
            $this->heritagesRoleRemoveUI($player, $role);
        });

        $ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_heritages_remove($role->getName())));
        foreach ($role->getHeritages() as $heritageId){
            $ui->addButton(($role = $this->getRole($heritageId))->getName(), ($roleImage = $role->getImage())->getType(), $roleImage->getPath(), $heritageId);
        }
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
        $ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_modified_general($role->getName())));
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
        $ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_modified_default($role->getName())));
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
                $this->listExcludeName = array_map(fn (string $name) => mb_strtolower($name),  array_merge($this->config->get("exclude-name-role", []), $this->getRoles(true, true)));
            }
        });
        $ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::title_modified_remove($role->getName())));
        $ui->setContent(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::description_modified_remove()));
        $ui->setButton1(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_accept()));
        $ui->setButton2(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::buttons_denied()));
        $player->sendForm($ui);
    }
}
