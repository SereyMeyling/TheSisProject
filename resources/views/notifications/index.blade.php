@extends('adminlte::page')

@section('title', 'ការជូនដំណឹង')

@section('content_header')
    <h1>ការជូនដំណឹង</h1>
@endsection

@section('content')

    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">

            <h3 class="card-title mb-0">ការជូនដំណឹងទាំងអស់</h3>

            @if($notifications->where('read_at', null)->count() > 0)
                <form action="{{ route('notifications.readAll') }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-primary">
                        សម្គាល់ថាបានអានទាំងអស់
                    </button>
                </form>
            @endif

        </div>

        <div class="card-body p-0">

            @forelse($notifications as $notification)

                <div
                    class="d-flex align-items-start px-3 py-3 {{ $loop->last ? '' : 'border-bottom' }} {{ is_null($notification->read_at) ? 'bg-light' : '' }}">

                    {{-- Icon --}}
                    <div class="mr-3 mt-1">
                        <i class="fas {{ $notification->data['icon'] ?? 'fa-bell' }} {{ $notification->data['color'] ?? 'text-primary' }}"
                            style="font-size: 20px;"></i>
                    </div>

                    {{-- Content --}}
                    <div class="flex-grow-1">

                        <div class="d-flex justify-content-between align-items-start">

                            <strong>
                                {{ $notification->data['title'] ?? 'ការជូនដំណឹង' }}
                                @if(is_null($notification->read_at))
                                    <span class="badge badge-danger ml-1" style="font-size: 9px;">ថ្មី</span>
                                @endif
                            </strong>

                            <small class="text-muted ml-2" style="white-space: nowrap;">
                                {{ $notification->created_at->diffForHumans() }}
                            </small>

                        </div>

                        <div class="text-muted" style="font-size: 13px;">
                            {{ $notification->data['message'] ?? '' }}
                        </div>

                        <div class="mt-2">

                            @if(!empty($notification->data['url']))
                                <a href="{{ $notification->data['url'] }}" class="btn btn-xs btn-primary"
                                    @if(is_null($notification->read_at))
                                        onclick="event.preventDefault(); document.getElementById('read-form-{{ $notification->id }}').submit();"
                                    @endif>
                                    មើលលម្អិត
                                </a>
                            @endif

                            @if(is_null($notification->read_at))
                                <form id="read-form-{{ $notification->id }}"
                                    action="{{ route('notifications.read', $notification->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @if(empty($notification->data['url']))
                                        <button type="submit" class="btn btn-xs btn-outline-secondary">
                                            សម្គាល់ថាបានអាន
                                        </button>
                                    @endif
                                </form>
                            @endif

                        </div>

                    </div>

                </div>

            @empty

                <div class="text-center text-muted py-5">
                    <i class="fas fa-bell-slash mb-2" style="font-size: 32px;"></i>
                    <p class="mb-0">មិនទាន់មានការជូនដំណឹងទេ</p>
                </div>

            @endforelse

        </div>

        @if($notifications->hasPages())
            <div class="card-footer">
                {{ $notifications->links() }}
            </div>
        @endif

    </div>

@endsection