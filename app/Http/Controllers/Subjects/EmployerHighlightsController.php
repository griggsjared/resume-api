<?php

declare(strict_types=1);

namespace App\Http\Controllers\Subjects;

use App\Domains\Resumes\Data\EmployerHighlightData;
use App\Domains\Resumes\Models\Employer;
use App\Domains\Resumes\Models\EmployerHighlight;
use App\Domains\Resumes\Models\Subject;
use App\Domains\Resumes\Services\EmployersService;
use App\Http\ApiData\EmployerHighlightApiData;
use App\Http\ApiData\PaginatedApiData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subjects\UpsertEmployerHighlightRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployerHighlightsController extends Controller
{
    public function __construct(
        private EmployersService $employersService,
    ) {}

    public function index(Request $request, Subject $subject, Employer $employer): JsonResponse
    {
        $this->authorize('view', $subject);

        $highlights = $employer->highlights();

        if ($request->has('search')) {
            $highlights->search($request->input('search'));
        }

        $order = $request->input('order', 'asc') === 'desc' ? 'desc' : 'asc';

        match ($request->input('order_by')) {
            'sort' => $highlights->orderBy('sort', $order),
            default => $highlights->orderBy('sort', $order),
        };

        /**
         * @var PaginatedApiData<EmployerHighlightApiData>
         */
        $ApiData = PaginatedApiData::fromPaginator(
            $highlights->paginate(
                $request->input('per_page', 20)
            )->withQueryString(),
            EmployerHighlightApiData::class
        );

        return response()->json($ApiData);
    }

    public function store(UpsertEmployerHighlightRequest $request, Subject $subject, Employer $employer): JsonResponse
    {
        $data = $this->employersService->upsertHighlight(
            EmployerHighlightData::from([
                ...$request->validated(),
                'employer' => $employer,
            ])
        );

        return response()->json(EmployerHighlightApiData::from(
            EmployerHighlight::find($data->id)
        ), 201);
    }

    public function show(Subject $subject, Employer $employer, EmployerHighlight $highlight): JsonResponse
    {
        $this->authorize('view', $subject);

        return response()->json(EmployerHighlightApiData::from($highlight));
    }

    public function update(UpsertEmployerHighlightRequest $request, Subject $subject, Employer $employer, EmployerHighlight $highlight): JsonResponse
    {
        $data = $this->employersService->upsertHighlight(
            EmployerHighlightData::from([
                ...$highlight->toArray(),
                ...$request->validated(),
            ])
        );

        return response()->json(EmployerHighlightApiData::from(
            $highlight->refresh()
        ));
    }

    public function destroy(Subject $subject, Employer $employer, EmployerHighlight $highlight): JsonResponse
    {
        $this->authorize('update', $subject);

        $this->employersService->deleteHighlight(
            EmployerHighlightData::from($highlight)
        );

        return response()->json([
            'message' => 'Ok',
        ]);
    }
}
