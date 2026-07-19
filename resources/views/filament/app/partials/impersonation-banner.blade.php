<div class="bg-amber-500 px-4 py-2 text-center text-sm font-medium text-amber-950">
    You are impersonating a tenant user.
    <form method="POST" action="{{ route('admin.leave-impersonation') }}" class="inline">
        @csrf
        <button type="submit" class="underline">
            Leave impersonation
        </button>
    </form>
</div>
