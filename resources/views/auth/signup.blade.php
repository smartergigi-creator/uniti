<!DOCTYPE html>
<html>

<head>

    <title>Signup</title>

    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">

</head>

<body>


    <div class="container">

        <div class="left">

            {{-- <h2>Why Join?</h2>



            <p>✔ Convert PDF to Interactive Ebooks</p>
            <p>✔ Easy Upload and Management</p>
            <p>✔ Fast Processing & Download</p>
            <p>✔ Access Anytime, Anywhere</p> --}}


        </div>


        <div class="right">

            <div class="form-box">

                <h2>Create Account</h2>

                <input type="text" id="fname" placeholder="First Name">

                <input type="text" id="lname" placeholder="Last Name">

                <input type="email" id="email" placeholder="Email">

                <input type="password" id="password" placeholder="Password">


                <button onclick="signup()">Sign Up</button>

                <br><br>

                <a href="/login">Already have account?</a>

            </div>

        </div>

    </div>


    <script src="{{ asset('js/auth.js') }}"></script>

</body>

</html>
