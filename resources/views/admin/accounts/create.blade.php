@extends('admin.layout')
@section('title', 'Add User')
@section('content')
    <div class="account-page-heading">
        <div><p class="account-eyebrow">User Management</p><h2>Create a teacher or student</h2><p>Choose the Teacher or Student role to give this account access to the consultation portal.</p></div>
        <a href="{{ route('admin.accounts') }}" class="account-button account-button-secondary">Cancel</a>
    </div>
    @include('admin.accounts.form', ['account' => null, 'formAction' => route('admin.accounts.store'), 'isEditing' => false])
@endsection
