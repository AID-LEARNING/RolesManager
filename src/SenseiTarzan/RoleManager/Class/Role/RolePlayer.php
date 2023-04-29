<?php

namespace SenseiTarzan\RoleManager\Class\Role;

use pocketmine\Server;
use SenseiTarzan\DataBase\Component\DataManager;
use SenseiTarzan\RoleManager\Component\RoleManager;
use SenseiTarzan\RoleManager\Event\EventChangeNameCustom;
use SenseiTarzan\RoleManager\Event\EventChangePrefix;
use SenseiTarzan\RoleManager\Event\EventChangeRole;
use SenseiTarzan\RoleManager\Event\EventChangeSuffix;

class RolePlayer implements \JsonSerializable
{

    private string $id;

    /**
     * RolePlayer constructor.
     * @param string $name
     * @param string $prefix
     * @param string $suffix
     * @param string $role
     * @param string[] $subRoles
     * @param string|null $nameRoleCustom
     * @param array $permissions
     * @throws \JsonException
     */
    public function __construct(private string $name, private string $prefix, private string $suffix, private string $role, private array $subRoles, private string|null $nameRoleCustom, private array $permissions = [])
    {
        $this->id = strtolower($this->name);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @param string $prefix
     */
    public function setPrefix(string $prefix): bool
    {
        $event = new EventChangePrefix(Server::getInstance()->getPlayerExact($this->getName()), $this->getPrefix(), $prefix);
        $event->call();
        if ($event->isCancelled()) return false;
        $this->prefix = $event->getNewPrefix();
        DataManager::getInstance()->getDataSystem()->updateOnline($this->getId(), "prefix", $event->getNewPrefix());
        return true;
    }

    /**
     * @return string
     */
    public function getSuffix(): string
    {
        return $this->suffix;
    }

    /**
     * @param string $suffix
     */
    public function setSuffix(string $suffix): bool
    {
        $event = new EventChangeSuffix(Server::getInstance()->getPlayerExact($this->getName()), $this->getSuffix(), $suffix);
        $event->call();
        if ($event->isCancelled()) return false;
        $this->suffix = $event->getNewSuffix();
        DataManager::getInstance()->getDataSystem()->updateOnline($this->getId(), "suffix", $event->getNewSuffix());
        return true;
    }


    /**
     * @return Role
     */
    public function getRole(): Role
    {
        return RoleManager::getInstance()->getRole($this->role);
    }

    /**
     * @return array
     */
    public function getSubRoles(): array
    {
        return $this->subRoles;
    }

    public function hasSubRole(string $role): bool
    {
        return in_array($role, $this->subRoles, true) || $this->role === $role || in_array($role, $this->getRole()->getAllHeritages(), true);
    }

    /**
     * @param array $roles
     * @return void
     */
    public function filterNoHasSubRoles(array &$roles): void
    {
        foreach ($roles as $index => $role) {
            if ($role instanceof Role) {
                if (!$this->hasSubRole($role->getId())) continue;
                unset($roles[$index]);
                continue;
            }
            if (!RoleManager::getInstance()->existRole($role)) {

                unset($roles[$index]);
                continue;
            }

            if (!$this->hasSubRole($role)) continue;
            unset($roles[$index]);
        }
    }

    /**
     * @param array|string|Role $roles
     * @return void
     */
    public function addSubRole(array|string|Role &$roles): void
    {
        if (is_array($roles)) {
            $this->filterNoHasSubRoles($roles);
        } else if (is_string($roles)) {
            if (!RoleManager::getInstance()->existRole($roles)) return;
            if ($this->hasSubRole($roles)) return;
            $roles = [$roles];
        } else {
            if (!$this->hasSubRole($roles->getId())) return;
            $roles = [$roles->getId()];
        }
        $this->setSubRoles(array_merge($this->subRoles, $roles));
    }

    /**
     * @param array|string|Role $roles
     * @return void
     */
    public function removeSubRole(array|string|Role &$roles): void
    {
        if (is_array($roles)) {
            $this->filterNoHasSubRoles($roles);
        } else if (is_string($roles)){
            if (!RoleManager::getInstance()->existRole($roles)) return;
            if ($this->hasSubRole($roles)) return;
            $roles = [$roles];
        } else {
            if (!$this->hasSubRole($roles->getId())) return;
            $roles = [$roles->getId()];
        }
        $this->setSubRoles(array_diff($this->subRoles, $roles));
    }

    /**
     * @param array|string|Role $roles
     * @return void
     */
    public function setSubRoles(array|string|Role $roles): void
    {
        if (is_string($roles)) {
            $roles = [$roles];
        } else if ($roles instanceof Role) {
            $roles = [$roles->getId()];
        }
        $this->subRoles = array_values($roles);
        DataManager::getInstance()->getDataSystem()->updateOnline($this->getId(), "subRoles", $this->subRoles);
    }

    public function clearSubRoles(): void
    {
        $this->setSubRoles([]);
    }

    /**
     * @param string|Role $role
     */
    public function setRole(string|Role $role): void
    {

        $event = new EventChangeRole(Server::getInstance()->getPlayerExact($this->getName()), $this->getRole(), $role instanceof Role ? $role : RoleManager::getInstance()->getRole($role));
        $event->call();
        if ($event->isCancelled()) return;
        $this->role = $event->getNewRole()->getId();
        DataManager::getInstance()->getDataSystem()->updateOnline($this->getId(), "role", $event->getNewRole()->getId());
    }

    /**
     * @return string|null
     */
    public function getNameRoleCustom(): ?string
    {
        return $this->nameRoleCustom;
    }

    /**
     * @param string $role
     * @return bool
     */
    public function setRoleNameCustom(string $role): bool
    {
        if (!$this->getRole()->isChangeName()) return false;
        $event = new EventChangeNameCustom(Server::getInstance()->getPlayerExact($this->getName()), $this->getNameRoleCustom(), $role);
        $event->call();
        if ($event->isCancelled()) return false;
        $this->nameRoleCustom = $event->getNewNameCustom();
        DataManager::getInstance()->getDataSystem()->updateOnline($this->getId(), "nameRoleCustom", $event->getNewNameCustom());
        return true;
    }

    public function getRoleName(): string
    {
        return ($this->getRole()->isChangeName() ? $this->getNameRoleCustom() : null) ?? $this->getRole()->getName();
    }

    /**
     * @return array
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function getPermissionsSubRoles(): array
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

    /**
     * @param array|string $permissions
     */
    public function setPermissions(array|string $permissions): void
    {
        if (is_string($permissions)) {
            $permissions = [$permissions];
        }
        $this->permissions = array_values($permissions);
        DataManager::getInstance()->getDataSystem()->updateOnline($this->getId(), "permissions", $this->getPermissions());
    }

    /**
     * @param array|string $permissions
     * @return void
     */
    public function addPermissions(array|string $permissions): void
    {
        if (is_array($permissions)) {
            $this->setPermissions(array_merge($this->permissions, array_values($permissions)));
            return;
        }
        $this->setPermissions(array_merge($this->permissions, [$permissions]));
    }

    /**
     * @param array|string $permissions
     * @return void
     */
    public function removePermissions(array|string $permissions): void
    {
        $this->setPermissions(array_diff($this->permissions, is_array($permissions) ? $permissions : [$permissions]));
    }


    public function jsonSerialize(): array
    {
        return ["prefix" => $this->getPrefix(), "suffix" => $this->getSuffix(), "role" => $this->getRole()->getId(), "subRoles" => $this->getSubRoles(), "nameRoleCustom" => $this->getNameRoleCustom(), "permissions" => $this->getPermissions()];
    }
}