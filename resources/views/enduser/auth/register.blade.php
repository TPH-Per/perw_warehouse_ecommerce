@extends('enduser.layout')

@section('title','Đăng ký')

@section('content')
  <h2>Đăng ký</h2>
  <form method="post" action="{{ url('/enduser/register') }}">
    @csrf
    <label>Họ tên<input type="text" name="name" value="{{ old('name') }}" required /></label>
    <label>Email<input type="email" name="email" value="{{ old('email') }}" required /></label>
    <label>Điện thoại<input type="text" name="phone_number" value="{{ old('phone_number') }}" /></label>
    <label>Mật khẩu<input type="password" name="password" required /></label>
    <label>Nhập lại mật khẩu<input type="password" name="password_confirmation" required /></label>
    <button type="submit">Đăng ký</button>
  </form>
@endsection

