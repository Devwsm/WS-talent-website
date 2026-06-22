@extends('template/dashboardLayout')
@section('content')
    <div class="w-full h-screen p-8 flex flex-col justify-center items-center">
        <div class="bg-[#1A1A1B] text-white p-8 w-full md:w-176 rounded-2xl">
            <h1 class="text-2xl lg:text-3xl font-bold uppercase text-center mb-4">Login</h1>
            @include('components/errors')
            <form action="{{ route('login.proses') }}" method="POST" class="flex flex-col gap-2">
                @csrf
                <label htmlFor="Username" class="text-xl lg:text-2xl capitalize font-semibold">Username</label>
                <input type="text" placeholder="Username" name="username" class="border p-2 mb-2 rounded-lg"/>
                <label htmlFor="password" class="text-xl lg:text-2xl capitalize font-semibold">password</label>
                <input type="password" placeholder="password" name="password" class="border p-2 mb-2 rounded-lg"/>
                <button type="submit" class="w-full text-white font-bold uppercase tracking-wide p-2 mt-4 bg-[#5E0006] hover:bg-[#5E0006]/70 transition rounded-lg">Login</button>
            </form>
        </div>
    </div>
@endsection