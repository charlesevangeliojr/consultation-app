@extends('admin.layout')
@section('title', 'Edit User')
@section('content')
    <div class="account-page-heading">
        <div><p class="account-eyebrow">User Management</p><h2>Edit User</h2><p>Update account details and permissions in one place.</p></div>
        <a href="{{ route('admin.accounts') }}" class="account-button account-button-secondary">Cancel</a>
    </div>
    @include('admin.accounts.form', ['formAction' => route('admin.accounts.update', $account), 'isEditing' => true])
    @include('admin.accounts.activity')
    @include('admin.accounts.school-profile')
@endsection
