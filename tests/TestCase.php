<?php

namespace Tests;

use App\Services\AnggaranEmail;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Hitungan anggaran email memakai berkas; di tes ia terpisah dan dikosongkan tiap tes agar tidak saling memengaruhi.
        config(['email_anggaran.berkas' => sys_get_temp_dir() . '/anggaran-email-uji-' . getmypid() . '.json']);
        @unlink(AnggaranEmail::berkas());
    }

    protected function tearDown(): void
    {
        @unlink(AnggaranEmail::berkas());

        parent::tearDown();
    }
}