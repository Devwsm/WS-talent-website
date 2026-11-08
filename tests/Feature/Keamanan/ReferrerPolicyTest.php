<?php

namespace Tests\Feature\Keamanan;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferrerPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_biasa_memakai_kebijakan_bawaan(): void
    {
        $this->get('/privasi')->assertOk()->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_tautan_atur_ulang_sandi_tidak_mengirim_referer(): void
    {
        $this->get('/innercircle/atur-ulang/abc123?email=a@contoh.test')
            ->assertOk()
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('Cache-Control');
    }
}
