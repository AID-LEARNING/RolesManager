<?php

namespace SenseiTarzan\RoleManager\Class\Role;

use SenseiTarzan\RoleManager\Component\DataManager;
use SenseiTarzan\RoleManager\Component\RoleManager;

class RolePlayer implements \JsonSerializable
{

    private string $id;

    public function __construct(private string $name, private string $prefix, private string $suffix, private string $role,  private string | null $nameRoleCustom, private array $permissions = [])
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
    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
        DataManager::getInstance()->getDataSystem()->updateOnline($this->getId(), "prefix", $prefix);
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
    public function setSuffix(string $suffix): void
    {
        $this->suffix = $suffix;
        DataManager::getInstance()->getDataSystem()->updateOnline($this->getId(), "suffix", $suffix);
    }



    /**
     * @return Role
     */
    public function getRole(): Role
    {
        return RoleManager::getInstance()->getRole($this->role);
    }

    /**
     * @param string $role
     */
    public function setRole(string $role): void
    {
        $this->role = $role;
        DataManager::getInstance()->getDataSystem()->updateOnline($this->getId(), "role", $role);
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
     */
    public function setRoleNameCustom(string $role): void
    {
        if (!$this->getRole()->isChangeName()) return;
        $this->nameRoleCustom = $role;
        DataManager::getInstance()->getDataSystem()->updateOnline($this->getId(), "nameRoleCustom", $role);
    }

    public function getRoleName(): string{
        return $this->getNameRoleCustom() ?? $this->getRole()->getName();
    }

    /**
     * @return array
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * @param array|string $permissions
     */
    public function setPermissions(array|string $permissions): void
    {
        if (is_string($permissions)){
            $permissions = [$permissions];
        }
        $this->permissions = array_values($permissions);
        DataManager::getInstance()->getDataSystem()->updateOnline($this->getId(), "permissions", $this->getPermissions());
    }

    /**
     * @param string $permission
     * @return void
     */
    public function addPermissionRaw(string $permission): void{
        if (in_array($permission, $this->permissions, true)) return;
        $this->permissions[] = $permission;
    }

    /**
     * @param array|string $permissions
     * @return void
     */
    public function addPermissions(array|string $permissions): void{
        if (is_array($permissions)) {
            foreach ($permissions as $permission) {
                $this->addPermissionRaw($permission);
            }
        }else{
            $this->addPermissionRaw($permissions);
        }
        DataManager::getInstance()->getDataSystem()->updateOnline($this->getId(), "permissions", $this->getPermissions());
    }

    /**
     * @param array|string $permissions
     * @return void
     */
    public function removePermissions(array|string $permissions): void{
        $this->setPermissions(array_diff($this->permissions, is_array($permissions) ? $permissions : [$permissions]));
    }


    public function jsonSerialize(): array
    {
        return ["prefix" => $this->getPrefix(), "suffix" => $this->getSuffix(), "role" => $this->getRole()->getId(), "nameRoleCustom" => $this->getNameRoleCustom(), "permissions" => $this->getPermissions()];
    }
}