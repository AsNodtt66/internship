<?php

namespace App\Enums;

enum RoleSlug: string
{
    case PESERTA = 'peserta';
    case PIC = 'pic';
    case STAFF_SDM = 'staff_sdm';
    case KABAG_SDM = 'kabag_sdm';
    case GM = 'gm';
    case KEPALA_BAGIAN = 'kepala_bagian';
    case PEMBIMBING_LAPANGAN = 'pembimbing_lapangan';

    /**
     * @return list<self>
     */
    public static function adminPanelRoles(): array
    {
        return [
            self::PIC,
            self::STAFF_SDM,
            self::KABAG_SDM,
            self::GM,
            self::KEPALA_BAGIAN,
            self::PEMBIMBING_LAPANGAN,
        ];
    }

    /**
     * @return list<self>
     */
    public static function administrativeRoles(): array
    {
        return [self::PIC, self::STAFF_SDM, self::KABAG_SDM, self::GM];
    }
}
