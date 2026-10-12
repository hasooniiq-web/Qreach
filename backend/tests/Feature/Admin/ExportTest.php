<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\TradeCustomer;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\QrBatchService;
use App\Services\TradeLedger;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;
use ZipArchive;

/**
 * Every dashboard list can be downloaded as Excel and PDF with its filters applied.
 */
class ExportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role = 'super_admin'): Admin
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = Admin::factory()->create();
        $admin->roles()->sync([Role::where('slug', $role)->value('id')]);

        return $admin;
    }

    /** All the text of the single worksheet of an .xlsx binary. */
    private function sheetText(string $binary): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xl');
        file_put_contents($tmp, $binary);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($tmp) === true, 'The download is not a valid zip/xlsx file.');
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $this->assertNotFalse($xml);
        $this->assertNotFalse($zip->getFromName('xl/styles.xml'));
        $zip->close();
        @unlink($tmp);

        $parsed = simplexml_load_string($xml);
        $this->assertNotFalse($parsed, 'The sheet is not well-formed XML.');

        return html_entity_decode(strip_tags(str_replace('</c>', ' ', $xml)), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function assertXlsx($response, string ...$expected): void
    {
        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml.sheet', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('.xlsx', $response->headers->get('Content-Disposition'));
        $text = $this->sheetText($response->getContent());
        foreach ($expected as $needle) {
            $this->assertStringContainsString($needle, $text);
        }
    }

    private function assertPdf($response): void
    {
        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertGreaterThan(1500, strlen($response->getContent()));
    }

    public function test_the_user_list_exports_to_excel_and_pdf_with_its_filter(): void
    {
        $admin = $this->admin();
        User::factory()->create(['name' => 'أبو جاسم', 'phone' => '+9647701112222']);
        User::factory()->create(['name' => 'علي', 'phone' => '+9647703334444']);

        $this->actingAs($admin, 'admin');

        $this->assertXlsx($this->get('/admin/users?export=xlsx&q='.urlencode('جاسم')), 'المستخدمون', 'أبو جاسم', '+9647701112222');
        $this->assertStringNotContainsString('+9647703334444', $this->sheetText($this->get('/admin/users?export=xlsx&q='.urlencode('جاسم'))->getContent()));
        $this->assertPdf($this->get('/admin/users?export=pdf'));
    }

    public function test_every_list_page_has_an_excel_and_pdf_export(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');

        $batch = app(QrBatchService::class)->createBatch(3, $admin->id);
        $vehicle = Vehicle::factory()->create(['nickname' => 'العروسة', 'make' => 'KIA', 'model' => 'G class']);
        $customer = TradeCustomer::create(['name' => 'محل الأمين']);
        $ledger = app(TradeLedger::class);
        $ledger->recordSale($customer, $batch, 2, 1000, now(), null, $admin->id);
        $ledger->recordPayment($customer, 500, now(), null, $admin->id);

        $pages = [
            '/admin/users' => 'المستخدمون',
            '/admin/vehicles' => 'العروسة',
            '/admin/qr' => 'رموز QR و NFC',
            '/admin/batches' => 'دفعات الرموز',
            "/admin/batches/{$batch->id}" => $batch->batch_code,
            '/admin/contact-requests' => 'طلبات التواصل',
            '/admin/emergencies' => 'التنبيهات الطارئة',
            '/admin/security' => 'الأحداث الأمنية',
            '/admin/audit' => 'سجلات التدقيق',
            '/admin/subscriptions' => 'الاشتراكات',
            '/admin/subscriptions/requests' => 'طلبات الترقية',
            '/admin/payments' => 'المدفوعات',
            '/admin/admins' => 'المسؤولون',
            '/admin/trade' => 'محل الأمين',
            "/admin/trade/customers/{$customer->id}" => 'كشف حساب',
            '/admin/reports' => 'تقرير',
        ];

        foreach ($pages as $path => $needle) {
            RateLimiter::clear('export:'.$admin->id); // 32 downloads in one test would hit the per-minute limit
            $this->assertXlsx($this->get($path.'?export=xlsx'), $needle);
            $this->assertPdf($this->get($path.'?export=pdf'));
        }
    }

    public function test_the_vehicle_export_shows_make_model_year_and_owner(): void
    {
        $admin = $this->admin();
        $owner = User::factory()->create(['name' => null, 'phone' => '+9647705556666']);
        Vehicle::factory()->for($owner)->create(['make' => 'Mercedes', 'model' => 'G class', 'year' => 2024, 'plate_number' => '65600']);

        $text = $this->sheetText($this->actingAs($admin, 'admin')->get('/admin/vehicles?export=xlsx')->getContent());

        $this->assertStringContainsString('Mercedes G class', $text);
        $this->assertStringContainsString('2024', $text);
        $this->assertStringContainsString('+9647705556666', $text); // owner without a name falls back to the phone
    }

    public function test_the_trade_ledger_export_carries_balance_and_receipt_numbers(): void
    {
        $admin = $this->admin();
        $batch = app(QrBatchService::class)->createBatch(1000, $admin->id);
        $customer = TradeCustomer::create(['name' => 'سوبر ماركت النور']);
        $ledger = app(TradeLedger::class);
        $ledger->recordSale($customer, $batch, 1000, 5000, now(), null, $admin->id);
        $payment = $ledger->recordPayment($customer, 1_000_000, now(), null, $admin->id);

        $text = $this->sheetText($this->actingAs($admin, 'admin')->get("/admin/trade/customers/{$customer->id}?export=xlsx")->getContent());

        $this->assertStringContainsString($payment->receipt_number, $text);
        $this->assertStringContainsString('4000000', $text); // balance as a real number cell
        $this->assertStringContainsString('الرصيد', $text);
    }

    public function test_an_export_is_audit_logged(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin')->get('/admin/users?export=xlsx&status=active')->assertOk();

        $log = AuditLog::where('action', 'export.generated')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('users', $log->metadata['page'] ?? null);
        $this->assertSame('xlsx', $log->metadata['format'] ?? null);
    }

    public function test_the_report_export_still_needs_the_export_permission(): void
    {
        $analyst = $this->admin('support_admin'); // can view lists, cannot export reports
        $this->actingAs($analyst, 'admin');

        $batch = app(QrBatchService::class)->createBatch(1, null);

        $this->get('/admin/reports?export=xlsx')->assertForbidden();
        $this->get("/admin/batches/{$batch->id}?export=pdf")->assertStatus(403);
    }

    public function test_exports_are_rate_limited_per_admin(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');
        RateLimiter::clear('export:'.$admin->id);

        for ($i = 0; $i < 15; $i++) {
            $this->get('/admin/users?export=xlsx')->assertOk();
        }
        $this->get('/admin/users?export=xlsx')->assertStatus(429);
    }

    public function test_cells_that_look_like_formulas_are_stored_as_text(): void
    {
        $admin = $this->admin();
        User::factory()->create(['name' => '=HYPERLINK("http://evil.example","x")', 'phone' => '+9647700000001']);

        $xml = $this->actingAs($admin, 'admin')->get('/admin/users?export=xlsx')->getContent();
        $tmp = tempnam(sys_get_temp_dir(), 'xl');
        file_put_contents($tmp, $xml);
        $zip = new ZipArchive;
        $zip->open($tmp);
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        @unlink($tmp);

        // Inline string, never a <f> formula element.
        $this->assertStringNotContainsString('<f>', $sheet);
        $this->assertStringContainsString('t="inlineStr"', $sheet);
    }
}
