<?php

use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Billing\BillingController;
use App\Http\Controllers\Department\DepartmentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Pharmacy\PharmacyController;
use App\Http\Controllers\Pharmacy\PharmacySaleController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\Settings\BackupController;
use App\Http\Controllers\Settings\GeneralSettingsController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\Support\SupportController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Patient\PatientController;
use App\Http\Controllers\MedicalRecord\MedicalRecordController;
use App\Http\Controllers\Doctor\DoctorController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('auth.login');
});

Auth::routes([
    'register' => false,
    'reset' => false,
    'verify' => false,
]);

Route::get('/home', [HomeController::class, 'index'])->name('home');

// ------------------ 2FA (mandatory) --------------------
Route::middleware(['auth'])->group(function () {
    Route::get('/2fa/setup', [TwoFactorController::class, 'showSetupForm'])->name('2fa.setup');
    Route::post('/2fa/setup', [TwoFactorController::class, 'confirmSetup'])->name('2fa.setup.confirm')->middleware('throttle:5,1');

    Route::get('/2fa/verify', [TwoFactorController::class, 'showVerifyForm'])->name('2fa.verify');
    Route::post('/2fa/verify', [TwoFactorController::class, 'verify'])->name('2fa.verify.submit')->middleware('throttle:5,1');
});

// ------------------ Department --------------------
Route::group(['prefix' => 'department', 'middleware' => ['auth', '2fa']], function () {
    Route::get('/', [DepartmentController::class, 'index'])->name('department.index');
    Route::post('/store', [DepartmentController::class, 'store'])->name('department.store');
    Route::get('/edit/{id}', [DepartmentController::class, 'edit'])->name('department.edit');
    Route::put('/update/{id}', [DepartmentController::class, 'update'])->name('department.update');
    Route::delete('/delete/{id}', [DepartmentController::class, 'destroy'])->name('department.destroy');
});

// ------------------ Admin Only Routes (Role: admin) --------------------
Route::group(['middleware' => ['auth', '2fa', 'role:admin']], function () {

    // User management routes
    Route::group(['prefix' => 'user'], function () {
        Route::get('/', [UserController::class, 'index'])->name('user.index');
        Route::post('/store', [UserController::class, 'store'])->name('user.store');
        Route::put('/{user}/role', [UserController::class, 'updateRole'])->name('user.update-role');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('user.destroy');
        Route::post('/{id}/reset-2fa', [UserController::class, 'resetTwoFactor'])->name('user.reset2fa');
    });

    // Role & Permission management routes
    Route::group(['prefix' => 'roles'], function () {
        Route::get('/', [RolePermissionController::class, 'index'])->name('roles.index');
        Route::post('/store', [RolePermissionController::class, 'storeRole'])->name('roles.store-role');
        Route::post('/permission/store', [RolePermissionController::class, 'storePermission'])->name('permissions.store-permission');
        Route::post('/{role}/permissions', [RolePermissionController::class, 'assignPermissionsToRole'])->name('roles.assign-permissions');
    });

    // System Settings routes
    Route::group(['prefix' => 'settings'], function () {
        Route::get('/general', [GeneralSettingsController::class, 'index'])->name('settingsgeneral.index');
        Route::post('/general', [GeneralSettingsController::class, 'update'])->name('settingsgeneral.update');

        Route::get('/billing', [SettingsController::class, 'bilingindex'])->name('settingsbillings.index');
        Route::post('/billing', [SettingsController::class, 'billingUpdate'])->name('settingsbillings.update');

        Route::get('/qrcode', [SettingsController::class, 'qrcodeindex'])->name('settingsqrcode.index');
    });
    Route::group(['prefix' => 'settings/backup'], function () {
        Route::get('/', [BackupController::class, 'index'])->name('settingsbackup.index');
        Route::get('/list', [BackupController::class, 'list'])->name('settingsbackup.list');
        Route::post('/create', [BackupController::class, 'store'])->name('settingsbackup.store');
        Route::get('/download/{filename}', [BackupController::class, 'download'])->name('settingsbackup.download');
        Route::delete('/{filename}', [BackupController::class, 'destroy'])->name('settingsbackup.destroy');
        Route::post('/restore', [BackupController::class, 'restore'])->name('settingsbackup.restore');
    });
});

