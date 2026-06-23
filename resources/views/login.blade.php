<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERS:: Sync Data Integration System</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            position: relative;

            background: linear-gradient(135deg,
                    #8B1E1E 0%,
                    #A52A2A 45%,
                    #C56A3D 100%);
        }

        /* Background Shapes */
        body::before {
            content: '';
            position: absolute;
            left: -150px;
            top: -100px;
            width: 500px;
            height: 900px;
            background: #ffffff;
            border-radius: 50%;
            opacity: .95;
        }

        body::after {
            content: '';
            position: absolute;
            right: -120px;
            bottom: -150px;
            width: 500px;
            height: 700px;
            background: rgba(214, 132, 72, .75);
            border-radius: 45%;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            border: none;
            border-radius: 25px;
            background: rgba(255, 255, 255, .95);
            backdrop-filter: blur(10px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, .25);
            position: relative;
            z-index: 10;
        }

        .logo {
            width: 110px;
            height: 110px;
            object-fit: contain;
        }

        h3 {
            color: #8B1E1E;
            font-weight: 700;
        }

        .form-control {
            border-radius: 12px;
            padding: 12px;
        }

        .form-control:focus {
            border-color: #A52A2A;
            box-shadow: 0 0 0 .2rem rgba(165, 42, 42, .15);
        }

        .btn-primary {
            background: #8B1E1E;
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
        }

        .btn-primary:hover {
            background: #721919;
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

    <div class="card login-card">
        <div class="card-body p-4">

            <div class="text-center mb-4">
                <img src="{{ asset('images/logo.png') }}" class="logo" alt="Logo">

                <h3 class="mt-3">ERS</h3>

                <p class="text-muted">
                    Sync Data Integration System
                </p>
            </div>

            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login.submit') }}">
                @csrf

                <div class="mb-3">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" value="{{ old('username') }}" required>
                </div>

                <div class="mb-3">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <button class="btn btn-primary w-100">
                    Login
                </button>
            </form>

            <div class="footer">
                Designed & Developed by <a href="https://fu.edu.sd/CESD" style="text-decoration: none"
                    target="_blank"><strong>CESD</strong></a>
            </div>

        </div>
    </div>

</body>

</html>
