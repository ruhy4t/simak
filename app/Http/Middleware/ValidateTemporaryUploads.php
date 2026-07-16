<?php

namespace App\Http\Middleware;

use App\Services\TemporaryUploadService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateTemporaryUploads
{
    public function __construct(private readonly TemporaryUploadService $temporaryUploads)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            foreach ($this->temporaryUploads->pathsFromInput($request->all()) as $path) {
                $this->temporaryUploads->validateOwned($path, $request->user()->getAuthIdentifier());
            }
        }

        return $next($request);
    }
}
