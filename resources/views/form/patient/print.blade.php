<!DOCTYPE html>
<html lang="km">

<head>
    <meta charset="UTF-8">
    <title>ប័ណ្ណព័ត៌មានអ្នកជំងឺ - {{ $patient->full_name }}</title>
    <style>
        body {
            font-family: 'Khmer OS Battambang', Arial, sans-serif;
            font-size: 14px;
            color: #000;
            margin: 0;
            padding: 20px;
            background: #f4f6f9;
        }

        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 30px;
            border: 1px solid #ddd;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .header h3 {
            margin: 0;
            color: #0056b3;
            font-size: 20px;
        }

        .header p {
            margin: 4px 0;
            font-size: 12px;
            color: #555;
        }

        .title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0;
            text-decoration: underline;
        }

        .info-table {
            width: 100%;
            margin-bottom: 30px;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 8px;
            vertical-align: top;
            border-bottom: 1px dashed #eee;
        }

        .footer {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }

        .signature {
            text-align: center;
            width: 250px;
            float: right;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .no-print {
                display: none;
            }

            .invoice-box {
                border: none;
                padding: 0;
                box-shadow: none;
            }
        }
    </style>
</head>

<body onload="window.print()">

    <div class="text-center no-print" style="margin-bottom: 20px; text-align: center;">
        <button onclick="window.print()"
            style="padding: 10px 20px; background: #28a745; color: #fff; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: bold;">
            🖨️ បោះពុម្ព (Print Patient Card)
        </button>
        <a href="{{ route('patients.index') }}"
            style="margin-left: 10px; text-decoration: none; padding: 10px 20px; background: #6c757d; color: #fff; border-radius: 5px; font-weight: bold;">ត្រឡប់ក្រោយ</a>
    </div>

    <div class="invoice-box">
        <div class="header">
            <h3>មន្ទីរសម្រាកព្យាបាល ព្រំ សន្តិភាព</h3>
            <p>PRUM SANTEPHEAP CLINIC</p>
            <p>ផ្ទះលេខ ១០២ ផ្លូវបេតុង ភូមិ ០៤ សង្កាត់ ទួលសង្កែ ខណ្ឌ ប្ញស្សីកែវ រាជធានីភ្នំពេញ</p>
            <p>Tel: 088 28 07 495 / 096 50 68 839 / 095 45 22 96</p>
        </div>

        <div class="title">ព័ត៌មានអ្នកជំងឺ (PATIENT INFORMATION)</div>

        <table class="info-table">
            <tr>
                <td style="width: 50%;"><strong>លេខកូដ (Code):</strong> {{ $patient->patient_code }}</td>
                <td style="width: 50%;"><strong>ថ្ងៃខែឆ្នាំចុះឈ្មោះ:</strong>
                    {{ $patient->created_at ? $patient->created_at->format('d/m/Y') : '-' }}</td>
            </tr>
            <tr>
                <td><strong>ឈ្មោះពេញ (Name):</strong> {{ $patient->full_name }}</td>
                <td><strong>ភេទ / ថ្ងៃខែឆ្នាំកំណើត (Sex / DOB):</strong>
                    {{ $patient->sex == 'Male' ? 'ប្រុស' : 'ស្រី' }} | {{ $patient->date_of_birth }}</td>
            </tr>
            <tr>
                <td><strong>លេខទូរសព្ទ (Tel):</strong> {{ $patient->phone ?? '-' }}</td>
                <td><strong>អសយដ្ឋាន (Address):</strong> {{ $patient->address ?? '-' }}</td>
            </tr>
        </table>

        <p style="font-size: 13px; color: #555; line-height: 1.6; margin-top: 20px;">
            យើងខ្ញុំសុំអនុញ្ញាតដោយមន្ទីរសម្រាកព្យាបាល ព្រំ សន្តិភាព ព្យាបាលទៅតាមលក្ខខណ្ឌ និងសម្បទាភាវារបស់គ្រូពេទ្យ។
            យើងខ្ញុំដឹងដែថា មន្ទីរសម្រាកព្យាបាល ព្រំ សន្តិភាព
            ជួយសង្គ្រោះអ្នកជំងឺមានគំនិតស្ម័គ្រចិត្តទទួលយកទាំងក្នុងពេលនេះ និងពេលអនាគតៀរយេៗ។
        </p>

        <div class="footer">
            <div style="float: left; margin-top: 40px;">
                <p><strong>បោះត្រា និងហត្ថលេខាបុគ្គលិកទទួលបន្ទុក</strong></p>
            </div>
            <div class="signature">
                <p>ភ្នំពេញ, ថ្ងៃទី {{ date('d') }} ខែ {{ date('m') }} ឆ្នាំ {{ date('Y') }}</p>
                <p><strong>ហត្ថលេខាអ្នកជំងឺ ឬសាច់ញាតិ</strong></p>
                <br><br><br>
                <p>......................................................</p>
            </div>
        </div>
    </div>
</body>

</html>
