<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReferenceNumberTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('qa_reference_rows', function (Blueprint $table) {
            $table->temporary();
            $table->id();
            $table->string('ref')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('qa_reference_rows');

        parent::tearDown();
    }

    public function test_numbering_continues_when_the_newest_row_uses_another_format(): void
    {
        ReferenceRow::query()->insert([
            ['ref' => 'RESISQRPR0000007'],
            ['ref' => 'RESISQRPR0000012'],
            ['ref' => 'STG-REP-001'],
            ['ref' => null],
        ]);

        $this->assertSame('RESISQRPR0000013', generateReferenceNumber(ReferenceRow::class, 'ref', 'RESISQRPR'));
    }

    public function test_numbering_ignores_suffixed_values_and_starts_at_one_when_empty(): void
    {
        $this->assertSame('RESISQRPR0000001', generateReferenceNumber(ReferenceRow::class, 'ref', 'RESISQRPR'));

        ReferenceRow::query()->insert([
            ['ref' => 'RESISQRPR0000003'],
            ['ref' => 'RESISQRPR0000009-COPY'],
        ]);

        $this->assertSame('RESISQRPR0000004', generateReferenceNumber(ReferenceRow::class, 'ref', 'RESISQRPR'));
    }
}

class ReferenceRow extends Model
{
    protected $table = 'qa_reference_rows';

    public $timestamps = false;

    protected $guarded = [];
}
