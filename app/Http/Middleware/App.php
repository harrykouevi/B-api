<?php namespace App\Http\Middleware;

use App\Repositories\UploadRepository;
use Closure;
use Exception;
use Illuminate\Http\Request;

class App
{

    /**
     * @var UploadRepository
     */
    protected UploadRepository $uploadRepository;

    /**
     * @param UploadRepository $uploadRepository
     */
    public function __construct(UploadRepository $uploadRepository)
    {
        $this->uploadRepository = $uploadRepository;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        try {
            $this->uploadRepository = new UploadRepository(app());
            $upload = $this->uploadRepository->findByField('uuid', setting('app_logo', ''))->first();
            $appLogo = asset('images/logo_default.png');
            if ($upload && $upload->hasMedia('app_logo')) {
                $appLogo = $upload->getFirstMediaUrl('app_logo');
            }
            view()->share('app_logo', $appLogo);
        } catch (Exception $exception) {
        }

        return $next($request);
    }

}

