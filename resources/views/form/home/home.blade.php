@extends('adminlte::page')
@section('title', 'ផ្ទាំងព័ត៌មាន')


@section('content')
<div class="container-fluid">
    <!-- Welcome Banner -->
    <div class="welcome-card">
        <div class="welcome-text">
            <h1>
                សូមស្វាគមន៍មកកាន់
            </h1>
            <h2>
                {{ $setting->system_name ?? 'មន្ទីរសម្រាកព្យាបាលព្រំ សន្តិភាព' }}
            </h2>
            <p>
                ប្រព័ន្ធគ្រប់គ្រងមន្ទីរព្យាបាល
                ដែលជួយគ្រប់គ្រងអ្នកជំងឺ វេជ្ជបណ្ឌិត
                ថ្នាំពេទ្យ ការទូទាត់ និងទិន្នន័យសុខាភិបាល
                ប្រកបដោយប្រសិទ្ធភាព។
            </p>
            <a href="{{ url('department/department') }}" class="btn btn-success">
                <i class="fas fa-hospital"></i>
                ប្រព័ន្ធគ្រប់គ្រង
            </a>
        </div>

        <div class="welcome-image">
            <img src="{{ $setting && $setting->logo
    ? asset('storage/' . $setting->logo)
    : asset('vendor/adminlte/dist/img/logo.jpg')
    }}" alt="Clinic">
        </div>
    </div>

    <!-- Feature Cards -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="home-card">
                <i class="fas fa-user-md"></i>
                <h4>
                    វេជ្ជបណ្ឌិត
                </h4>
                <p>
                    គ្រប់គ្រងព័ត៌មានវេជ្ជបណ្ឌិត
                </p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="home-card">
                <i class="fas fa-user-injured"></i>
                <h4>
                    អ្នកជំងឺ
                </h4>
                <p>
                    គ្រប់គ្រងប្រវត្តិអ្នកជំងឺ
                </p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="home-card">
                <i class="fas fa-pills"></i>
                <h4>
                    ឱសថស្ថាន
                </h4>
                <p>
                    គ្រប់គ្រងថ្នាំ និងស្តុក
                </p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="home-card">
                <i class="fas fa-flask"></i>
                <h4>
                    មន្ទីរពិសោធន៍
                </h4>
                <p>
                    គ្រប់គ្រងលទ្ធផលតេស្ត
                </p>
            </div>
        </div>
    </div>

    <!-- Quick Information -->
    <div class="clinic-info mt-4">
        <div>
            <i class="fas fa-clock"></i>
            <strong>ម៉ោងធ្វើការ</strong>
            <br>
            {{ $setting->working_hours ?? '07:00 AM - 08:00 PM' }}
        </div>
        <div>
            <i class="fas fa-phone"></i>
            <strong>ទំនាក់ទំនង</strong>
            <br>
            {{ $setting->phone ?? '012 XXX XXX' }}
        </div>
        <div>
            <i class="fas fa-map-marker-alt"></i>
            <strong>ទីតាំង</strong>
            <br>
            {{ $setting->address ?? 'Cambodia' }}
        </div>
    </div>
</div>


@stop


@section('css')

<style>
    body {
        background: #f8faf9;
    }

    /* Welcome */
    .welcome-card {
        background: white;
        border-radius: 20px;
        padding: 35px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .08);
    }

    .welcome-text {
        width: 60%;
    }

    .welcome-text h1 {
        color: #006D36;
        font-size: 35px;
        font-weight: 700;
    }


    .welcome-text h2 {
        font-size: 28px;
        font-weight: 900;
    }


    .welcome-text p {
        font-size: 18px;
        line-height: 2;
        color: #555;
    }


    .welcome-image img {
        width: 260px;
    }


    .btn-success {
        background: #006D36;
        border: none;
        padding: 12px 25px;
        border-radius: 10px;
    }


    /* Feature */
    .home-card {
        background: white;
        text-align: center;
        padding: 25px;
        border-radius: 15px;
        height: 180px;
        transition: .3s;
    }


    .home-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, .1);
    }

    .home-card i {
        font-size: 40px;
        color: #006D36;
    }


    .home-card h4 {
        margin-top: 15px;
    }

    /* Information */

    .clinic-info {
        background: #006D36;
        color: white;
        border-radius: 15px;
        padding: 25px;
        display: flex;
        justify-content: space-around;
        text-align: center;
    }


    .clinic-info i {
        font-size: 25px;
        margin-bottom: 10px;
    }
</style>

@stop