// ------------------ Pharmacist & Admin Routes (Role: admin|pharmacist) --------------------
Route::group(['prefix' => 'pharmacy', 'middleware' => ['auth', '2fa', 'role:admin|pharmacist']], function () {
    Route::get('/', [PharmacyController::class, 'index'])->name('pharmacy.index');
    Route::get('/export', [PharmacyController::class, 'export'])->name('pharmacy.export');
    Route::get('/export/names', [PharmacyController::class, 'exportNames'])->name('pharmacy.export.names');
    Route::get('/export/stock-report', [PharmacyController::class, 'exportStockReport'])->name('pharmacy.export.stockReport');
    Route::get('/expiring-detail', [PharmacyController::class, 'expiringDetail'])->name('pharmacy.expiring.detail');
    Route::get('/data', [PharmacyController::class, 'data'])->name('pharmacy.data');


    Route::post('/', [PharmacyController::class, 'store'])->name('pharmacy.store');
    Route::get('/{medicine}/edit', [PharmacyController::class, 'edit'])->name('pharmacy.edit');
    Route::put('/{medicine}', [PharmacyController::class, 'update'])->name('pharmacy.update');
    Route::delete('/{medicine}', [PharmacyController::class, 'destroy'])->name('pharmacy.destroy');
    Route::post('/{medicine}/restock', [PharmacyController::class, 'addBatch'])->name('pharmacy.restock');
    Route::get('/{medicine}/details', [PharmacyController::class, 'details'])->name('pharmacy.details');

    Route::post('/suppliers', [SupplierController::class, 'store'])->name('pharmacy.suppliers.store');

    // Sell
    Route::get('/sell', [PharmacySaleController::class, 'index'])->name('pharmacy.sell.index');
    Route::get('/sell/search', [PharmacySaleController::class, 'search'])->name('pharmacy.sell.search');
    Route::get('/sell/history', [PharmacySaleController::class, 'history'])->name('pharmacy.sell.history');
    Route::post('/sell', [PharmacySaleController::class, 'store'])->name('pharmacy.sell.store');
    Route::get('/sell/{sale}/pdf', [PharmacySaleController::class, 'exportPdf'])->name('pharmacy.sell.pdf');

    Route::get('/stats', [PharmacyController::class, 'stats'])->name('pharmacy.stats');
});

// ------------------ Cashier & Admin Routes (Role: admin|cashier) --------------------
Route::group(['prefix' => 'billing', 'middleware' => ['auth', '2fa', 'role:admin|cashier']], function () {
    Route::get('/', [BillingController::class, 'index'])->name('billing.index');
    Route::post('/store', [BillingController::class, 'store'])->name('billing.store');
    Route::get('/{id}', [BillingController::class, 'show'])->name('billing.show');
    Route::post('/{id}/pay', [BillingController::class, 'processPayment'])->name('billing.pay');
});

// ------------------ Admin, Doctor, Nurse & Cashier Routes --------------------
Route::group(['middleware' => ['auth', '2fa', 'role:admin|doctor|nurse|cashier']], function () {
    // Doctor Route
    Route::prefix('doctor')->name('doctor.')->group(function () {
        Route::get('/', [DoctorController::class, 'index'])->name('index');
        Route::get('/consultation/{id}', [DoctorController::class, 'edit'])->name('consultation');
        Route::put('/consultation/{id}', [DoctorController::class, 'update'])->name('update');
    });

    // Patient Routes
    Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
    Route::get('/patients/create', [PatientController::class, 'create'])->name('patients.create');
    Route::post('/patients', [PatientController::class, 'store'])->name('patients.store');
    Route::get('/patients/{id}', [PatientController::class, 'show'])->name('patients.show');
    Route::get('/patients/{id}/edit', [PatientController::class, 'edit'])->name('patients.edit');
    Route::put('/patients/{id}', [PatientController::class, 'update'])->name('patients.update');
    Route::delete('/patients/{id}', [PatientController::class, 'destroy'])->name('patients.destroy');
    // Print
    Route::get('/patients/{id}/print', [PatientController::class, 'print'])->name('patients.print');


    // ------------------ Doctor, Nurse & Admin Routes (Role: admin|doctor|nurse) --------------------
    Route::group(['middleware' => ['auth', '2fa', 'role:admin|doctor|nurse']], function () {
        Route::get('/appointment', function () {
            return view('home');
        });
        Route::get('/appointments', function () {
            return view('home');
        });
        Route::get('/lab', function () {
            return view('home');
        });
    });

    // ------------------ Support (Authenticated Users) --------------------
    Route::group(['prefix' => 'support', 'middleware' => ['auth', '2fa']], function () {
        Route::get('/', [SupportController::class, 'index'])->name('support.index');
    });

    // ------------------ Medical Record Routes --------------------
    Route::group(['middleware' => ['auth', '2fa', 'role:admin|doctor|nurse']], function () {
        Route::get('/medical-records', [MedicalRecordController::class, 'index'])->name('medical-records.index');
        Route::get('/medical-records/create', [MedicalRecordController::class, 'create'])->name('medical-records.create');
        Route::post('/medical-records', [MedicalRecordController::class, 'store'])->name('medical-records.store');
        Route::get('/medical-records/{id}', [MedicalRecordController::class, 'show'])->name('medical-records.show');
        Route::get('/medical-records/{id}/edit', [MedicalRecordController::class, 'edit'])->name('medical-records.edit');
        Route::put('/medical-records/{id}', [MedicalRecordController::class, 'update'])->name('medical-records.update');
        Route::delete('/medical-records/{id}', [MedicalRecordController::class, 'destroy'])->name('medical-records.destroy');
    });
});
