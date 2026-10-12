<?php

namespace Tests\Feature\Clinic;

use App\Actions\Patients\StoreDocument;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;
use Tests\TestCase;

class PatientDocumentsTest extends TestCase
{
    use RefreshDatabase;

    private User $dentist;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->dentist = User::factory()->dentist()->withTwoFactor()->create(['name' => 'Dr. Upload']);
        $this->patient = Patient::factory()->assignedTo($this->dentist)->create();
        $this->actingAs($this->dentist);
    }

    public function test_a_document_is_stored_privately_under_a_random_name(): void
    {
        Livewire::test('pages::clinic.patients.documents', ['patient' => $this->patient])
            ->set('upload', UploadedFile::fake()->image('xray-36.png', 600, 400))
            ->set('category', 'xray')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('xray-36.png')
            ->assertSee('Dr. Upload');

        $document = Document::sole();
        $this->assertSame('local', $document->disk);
        $this->assertStringStartsWith("patients/{$this->patient->id}/", $document->path);
        $this->assertStringNotContainsString('xray-36', $document->path);
        $this->assertSame('image/png', $document->mime_type);
        Storage::disk('local')->assertExists($document->path);
        Storage::disk('public')->assertMissing($document->path);
        $this->assertTrue(AuditLog::where('action', 'clinical.document_uploaded')->where('patient_id', $this->patient->id)->exists());
    }

    public function test_only_pdfs_and_images_are_accepted_by_content(): void
    {
        // An HTML file renamed to .pdf is rejected because the rule checks the content.
        // (Fake uploads report a type from their name, so use a real temporary file.)
        $path = tempnam(sys_get_temp_dir(), 'doc');
        file_put_contents($path, '<html><body><script>alert(1)</script></body></html>');
        $disguised = new UploadedFile($path, 'report.pdf', 'application/pdf', null, true);
        $rules = ['upload' => ['required', 'file', 'mimes:'.implode(',', Document::MIMES)]];
        $this->assertTrue(Validator::make(['upload' => $disguised], $rules)->fails());
        @unlink($path);

        Livewire::test('pages::clinic.patients.documents', ['patient' => $this->patient])
            ->set('upload', UploadedFile::fake()->create('notes.docx', 10, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'))
            ->call('save')
            ->assertHasErrors(['upload']);

        $this->assertSame(0, Document::count());
    }

    public function test_files_over_ten_megabytes_are_rejected(): void
    {
        Livewire::test('pages::clinic.patients.documents', ['patient' => $this->patient])
            ->set('upload', UploadedFile::fake()->create('big.pdf', 10241, 'application/pdf'))
            ->call('save')
            ->assertHasErrors(['upload']);
    }

    public function test_download_is_authorised_audited_and_hardened(): void
    {
        $document = $this->storeDocument();

        $response = $this->get(route('clinic.patients.documents.download', ['patient' => $this->patient, 'document' => $document->id]));
        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->assertStringStartsWith('attachment', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('sandbox', (string) $response->headers->get('Content-Security-Policy'));

        $inline = $this->get(route('clinic.patients.documents.download', ['patient' => $this->patient, 'document' => $document->id, 'inline' => 1]));
        $this->assertStringStartsWith('inline', (string) $inline->headers->get('Content-Disposition'));

        $this->assertSame(2, AuditLog::where('action', 'clinical.document_downloaded')->where('patient_id', $this->patient->id)->count());
    }

    public function test_documents_of_another_patient_cannot_be_fetched_through_this_patient(): void
    {
        $other = Patient::factory()->assignedTo($this->dentist)->create();
        $foreign = $this->storeDocument($other);

        $this->get(route('clinic.patients.documents.download', ['patient' => $this->patient, 'document' => $foreign->id]))->assertNotFound();
    }

    public function test_receptionists_and_other_dentists_cannot_list_or_download(): void
    {
        $document = $this->storeDocument();

        foreach ([User::factory()->receptionist()->withTwoFactor()->create(), User::factory()->dentist()->withTwoFactor()->create()] as $user) {
            $this->actingAs($user);
            $this->get(route('clinic.patients.documents', $this->patient))->assertForbidden();
            $this->get(route('clinic.patients.documents.download', ['patient' => $this->patient, 'document' => $document->id]))->assertForbidden();
        }
    }

    public function test_only_administrators_can_remove_and_the_file_is_kept(): void
    {
        $document = $this->storeDocument();

        Livewire::test('pages::clinic.patients.documents', ['patient' => $this->patient])
            ->call('remove', $document->id)
            ->assertForbidden();

        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        Livewire::test('pages::clinic.patients.documents', ['patient' => $this->patient])
            ->call('remove', $document->id)
            ->assertDontSee('scan.png');

        $this->assertSoftDeleted($document);
        Storage::disk('local')->assertExists($document->path);
        $this->assertTrue(AuditLog::where('action', 'clinical.document_removed')->exists());

        // Removed documents are no longer downloadable.
        $this->get(route('clinic.patients.documents.download', ['patient' => $this->patient, 'document' => $document->id]))->assertNotFound();
    }

    private function storeDocument(?Patient $patient = null): Document
    {
        return app(StoreDocument::class)->handle(
            $patient ?? $this->patient,
            UploadedFile::fake()->image('scan.png', 200, 200),
            'xray',
            $this->dentist,
        );
    }
}
