@if ($paginator->hasPages())
    <nav class="flex items-center justify-center space-x-4 mt-6" role="navigation">
        
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span class="px-4 py-2 text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed">
                ← Prev
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" 
               class="px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition">
                ← Prev
            </a>
        @endif

        {{-- Page Counter --}}
        <span class="text-gray-600 font-medium">
            Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}
        </span>

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" 
               class="px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition">
                Next →
            </a>
        @else
            <span class="px-4 py-2 text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed">
                Next →
            </span>
        @endif

    </nav>
@endif
