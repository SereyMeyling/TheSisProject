@extends('adminlte::page')

@section('title', 'Support Center')

@section('content')
<div class="container-fluid">

    <!-- Header -->
    <div class="support-header mb-4">
        <div class="row align-items-center">

            <div class="col-md-8">
                <h2><i class="fas fa-headset text-success"></i> Support Center</h2>
                <p class="text-muted mb-0">
                    ត្រូវការជំនួយមែនទេ? អាចទាក់ទងក្រុមអភិវឌ្ឍន៏បានគ្រប់ពេលវេលា
                </p>
            </div>

            <div class="col-md-4 text-md-right mt-3 mt-md-0">
                <button class="btn btn-success">
                    <i class="fas fa-phone"></i>
                    ជំនួយការបន្ទាន់
                </button>
            </div>

        </div>
    </div>

    <div class="row">

        <!-- Developers -->

        <div class="col-lg-8">

            <div class="card shadow-sm border-0">

                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-users text-success"></i>
                        ក្រុមអភិវឌ្ឍន៏
                    </h5>
                </div>

                <div class="card-body">

                    <div class="row">

                        <!-- Developer -->
                        <div class="col-md-6 mb-4">
                            <div class="developer-card">
                                <img src={{ asset('images/supportteam/ChhivSereyMeyling.png') }} alt="Developer 1">
                                <h5>ឈីវ សិរីម៉ីលិញ</h5>
                                <span class="badge badge-success">
                                    មេក្រុម
                                </span>
                                <hr>
                                <p>
                                    <i class="fas fa-phone"></i>
                                    012 345 678
                                </p>

                                <p>
                                    <i class="fas fa-envelope"></i>
                                    meyling@gmail.com
                                </p>

                                <button class="btn btn-outline-success btn-sm copy-phone" data-copy="012345678">

                                    Copy Phone

                                </button>

                            </div>

                        </div>
                        <!-- Developer -->

                        <div class="col-md-6 mb-4">

                            <div class="developer-card">
                                <img src={{ asset('images/supportteam/chandany.png') }} alt="Developer 1">
                                <h5>ចាន់ ដានី</h5>
                                <span class="badge badge-success">
                                    សមាជិកក្រុម
                                </span>
                                <hr>
                                <p>
                                    <i class="fas fa-phone"></i>
                                    012 345 678
                                </p>

                                <p>
                                    <i class="fas fa-envelope"></i>
                                    dany@gmail.com
                                </p>

                                <button class="btn btn-outline-success btn-sm copy-phone" data-copy="012345678">

                                    Copy Phone

                                </button>

                            </div>

                        </div>
                        <!-- Developer -->

                        <div class="col-md-6 mb-4">

                            <div class="developer-card">
                                <img src={{ asset('images/supportteam/deounthavy.png') }} alt="Developer 1">
                                <h5>ឌឿន ថាវី</h5>
                                <span class="badge badge-success">
                                    សមាជិកក្រុម
                                </span>
                                <hr>
                                <p>
                                    <i class="fas fa-phone"></i>
                                    012 345 678
                                </p>

                                <p>
                                    <i class="fas fa-envelope"></i>
                                    thavy@gmail.com
                                </p>

                                <button class="btn btn-outline-success btn-sm copy-phone" data-copy="012345678">

                                    Copy Phone

                                </button>

                            </div>

                        </div>
                        <!-- Developer -->

                        <div class="col-md-6 mb-4">

                            <div class="developer-card">
                                <img src={{ asset('images/supportteam/namvanna.png') }} alt="Developer 1">
                                <h5>ណាំ វណ្ណា</h5>
                                <span class="badge badge-success">
                                    សមាជិកក្រុម
                                </span>
                                <hr>
                                <p>
                                    <i class="fas fa-phone"></i>
                                    012 345 678
                                </p>

                                <p>
                                    <i class="fas fa-envelope"></i>
                                    vanna@gmail.com
                                </p>

                                <button class="btn btn-outline-success btn-sm copy-phone" data-copy="012345678">

                                    Copy Phone

                                </button>

                            </div>

                        </div>
                        <!-- Developer -->

                        <div class="col-md-6 mb-4">

                            <div class="developer-card">
                                <img src={{ asset('images/supportteam/phenchentra.png') }} alt="Developer 1">
                                <h5>ផេន ចិត្រ្តា</h5>
                                <span class="badge badge-success">
                                    សមាជិកក្រុម
                                </span>
                                <hr>
                                <p>
                                    <i class="fas fa-phone"></i>
                                    012 345 678
                                </p>

                                <p>
                                    <i class="fas fa-envelope"></i>
                                    chentra@gmail.com
                                </p>

                                <button class="btn btn-outline-success btn-sm copy-phone" data-copy="012345678">

                                    Copy Phone

                                </button>

                            </div>

                        </div>


                        <!-- Copy this card 4 more times -->

                    </div>

                </div>

            </div>

        </div>

        <!-- Right -->

        <div class="col-lg-4">

            <div class="card shadow-sm border-0 mb-4">

                <div class="card-header bg-success text-white">

                    ព័ត៏មានប្រព័ន្ធ

                </div>

                <div class="card-body">

                    <table class="table table-borderless table-sm">

                        <tr>
                            <td>ជំនាន់</td>
                            <td>1.0.0</td>
                        </tr>

                        <tr>
                            <td>Laravel</td>
                            <td>8</td>
                        </tr>

                        <tr>
                            <td>PHP</td>
                            <td>8.2</td>
                        </tr>

                        <tr>
                            <td>Database</td>
                            <td>MySQL</td>
                        </tr>

                        <tr>
                            <td>Server</td>
                            <td>Ubuntu</td>
                        </tr>

                    </table>

                </div>

            </div>

            <div class="card border-0 shadow-sm">

                <div class="card-header bg-danger text-white">

                    Emergency Contact

                </div>

                <div class="card-body">

                    <h4>☎ 012 888 999</h4>

                    <p>support@hospital.com</p>

                    <small>
                        Monday - Friday<br>
                        8:00 AM - 5:00 PM
                    </small>

                </div>

            </div>

        </div>

    </div>

    <!-- FAQ -->

    <div class="card shadow-sm border-0 mt-4">

        <div class="card-header bg-white">

            សំណួរញឹកញាប់ (FAQ)

        </div>

        <div class="card-body">

            <div id="faq">

                <div class="card">

                    <div class="card-header">

                        <a class="card-link" data-toggle="collapse" href="#q1">

                            ខ្ញុំភ្លេចលេខសម្ងាត់សម្រាប់ចូល

                        </a>

                    </div>

                    <div id="q1" class="collapse show" data-parent="#faq">

                        <div class="card-body">
                            សូមទាក់ទងអ្នកគ្រប់គ្រងប្រព័ន្ធរបស់អ្នកដើម្បីកំណត់លេខសម្ងាត់របស់អ្នកឡើងវិញ។

                        </div>

                    </div>

                </div>

                <div class="card">

                    <div class="card-header">

                        <a class="collapsed card-link" data-toggle="collapse" href="#q2">

                            មិនអាចប្រើប្រាស់ព្រីនបាន

                        </a>

                    </div>

                    <div id="q2" class="collapse" data-parent="#faq">

                        <div class="card-body">

                            សូមចាប់ផ្តើមព្រីនឡើងវិញ ហើយព្យាយាមម្តងទៀត។

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>
@stop
@section('css')
<style>
    .support-header {
        background: #fff;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .05);
    }

    .developer-card {
        background: #fff;
        text-align: center;
        border-radius: 15px;
        padding: 25px;
        transition: .3s;
        border: 1px solid #eee;
    }

    .developer-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, .1);
    }

    .developer-card img {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        margin-bottom: 15px;
        border: 4px solid #006D36;
        object-fit: cover;
        object-position: center;
    }

    .developer-card p {
        margin-bottom: 8px;
    }

    .card {
        border-radius: 15px;
    }

    .card-header {
        font-weight: 600;
    }
</style>
@stop
@section('js')
@parent
<script>
    $('.copy-phone').click(function () {

        let phone = $(this).data('copy');

        navigator.clipboard.writeText(phone);

        $(this).html('<i class="fas fa-check"></i> Copied');

        let btn = $(this);

        setTimeout(function () {

            btn.html('Copy Phone');

        }, 2000);

    });
</script>
@stop
