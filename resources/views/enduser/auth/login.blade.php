@extends('enduser.layout')

@section('title','Đăng nhập')

@section('content')
  <h2>Đăng nhập</h2>
  <form method="post" action="{{ url('/enduser/login') }}">
    @csrf
    <label>Email<input type="email" name="email" value="{{ old('email') }}" required /></label>
    <label>Mật khẩu<input type="password" name="password" required /></label>
    <label><input type="checkbox" name="remember" /> Ghi nhớ</label>
    <button type="submit">Đăng nhập</button>
  </form>
@endsection

