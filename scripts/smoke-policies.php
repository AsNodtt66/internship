<?php

require __DIR__.'/../vendor/autoload.php';

// Minimal test-only fallback for this repository audit environment.
// Real application/runtime requirements still include ext-mbstring.
if (! function_exists('mb_split')) {
    function mb_split(string $pattern, string $string, int $limit = -1): array|false
    {
        return preg_split('/'.$pattern.'/u', $string, $limit);
    }
}

use App\Enums\RoleSlug;
use App\Models\ApprovalWorkflow;
use App\Models\Bagian;
use App\Models\Pengajuan;
use App\Models\PenugasanPembimbing;
use App\Models\Peserta;
use App\Models\Role;
use App\Models\User;
use App\Policies\ApprovalWorkflowPolicy;
use App\Policies\BagianPolicy;
use App\Policies\PengajuanPolicy;

function userWithRole(int $id, RoleSlug $slug): User
{
    $role = new Role;
    $role->setRawAttributes(['slug' => $slug->value], true);

    $user = new User;
    $user->setRawAttributes(['id' => $id, 'is_active' => true], true);
    $user->setRelation('role', $role);

    return $user;
}

function check(bool $condition, string $message): void
{
    if (! $condition) {
        fwrite(STDERR, "[FAIL] {$message}\n");
        exit(1);
    }

    fwrite(STDOUT, "[OK] {$message}\n");
}

$pic = userWithRole(1, RoleSlug::PIC);
$gm = userWithRole(2, RoleSlug::GM);
$staff = userWithRole(3, RoleSlug::STAFF_SDM);
$kepalaTarget = userWithRole(4, RoleSlug::KEPALA_BAGIAN);
$kepalaLain = userWithRole(5, RoleSlug::KEPALA_BAGIAN);
$pembimbing = userWithRole(6, RoleSlug::PEMBIMBING_LAPANGAN);
$pesertaUser = userWithRole(7, RoleSlug::PESERTA);
$pesertaLain = userWithRole(8, RoleSlug::PESERTA);

$bagian = new Bagian;
$bagian->setRawAttributes(['id' => 10, 'kepala_bagian_id' => $kepalaTarget->id], true);

$peserta = new Peserta;
$peserta->setRawAttributes(['id' => 11, 'user_id' => $pesertaUser->id], true);

$penugasan = new PenugasanPembimbing;
$penugasan->setRawAttributes(['pembimbing_id' => $pembimbing->id], true);

$pengajuan = new Pengajuan;
$pengajuan->setRawAttributes(['id' => 20, 'status' => 'berjalan'], true);
$pengajuan->setRelation('bagianTujuan', $bagian);
$pengajuan->setRelation('peserta', $peserta);
$pengajuan->setRelation('penugasanPembimbing', $penugasan);

$bagianPolicy = new BagianPolicy;
check($bagianPolicy->viewAny($pic), 'PIC dapat mengakses master Bagian.');
check(! $bagianPolicy->viewAny($gm), 'GM tidak dapat membuka master Bagian hanya dengan URL langsung.');

$pengajuanPolicy = new PengajuanPolicy;
check($pengajuanPolicy->view($pic, $pengajuan), 'PIC dapat melihat pengajuan.');
check($pengajuanPolicy->view($kepalaTarget, $pengajuan), 'Kepala Bagian tujuan dapat melihat pengajuan bagiannya.');
check(! $pengajuanPolicy->view($kepalaLain, $pengajuan), 'Kepala Bagian lain tidak dapat melihat pengajuan lintas bagian.');
check($pengajuanPolicy->view($pembimbing, $pengajuan), 'Pembimbing yang ditugaskan dapat melihat pengajuan.');
check($pengajuanPolicy->view($pesertaUser, $pengajuan), 'Peserta dapat melihat pengajuan miliknya sendiri.');
check(! $pengajuanPolicy->view($pesertaLain, $pengajuan), 'Peserta lain tidak dapat melihat pengajuan yang bukan miliknya.');

$approvalPolicy = new ApprovalWorkflowPolicy;
$gmStep = new ApprovalWorkflow;
$gmStep->setRawAttributes(['urutan' => 1, 'penandatangan_id' => null], true);
$gmStep->setRelation('pengajuan', $pengajuan);
check($approvalPolicy->view($gm, $gmStep), 'GM dapat melihat dokumen tahap GM sebelum menandatangani.');
check(! $approvalPolicy->view($staff, $gmStep), 'Role tahap lain tidak dapat melihat dokumen disposisi GM.');

$kepalaStep = new ApprovalWorkflow;
$kepalaStep->setRawAttributes(['urutan' => 4, 'penandatangan_id' => null], true);
$kepalaStep->setRelation('pengajuan', $pengajuan);
check($approvalPolicy->view($kepalaTarget, $kepalaStep), 'Kepala Bagian target dapat melihat disposisi tahap terakhir.');
check(! $approvalPolicy->view($kepalaLain, $kepalaStep), 'Kepala Bagian lain ditolak pada disposisi tahap terakhir.');

fwrite(STDOUT, "Policy smoke checks passed.\n");
