<!DOCTYPE html>
<html>

<head>
    <title>ERS:: Sync Data Integration System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f4f7fb;
        }

        .navbar-custom {
            background: #0f172a;
        }

        .settings-card {
            border: none;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .08);
        }

        .page-title {
            font-weight: 700;
            color: #0f172a;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }

        .footer strong {
            color: #8B1E1E;
        }
    </style>

</head>

<body>

    <nav class="navbar navbar-dark navbar-custom">
        <div class="container">

            <span class="navbar-brand">
                ERS Sync Data Integration System
            </span>

            <form action="{{ route('logout') }}" method="POST">
                @csrf

                <button class="btn btn-danger">
                    Logout
                </button>
            </form>

        </div>
    </nav>

    <div class="container mt-5">

        <div class="row g-4">

            <!-- Server Configuration -->
            <div class="col-md-6">

                <div class="card settings-card h-100">

                    <div class="card-body p-4">

                        @php
                            $ersApiStatus =
                                DB::table('system_settings')->where('key', 'api_status')->value('value') ??
                                'offline';
                        @endphp

                        <div class="d-flex justify-content-between align-items-center mb-4">

                            <h3 class="page-title mb-0">
                                Server Configuration
                            </h3>

                        </div>

                        @if (session('server_success'))
                            <div class="alert alert-success">
                                {{ session('server_success') }}
                            </div>
                        @endif

                        @if (session('server_error'))
                            <div class="alert alert-danger">
                                {{ session('server_error') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('settings.ip.update') }}">
                            @csrf

                            <label class="form-label">
                                Remote Server Address
                            </label>

                            <input type="text" name="server_ip" class="form-control form-control-lg"
                                value="{{ $ip }}" placeholder="41.41.129.31:886" required>

                            <small class="text-muted">
                                Examples: 41.41.129.31 or 41.41.129.31:886
                            </small>

                            <button class="btn btn-primary w-100 mt-4">
                                Save Configuration
                            </button>

                        </form>

                        <div class="card mt-4 border-0 shadow-sm">

                            <div class="card-body">

                                <div class="d-flex justify-content-between align-items-center">

                                    <div>

                                        <h5 class="mb-1">
                                            ERS API
                                        </h5>

                                        @if ($ersApiStatus === 'online')
                                            <strong class="text-success">
                                                APIs are currently Online
                                            </strong>
                                        @else
                                            <strong class="text-danger">
                                                APIs are currently Offline
                                            </strong>
                                        @endif
                                    </div>

                                    <form method="POST" action="{{ route('settings.api.status') }}">
                                        @csrf

                                        @if ($ersApiStatus === 'online')
                                            <input type="hidden" name="status" value="offline">

                                            <button type="submit" class="btn btn-danger">
                                                Set Offline
                                            </button>
                                        @else
                                            <input type="hidden" name="status" value="online">

                                            <button type="submit" class="btn btn-success">
                                                Set Online
                                            </button>
                                        @endif

                                    </form>

                                </div>

                            </div>

                        </div>

                        {{-- Last Synchronization --}}

                        @if ($lastSyncLog['available'])
                            <div class="card mt-4">

                                <div class="card-body p-4">

                                    <div class="d-flex justify-content-between align-items-center mb-3">

                                        <h4 class="mb-0">
                                            Last Synchronization
                                        </h4>

                                        <span class="badge bg-success">
                                            Completed
                                        </span>

                                    </div>

                                    <pre class="bg-dark text-light p-3 rounded"
                                        style="
                    max-height: 350px;
                    overflow-y: auto;
                    font-size: 13px;
                    white-space: pre-wrap;
                    word-break: break-word;
                ">{{ $lastSyncLog['log'] }}</pre>

                                </div>

                            </div>
                        @else
                            <div class="alert alert-secondary mt-4">
                                {{ $lastSyncLog['log'] }}
                            </div>
                        @endif

                    </div>

                </div>

            </div>

            <!-- Sync Data -->
            <div class="col-md-6">

                <div class="card settings-card h-100">

                    <div class="card-body p-4">

                        <h3 class="page-title mb-4">
                            Manually Sync
                        </h3>

                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show">
                                <strong>Success!</strong>
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if (session('sync_success'))
                            <div class="alert alert-success">
                                <strong>{{ session('sync_success') }}</strong>

                                <hr>

                                <ul class="mb-0">
                                    <li>Students: {{ session('students_count', 0) }}</li>

                                    @if (session('sync_details'))
                                        @foreach (session('sync_details') as $name => $count)
                                            <li>{{ $name }}: {{ $count }}</li>
                                        @endforeach
                                    @endif
                                </ul>
                            </div>
                        @endif

                        @if (session('sync_error'))
                            <div class="alert alert-danger">
                                <strong>{{ session('sync_error') }}</strong>

                                @if (session('failed_step'))
                                    <br>
                                    Failed Step:
                                    <strong>{{ session('failed_step') }}</strong>
                                @endif
                            </div>
                        @endif

                        <form method="POST" action="{{ route('sync.start') }}">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    Faculty
                                </label>

                                <select id="faculty" name="faculty_code" class="form-select" required>
                                    <option value="">Select Faculty</option>

                                    @foreach ($faculties as $faculty)
                                        <option value="{{ $faculty->faculty_code }}"
                                            {{ old('faculty_code') == $faculty->faculty_code ? 'selected' : '' }}>
                                            {{ $faculty->faculty_desc_e }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    Major
                                </label>

                                <select id="major" name="major_code" class="form-select" required>

                                    <option value="">Select Faculty First</option>

                                </select>
                                <input type="hidden" id="old_major" value="{{ old('major_code') }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    Batch
                                </label>

                                <select name="batch" class="form-select" required>
                                    <option value="">Select Batch</option>

                                    @foreach ($batches as $batch)
                                        <option value="{{ $batch->batch }}"
                                            {{ old('batch') == $batch->batch ? 'selected' : '' }}>
                                            {{ $batch->batch }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    Semester
                                </label>

                                <select name="semester" class="form-select" required>
                                    <option value="">Select Semester</option>

                                    @for ($i = 1; $i <= 10; $i++)
                                        <option value="{{ $i }}"
                                            {{ old('semester') == $i ? 'selected' : '' }}>
                                            {{ $i }}
                                        </option>
                                    @endfor
                                </select>
                            </div>

                            <button class="btn btn-success w-100 btn-lg">
                                Start Synchronization
                            </button>
                        </form>
                    </div>

                </div>

            </div>

        </div>

        <div class="text-center mt-4 footer">
            Designed & Developed by
            <a href="https://fu.edu.sd/CESD" style="text-decoration:none" target="_blank">
                <strong>CESD</strong>
            </a>
        </div>

    </div>
    <script>
        const faculty = document.getElementById('faculty');
        const major = document.getElementById('major');

        const oldMajor = "{{ old('major_code') }}";


        function loadMajors(facultyCode, selectedMajor = '') {

            // No faculty selected
            if (!facultyCode) {

                major.innerHTML =
                    '<option value="">Select Faculty First</option>';

                return;
            }

            // Faculty selected → load majors
            major.innerHTML =
                '<option value="">Loading...</option>';

            fetch("{{ url('/get-majors') }}/" + facultyCode)
                .then(response => response.json())
                .then(data => {

                    // Clear everything
                    major.innerHTML = '';


                    data.forEach(function(item) {

                        const option =
                            document.createElement('option');

                        option.value = item.major_code;

                        option.textContent =
                            item.major_desc_e;


                        // Restore previously selected major
                        if (
                            String(item.major_code) ===
                            String(selectedMajor)
                        ) {
                            option.selected = true;
                        }


                        major.appendChild(option);
                    });


                    // If there is only one major,
                    // select it automatically
                    if (data.length === 1) {

                        major.value =
                            data[0].major_code;
                    }

                })
                .catch(error => {

                    console.error(
                        'Error loading majors:',
                        error
                    );

                    major.innerHTML =
                        '<option value="">Failed to load majors</option>';
                });
        }


        // Faculty changed
        faculty.addEventListener('change', function() {

            loadMajors(this.value);

        });

        // Restore values after submit
        document.addEventListener('DOMContentLoaded', function() {

            if (faculty.value) {

                loadMajors(
                    faculty.value,
                    oldMajor
                );

            }

        });
    </script>
</body>

</html>
