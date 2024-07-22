<?php

namespace SenseiTarzan\RoleManager\Class\Role;

use Error;
use Generator;
use JsonSerializable;
use pocketmine\event\EventPriority;
use pocketmine\permission\PermissionAttachment;
use pocketmine\Server;
use SenseiTarzan\DataBase\Component\DataManager;
use SenseiTarzan\RoleManager\Class\Exception\CancelEventException;
use SenseiTarzan\RoleManager\Class\Exception\RoleFilteredNameCustomException;
use SenseiTarzan\RoleManager\Class\Exception\RoleNoNameCustomException;
use SenseiTarzan\RoleManager\Component\RoleManager;
use SenseiTarzan\RoleManager\Event\EventChangeNameCustom;
use SenseiTarzan\RoleManager\Event\EventChangePrefix;
use SenseiTarzan\RoleManager\Event\EventChangeRole;
use SenseiTarzan\RoleManager\Event\EventChangeSuffix;
use SenseiTarzan\RoleManager\Utils\Utils;
use SOFe\AwaitGenerator\Await;

class RolePlayerOffline implements JsonSerializable
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
     */
    public function __construct(private readonly string $name, private string $prefix, private string $suffix, private string $role, private array $subRoles, private string|null $nameRoleCustom, private array $permissions = [])
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
     * @return string
     */
    public function getSuffix(): string
    {
        return $this->suffix;
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
    /**
     * @return string|null
     */
    public function getNameRoleCustom(): ?string
    {
        return $this->nameRoleCustom;
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

    public function jsonSerialize(): array
    {
        return [
            "prefix" => $this->getPrefix(),
            "suffix" => $this->getSuffix(),
            "role" => $this->getRole()->getId(),
            "subRoles" => $this->getSubRoles(),
            "nameRoleCustom" => $this->getNameRoleCustom(),
            "permissions" => $this->getPermissions()
        ];
    }
}