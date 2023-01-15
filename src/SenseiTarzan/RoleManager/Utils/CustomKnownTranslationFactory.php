<?php

namespace SenseiTarzan\RoleManager\Utils;

use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use SenseiTarzan\RoleManager\Class\Role\Role;

class CustomKnownTranslationFactory
{

    public static function format_permissions_list_string(array $permissions): string
    {
        return "\n- " . implode(",\n- ", $permissions) . "\n";
    }

    public static function title_create_role(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::TITLE_CREATE_ROLE, []);
    }
    public static function exemple_heritages_label(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::LABEL_ADD_HERITAGES, []);
    }
    public static function exemple_permissions_label(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::LABEL_ADD_HERITAGES, []);
    }
    public static function error_player_disconnected(string $name): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::ERROR_PLAYER_DISCONNECTED, ["player" => $name]);
    }

    public static  function set_role_sender(Player|string $player, Role $role): Translatable{
        return new Translatable(CustomKnownTranslationKeys::SET_ROLE_SENDER, ['player' => $player instanceof Player ? $player->getName() : $player, 'role' => $role->getName()]);
    }

    public static  function set_role_target(Role $role): Translatable{
        return new Translatable(CustomKnownTranslationKeys::SET_ROLE_TARGET, ['role' => $role->getName()]);
    }
    public static  function add_permissions_sender(Player|string $player, array $permissions): Translatable{
        return  new Translatable(CustomKnownTranslationKeys::ADD_PERMISSIONS_SENDER, ['player' => $player instanceof Player ? $player->getName() : $player, 'permissions' => self::format_permissions_list_string($permissions)]);
    }
    public static  function add_permissions_target( array $permissions): Translatable{
        return new Translatable(CustomKnownTranslationKeys::ADD_PERMISSIONS_TARGET, ['permissions' => self::format_permissions_list_string($permissions)]);
    }
    public static  function remove_permissions_sender(Player|string $player, array $permissions): Translatable{
        return new Translatable(CustomKnownTranslationKeys::REMOVE_PERMISSIONS_SENDER, ['player' => $player instanceof Player ? $player->getName() : $player, 'permissions' => self::format_permissions_list_string($permissions)]);
    }
    public static  function remove_permissions_target( array $permissions): Translatable{
        return new Translatable(CustomKnownTranslationKeys::REMOVE_PERMISSIONS_TARGET,['permissions' => self::format_permissions_list_string($permissions)]);
    }
    public static  function set_permissions_sender(Player|string $player, array $permissions): Translatable{

        return new Translatable(CustomKnownTranslationKeys::SET_PERMISSIONS_SENDER,['player' => $player instanceof Player ? $player->getName() : $player, 'permissions' => self::format_permissions_list_string($permissions)]);
    }
    public static  function set_permissions_target( array $permissions): Translatable{
        return new Translatable(CustomKnownTranslationKeys::SET_PERMISSIONS_TARGET,['permissions' => self::format_permissions_list_string($permissions)]);
    }

    public static function set_suffix_sender(Player $player, string $suffix): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::SET_SUFFIX_SENDER, ['player' => $player->getName() , 'suffix'=> $suffix]);
    }

    public static function set_suffix_target(string $suffix): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::SET_SUFFIX_TARGET, ['suffix'=> $suffix]);
    }
    public static function set_prefix_sender(Player $player, string $prefix): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::SET_PREFIX_SENDER, ['player' => $player->getName(), 'prefix'=> $prefix]);
    }
    public static function set_prefix_target( string $prefix): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::SET_PREFIX_TARGET, ['prefix'=> $prefix]);
    }
    public static function set_name_role_sender(Player $player, string $nameRoleCustom): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::SET_NAME_ROLE_CUSTOM_SENDER, ['player' => $player->getName(), 'nameCustom'=> $nameRoleCustom]);
    }
    public static function set_name_role_custom_target(string $nameRoleCustom): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::SET_NAME_ROLE_CUSTOM_TARGET, ['nameCustom'=> $nameRoleCustom]);
    }

}