<!-- Sidebar Navigation Layout -->
<aside class="main-sidebar sidebar-light-primary elevation-4">
    <!-- Brand Logo -->
    <a href="{{ route('home') }}" class="brand-link">
        <img src="{{ asset('vendor/adminlte/dist/img/logo.jpg') }}" alt="Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-bold">PrumSantepheap</span>
    </a>

    <!-- Sidebar Content -->
    <div class="sidebar">
        <!-- Sidebar User Panel -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
            <div class="image">
                <i class="fas fa-user-circle fa-2x text-secondary mr-2"></i>
            </div>
            <div class="info">
                <a href="#" class="d-block font-weight-bold text-dark">{{ Auth::user()->name ?? 'User' }}</a>
                <span class="badge badge-success small">
                    {{ Auth::user()->roles->pluck('name')->first() ?? 'Member' }}
                </span>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                
                <!-- 1. Dashboard (All Authenticated Users) -->
                <li class="nav-item">
                    <a href="{{ route('home') }}" class="nav-link {{ request()->is('home*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>ផ្ទាំងព័ត៌មាន (Dashboard)</p>
                    </a>
                </li>

                <!-- 2. Department (All Authenticated Users) -->
                <li class="nav-item">
                    <a href="{{ route('department.index') }}" class="nav-link {{ request()->is('department*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-sitemap"></i>
                        <p>ដេប៉ាតឺម៉ង់ (Department)</p>
                    </a>
                </li>

                <!-- 3. Clinical Section: Doctor, Nurse & Admin -->
                @hasanyrole('admin|doctor|nurse')
                <li class="nav-header text-uppercase font-weight-bold text-muted mt-2">ការងារព្យាបាល (Clinical)</li>
                <li class="nav-item">
                    <a href="{{ url('doctor') }}" class="nav-link {{ request()->is('doctor*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-user-md"></i>
                        <p>វេជ្ជបណ្ឌិត (Doctors)</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ url('patients') }}" class="nav-link {{ request()->is('patients*') || request()->is('patient*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-user-injured"></i>
                        <p>អ្នកជំងឺ (Patients)</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ url('appointments') }}" class="nav-link {{ request()->is('appointments*') || request()->is('appointment*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-calendar-check"></i>
                        <p>ការណាត់ជួប (Appointments)</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ url('lab') }}" class="nav-link {{ request()->is('lab*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-flask"></i>
                        <p>មន្ទីរពិសោធន៍ (Laboratory)</p>
                    </a>
                </li>
                @endhasanyrole

                <!-- 4. Pharmacy Section: Pharmacist & Admin -->
                @hasanyrole('admin|pharmacist')
                <li class="nav-header text-uppercase font-weight-bold text-muted mt-2">ឱសថស្ថាន (Pharmacy)</li>
                <li class="nav-item">
                    <a href="{{ route('pharmacy.index') }}" class="nav-link {{ request()->is('pharmacy*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-pills"></i>
                        <p>ឱសថស្ថាន (Pharmacy)</p>
                    </a>
                </li>
                @endhasanyrole

                <!-- 5. Finance Section: Cashier & Admin -->
                @hasanyrole('admin|cashier')
                <li class="nav-header text-uppercase font-weight-bold text-muted mt-2">ហិរញ្ញវត្ថុ (Finance)</li>
                <li class="nav-item">
                    <a href="{{ route('billing.index') }}" class="nav-link {{ request()->is('billing*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-file-invoice-dollar"></i>
                        <p>ការទូទាត់ប្រាក់ (Billing)</p>
                    </a>
                </li>
                @endhasanyrole

                <!-- 6. Admin Only Section: User Management & System Settings -->
                @hasrole('admin')
                <li class="nav-header text-uppercase font-weight-bold text-muted mt-2">ការគ្រប់គ្រងប្រព័ន្ធ (Admin)</li>
                <li class="nav-item">
                    <a href="{{ route('user.index') }}" class="nav-link {{ request()->is('user*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-users"></i>
                        <p>គ្រប់គ្រងអ្នកប្រើប្រាស់ (Users)</p>
                    </a>
                </li>
                <li class="nav-item {{ request()->is('settings*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->is('settings*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-cogs"></i>
                        <p>
                            ការកំណត់ប្រព័ន្ធ (Settings)
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('settingsgeneral.index') }}" class="nav-link {{ request()->is('settings/general*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>ការកំណត់ទូទៅ (General)</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('settingsbillings.index') }}" class="nav-link {{ request()->is('settings/billing*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>ការកំណត់វិក្កយបត្រ (Billing)</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('settingsqrcode.index') }}" class="nav-link {{ request()->is('settings/qrcode*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>ការកំណត់ QR Code</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('settingsbackup.index') }}" class="nav-link {{ request()->is('settings/backup*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>ការបម្រុងទុកទិន្នន័យ (Backup)</p>
                            </a>
                        </li>
                    </ul>
                </li>
                @endhasrole

                <!-- 7. Support -->
                <li class="nav-item mt-3">
                    <a href="{{ route('support.index') }}" class="nav-link {{ request()->is('support*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-life-ring"></i>
                        <p>ជំនួយ (Support)</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>
