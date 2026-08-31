<?php

namespace Tests\Unit\Support\Documents;

use App\Support\Documents\PrivateDocumentRegistry;
use PHPUnit\Framework\TestCase;

class PrivateDocumentRegistryTest extends TestCase
{
    public function test_it_accepts_relative_document_paths(): void
    {
        $this->assertTrue(PrivateDocumentRegistry::isSafePath('cv/abc123.pdf'));
        $this->assertTrue(PrivateDocumentRegistry::isSafePath('penilaian/2026/file.pdf'));
    }

    public function test_it_rejects_traversal_and_absolute_paths(): void
    {
        $this->assertFalse(PrivateDocumentRegistry::isSafePath('../.env'));
        $this->assertFalse(PrivateDocumentRegistry::isSafePath('cv/../../.env'));
        $this->assertFalse(PrivateDocumentRegistry::isSafePath('/etc/passwd'));
        $this->assertFalse(PrivateDocumentRegistry::isSafePath('C:\\Windows\\system.ini'));
        $this->assertFalse(PrivateDocumentRegistry::isSafePath(''));
        $this->assertFalse(PrivateDocumentRegistry::isSafePath('https://evil.test/file.pdf'));
        $this->assertFalse(PrivateDocumentRegistry::isSafePath('cv/./file.pdf'));
        $this->assertFalse(PrivateDocumentRegistry::isSafePath("cv/file\x1F.pdf"));
    }

    public function test_sensitive_pengajuan_fields_are_centralized(): void
    {
        $fields = PrivateDocumentRegistry::pengajuanFields();

        foreach (['file_cv', 'file_ktp_ktm', 'file_transkrip', 'file_bpjs_ketenagakerjaan'] as $field) {
            $this->assertContains($field, $fields);
        }
    }
}
