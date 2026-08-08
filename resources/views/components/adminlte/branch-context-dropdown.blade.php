@php
    $user = auth()->user();
    $accessibleBranches = collect();
    $isConsolidated = false;
    $activeBranchName = null;
    $provinceName = null;

    if ($user) {
        $branchService = app(\App\Services\BranchService::class);
        $accessibleBranches = $branchService->getAccessibleBranchesForUser($user);
        $activeBranchId = session('active_branch_id');
        $isConsolidated = $activeBranchId === 'all';
        $provinceName = $user->province?->name ?? $user->branch?->province?->name;

        if (!$isConsolidated && $activeBranchId) {
            $activeBranchName = $accessibleBranches->firstWhere('id', (int)$activeBranchId)?->name;
        }
        if (!$activeBranchName && !$isConsolidated) {
            $activeBranchName = $user->branch?->name;
        }
    }
@endphp

@if($user && $user->hasRole('provincial_admin'))
<li class="nav-item dropdown me-2">
    <a class="nav-link dropdown-toggle d-flex align-items-center gap-1" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">
        <i class="bi bi-geo-alt-fill text-primary"></i>
        <span class="d-none d-md-inline fw-semibold">
            @if($isConsolidated)
                @if($provinceName)
                    {{ $provinceName }} (Consolidado)
                @else
                    Todas las Sucursales
                @endif
            @else
                {{ $activeBranchName ?? 'Seleccionar Sucursal' }}
            @endif
        </span>
    </a>
    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
        <li>
            <h6 class="dropdown-header text-uppercase fs-7 text-muted">Contexto de Sucursal</h6>
        </li>
        @if($user->hasRole('provincial_admin'))
        <li>
            <form action="{{ route('web.branch-context.switch') }}" method="POST" class="m-0">
                @csrf
                <input type="hidden" name="branch_id" value="all">
                <button type="submit" class="dropdown-item d-flex justify-content-between align-items-center {{ $isConsolidated ? 'active fw-bold' : '' }}">
                    <span>
                        <i class="bi bi-buildings me-2"></i> 
                        @if($user->hasRole('provincial_admin') && $provinceName)
                            Todas las sucursales de {{ $provinceName }}
                        @else
                            Todas las sucursales
                        @endif
                    </span>
                    <span class="badge bg-secondary ms-2">Consolidado</span>
                </button>
            </form>
        </li>
        <li><hr class="dropdown-divider"></li>
        @endif

        @foreach($accessibleBranches as $branch)
        <li>
            <form action="{{ route('web.branch-context.switch') }}" method="POST" class="m-0">
                @csrf
                <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                <button type="submit" class="dropdown-item d-flex align-items-center {{ (!$isConsolidated && ($activeBranchId == $branch->id || (!$activeBranchId && $user->branch_id == $branch->id))) ? 'active fw-bold' : '' }}">
                    <i class="bi bi-building me-2"></i> {{ $branch->name }}
                </button>
            </form>
        </li>
        @endforeach
    </ul>
</li>
@endif
