<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Admin\SiteSettingsController;
use App\Http\Controllers\Admin\AdminAccountsController;
use App\Http\Controllers\Admin\AdminRolesController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\SchoolController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\StudentProfileController;
use App\Http\Controllers\TeacherProfileController;
use App\Http\Controllers\StudentDashboardController;
use App\Models\ConsultationAvailability;
use App\Http\Controllers\PwaController;

Route::get('/manifest.webmanifest', [PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/admin/manifest.webmanifest', [PwaController::class, 'adminManifest'])->name('pwa.admin-manifest');
// Shared hosting can serve uploaded images through PHP without a storage symlink.
Route::get('/storage/{directory}/{filename}', [\App\Http\Controllers\PublicImageController::class, 'show'])->name('images.storage');
Route::get('/media/{directory}/{filename}', [\App\Http\Controllers\PublicImageController::class, 'show'])->name('images.media');

Route::get('/', function () {
    $publicAvailability = ConsultationAvailability::query()
        ->with('teacher')
        ->where(fn ($query) => $query->whereDate('date', '>', today())
            ->orWhere(fn ($today) => $today->whereDate('date', today())->where('ends_at', '>', now()->format('H:i:s'))))
        ->whereNull('cancelled_at')
        ->whereHas('teacher', fn ($query) => $query->whereHas('teacher', fn ($profile) => $profile->where('consultation_approved', true)))
        ->whereHas('teacher.role', fn ($role) => $role->whereRaw('LOWER(name) LIKE ?', ['%teacher%'])->orWhereRaw('LOWER(name) LIKE ?', ['%faculty%']))
        ->orderBy('date')
        ->orderBy('starts_at')
        ->limit(12)
        ->get();
    return view('welcome', compact('publicAvailability'));
});

Route::get('/admin/login', [AdminLoginController::class, 'create'])->name('admin.login');
Route::post('/admin/login', [AdminLoginController::class, 'store'])->middleware('throttle:5,1')->name('admin.login.store');

Route::get('/portal/login', [ConsultationController::class, 'login'])->name('portal.login');
Route::post('/portal/login', [ConsultationController::class, 'authenticate'])->middleware('throttle:5,1')->name('portal.login.store');
Route::post('/account/activity', fn () => response()->noContent())->middleware(['auth', 'throttle:10,1'])->name('account.activity');
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegistrationController::class, 'create'])->name('register');
    Route::post('/register', [RegistrationController::class, 'store'])->middleware('throttle:5,1')->name('register.store');
});
Route::middleware(['auth', \App\Http\Middleware\RefreshProposedConsultationRequests::class, \App\Http\Middleware\EnsureTeacherProfileComplete::class])->prefix('consultations')->name('consultations.')->group(function () {
    Route::get('/', [ConsultationController::class, 'home'])->name('home');
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
    Route::post('/tour/complete', [\App\Http\Controllers\PortalTourController::class, 'complete'])->name('tour.complete');
    Route::get('/student/consultations', [\App\Http\Controllers\StudentConsultationsController::class, 'index'])->name('student-consultations.index');
    Route::post('/student/consultations/seen', [\App\Http\Controllers\StudentConsultationsController::class, 'seen'])->name('student-consultations.seen');
    Route::get('/browse', [ConsultationController::class, 'browse'])->name('browse');
    Route::get('/student/profile', [StudentProfileController::class, 'edit'])->name('student-profile.edit');
    Route::put('/student/profile', [StudentProfileController::class, 'update'])->name('student-profile.update');
    Route::post('/availability/{availability}/book', [ConsultationController::class, 'book'])->name('book');
    Route::post('/teachers/{teacher}/request-time', [ConsultationController::class, 'requestTime'])->name('request-time');
    Route::get('/teacher', [ConsultationController::class, 'teacher'])->name('teacher');
    Route::get('/teacher/requests', [ConsultationController::class, 'teacherRequests'])->name('teacher.requests');
    Route::get('/teacher/availability', [ConsultationController::class, 'teacherAvailability'])->name('teacher.availability');
    Route::get('/teacher/settings', [ConsultationController::class, 'teacherPreferences'])->name('teacher.preferences');
    Route::get('/teacher/profile', [TeacherProfileController::class, 'edit'])->name('teacher-profile.edit');
    Route::put('/teacher/profile', [TeacherProfileController::class, 'update'])->name('teacher-profile.update');
    Route::post('/teacher/availability', [ConsultationController::class, 'availability'])->name('availability.store');
    Route::put('/teacher/settings', [ConsultationController::class, 'teacherSettings'])->name('teacher.settings');
    Route::patch('/teacher/availability/{availability}/cancel', [ConsultationController::class, 'cancelAvailability'])->name('availability.cancel');
    Route::patch('/student/appointments/{appointment}/cancel', [ConsultationController::class, 'cancelBooking'])->name('appointments.cancel');
    Route::patch('/teacher/appointments/{appointment}', [ConsultationController::class, 'updateAppointment'])->name('appointments.update');
    Route::post('/logout', [ConsultationController::class, 'logout'])->name('logout');
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/school', [SchoolController::class, 'index'])->middleware('permission:settings,view')->name('school');
    Route::put('/school/registration-grades', [SchoolController::class, 'registrationGrades'])->middleware('permission:settings,edit')->name('school.registration-grades');
    Route::post('/school/{type}', [SchoolController::class, 'store'])->middleware('permission:settings,edit')->name('school.store');
    Route::put('/school/{type}/{id}', [SchoolController::class, 'update'])->middleware('permission:settings,edit')->name('school.update');
    Route::delete('/school/{type}/{id}', [SchoolController::class, 'destroy'])->middleware('permission:settings,edit')->name('school.destroy');
    Route::put('/accounts/{user}/school-profile', [SchoolController::class, 'updateProfile'])->middleware('permission:accounts,edit')->name('accounts.school-profile');
    Route::get('/consultations', [SchoolController::class, 'monitoring'])->middleware(['permission:dashboard,view', \App\Http\Middleware\RefreshProposedConsultationRequests::class])->name('consultations');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->middleware(['permission:dashboard,view', \App\Http\Middleware\RefreshProposedConsultationRequests::class])->name('dashboard');
    Route::get('/settings', [SiteSettingsController::class, 'edit'])->middleware('permission:settings,view')->name('settings');
    Route::put('/settings', [SiteSettingsController::class, 'update'])->middleware('permission:settings,edit')->name('settings.update');
    Route::get('/accounts', [AdminAccountsController::class, 'index'])->middleware('permission:accounts,view')->name('accounts');
    Route::get('/accounts/create', [AdminAccountsController::class, 'create'])->middleware('permission:accounts,add')->name('accounts.create');
    Route::get('/roles', [AdminRolesController::class, 'index'])->middleware('permission:accounts,view')->name('roles.index');
    Route::get('/roles/create', [AdminRolesController::class, 'create'])->middleware('permission:accounts,add')->name('roles.create');
    Route::post('/roles', [AdminRolesController::class, 'store'])->middleware('permission:accounts,add')->name('roles.store');
    Route::get('/roles/{role}/edit', [AdminRolesController::class, 'edit'])->middleware('permission:accounts,edit')->name('roles.edit');
    Route::put('/roles/{role}', [AdminRolesController::class, 'update'])->middleware('permission:accounts,edit')->name('roles.update');
    Route::delete('/roles/{role}', [AdminRolesController::class, 'destroy'])->middleware('permission:accounts,delete')->name('roles.destroy');
    Route::post('/accounts', [AdminAccountsController::class, 'store'])->middleware('permission:accounts,add')->name('accounts.store');
    Route::get('/accounts/{user}/edit', [AdminAccountsController::class, 'edit'])->middleware('permission:accounts,edit')->name('accounts.edit');
    Route::put('/accounts/{user}', [AdminAccountsController::class, 'update'])->middleware('permission:accounts,edit')->name('accounts.update');
    Route::patch('/accounts/{user}/status', [AdminAccountsController::class, 'updateStatus'])->middleware('permission:accounts,edit')->name('accounts.status');
    Route::delete('/accounts/{user}', [AdminAccountsController::class, 'destroy'])->middleware('permission:accounts,delete')->name('accounts.destroy');
    Route::post('/logout', [AdminLoginController::class, 'destroy'])->name('logout');
});
