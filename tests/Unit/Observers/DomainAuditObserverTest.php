<?php

namespace Tests\Unit\Observers;

use App\Observers\DomainAuditObserver;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class DomainAuditObserverTest extends TestCase
{
    public function test_audit_allowlist_does_not_include_sensitive_document_or_identity_fields(): void
    {
        $reflection = new ReflectionClass(DomainAuditObserver::class);
        /** @var array<class-string, array<int, string>> $allowlist */
        $allowlist = $reflection->getConstant('SAFE_FIELDS');
        $allFields = array_unique(array_merge(...array_values($allowlist)));

        foreach ([
            'password', 'remember_token', 'nik', 'no_ktp', 'alamat',
            'cv_path', 'ktp_path', 'bpjs_path', 'transkrip_path',
            'surat_kampus_path', 'file_path', 'file_bukti',
        ] as $forbidden) {
            $this->assertNotContains($forbidden, $allFields);
        }
    }
}
