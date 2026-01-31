<?php
// app/Enums/PaymentStatusEnum.php

namespace App\Models\Enums;

enum RoleStatusEnum: string
{
    case SuperAdmin = 'superAdmin';
    case Admin = 'admin';
    case cashier = 'cashier';

    public function id(): int
    {
        return match ($this) {
            self::SuperAdmin => 1,
            self::Admin => 2,
            self::cashier => 3,
        };
    }
    /**
     * Menyediakan label untuk setiap status hak akses
     *
     * @return array
     */
    public static function labels(): array
    {
        return [
            self::SuperAdmin->value => 'Super Admin',
            self::Admin->value => 'Admin',
            self::cashier->value => 'Cashier',
        ];
    }

    /**
     * Mendapatkan label dari status pembayaran
     *
     * @return string
     */
    public function label(): string
    {
        return self::labels()[$this->value];
    }

    /**
     * Mendapatkan default case untuk enum
     *
     * @return self
     */
    public static function default(): self
    {
        return self::cashier; // Status default adalah "Cashier"
    }
    /**
     * Mengembalikan daftar semua role dalam format array untuk dropdown, dll.
     * Key adalah value dari enum, Value adalah labelnya.
     *
     * @return array<string, string>
     */
    public static function toAssociativeArray(): array
    {
        $roles = [];
        foreach (self::cases() as $case) {
            $roles[$case->value] = $case->label();
        }
        return $roles;
    }


    /**
     * Menentukan apakah sebuah peran adalah peran inti sistem yang tidak boleh diubah/dihapus.
     * Logika ini digunakan oleh RolePolicy.
     *
     * @param string $roleId
     * @return bool
     */

    public static function isCoreSystemRole(int $roleId): bool
    {
        return in_array($roleId, [
            self::SuperAdmin->id(),
            self::Admin->id(),
            self::cashier->id(),
        ]);
    }
}
