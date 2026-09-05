<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Models\Book;
use App\Services\DigitalBookFactory;
use App\Services\PhysicalBookFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use App\Services\BorrowingService;

class BookController extends Controller
{
    public function __construct(
        private readonly BorrowingService $borrowingService
    ) {
    }

    
    //web and api respomse
    public function index(Request $request): View|JsonResponse
    {
        try {
            
            $query = Book::query();

          
            $search = trim((string) $request->input('search', ''));

            if ($search !== '') {
                $tokens = preg_split('/[\s\p{P}]+/u', $search, -1, PREG_SPLIT_NO_EMPTY);
                if ($tokens === false) {
                    $tokens = [$search];
                }

                $tokens = array_slice(array_values(array_unique($tokens)), 0, 8);

                $query->where(function ($searchQuery) use ($tokens): void {
                    foreach ($tokens as $token) {
                        $like = '%'.$token.'%';
                        $searchQuery->orWhere(function ($tokenQuery) use ($like): void {
                            $tokenQuery->where('title', 'like', $like)
                                ->orWhere('author', 'like', $like)
                                ->orWhere('isbn', 'like', $like)
                                ->orWhere('category', 'like', $like);
                        });
                    }
                });

                $query->orderByRaw(
                    'case
                        when lower(title) = lower(?) then 0
                        when lower(title) like lower(?) then 1
                        when lower(title) like lower(?) then 2
                        when lower(author) like lower(?) then 3
                        when isbn like ? then 4
                        when lower(category) like lower(?) then 5
                        else 6
                    end',
                    [$search, $search.'%', '%'.$search.'%', '%'.$search.'%', '%'.$search.'%', '%'.$search.'%']
                );
            }

            if ($type = $request->input('type')) {
                $query->where('type', $type);
            }

            // 每个 Library Books 页面最多显示五本书
            $books = $query->latest()->paginate(5)->withQueryString();

    
            // 2. Consume Borrow & Return REST JSON API

            try {
                // 模拟外部 HTTP 客户端创建一个发送到 API 的请求
                $apiRequest = \Illuminate\Http\Request::create('/api/v1/borrowings/active-counts', 'GET');
                
                // 让 Laravel 路由直接处理这个请求，并拿回纯 JSON 响应
                $response = \Illuminate\Support\Facades\Route::dispatch($apiRequest);

                // 检查 API 是否成功返回 (HTTP 200 OK)
                if ($response->getStatusCode() === 200) {
                    // 解析 JSON 字符串
                    $responseData = json_decode($response->getContent(), true);
                    $activeCounts = $responseData['data'] ?? [];

                    // 将获取到的借出数量映射到每一本书的属性上
                    foreach ($books as $book) {
                        $book->active_borrowings_count = $activeCounts[$book->id] ?? 0;
                    }
                } else {
                    // API 失败降级处理
                    foreach ($books as $book) { $book->active_borrowings_count = 0; }
                }
            } catch (\Exception $apiException) {
                \Illuminate\Support\Facades\Log::error('Failed to consume borrowing JSON API: ' . $apiException->getMessage());
                foreach ($books as $book) { $book->active_borrowings_count = 0; }
            }

            // Web Service API 
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $books,
                ], Response::HTTP_OK);
            }

            // MVC View 
            return view('books.index', compact('books'));

        } catch (Throwable $e) {
            Log::error('Failed to retrieve books list.', [
                'event' => 'BOOK_INDEX_ERROR',
                'error_message' => $e->getMessage(),
                'ip_address' => $request->ip(),
                'user_id' => $request->user()?->id,
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Internal server error. Unable to fetch books.',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return back()->with('error', 'Unable to retrieve books list at the moment.');
        }
    }
    
    //web mvc
    public function create(): View
    {
        return view('books.create');
    }

    //Factory Method Pattern

    public function store(StoreBookRequest $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            
            $factory = match ($validated['type']) {
                'physical' => new PhysicalBookFactory,
                'ebook' => new DigitalBookFactory,
            };

           
            $book = $factory->registerBook(
                $validated,
                $request->file('cover_image'),
                $request->file('ebook_file')
            );

            DB::commit();

         
            Log::info('Book created successfully.', [
                'event' => 'BOOK_CREATION_SUCCESS',
                'book_id' => $book->id,
                'isbn' => $book->isbn,
                'title' => $book->title,
                'type' => $book->type,
                'performed_by' => $request->user()?->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()->toIso8601String(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Book registered successfully.',
                    'data' => $book,
                ], Response::HTTP_CREATED);
            }

            return redirect()->route('books.index')->with('success', 'Book created successfully.');

        } catch (Throwable $e) {
            DB::rollBack();

        
            Log::error('Book creation failed.', [
                'event' => 'BOOK_CREATION_FAILED',
                'isbn' => $validated['isbn'] ?? 'N/A',
                'error_message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
                'performed_by' => $request->user()?->id,
                'ip_address' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while creating the book record.',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return back()->withInput()->with('error', 'Failed to register book: '.$e->getMessage());
        }
    }

   
    public function show(Request $request, int $id): View|JsonResponse
    {
        try {
            $book = Book::find($id);

            if (! $book) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Book not found.',
                    ], Response::HTTP_NOT_FOUND);
                }

                return redirect()->route('books.index')->with('error', 'Book not found.');
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $book,
                ], Response::HTTP_OK);
            }

            return view('books.show', compact('book'));

        } catch (Throwable $e) {
            Log::error('Failed to fetch book details.', [
                'event' => 'BOOK_SHOW_ERROR',
                'book_id' => $id,
                'error_message' => $e->getMessage(),
                'ip_address' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Internal server error.',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return redirect()->route('books.index')->with('error', 'Error loading book details.');
        }
    }


    public function edit(int $id): View|RedirectResponse
    {
        $book = Book::find($id);

        if (! $book) {
            return redirect()->route('books.index')->with('error', 'Book not found.');
        }

        return view('books.edit', compact('book'));
    }

   
    public function update(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $book = Book::find($id);

        if (! $book) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Book not found.'], Response::HTTP_NOT_FOUND);
            }

            return redirect()->route('books.index')->with('error', 'Book not found.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'total_copies' => $book->isPhysical()
                ? [
                    'required',
                    'integer',
                    'min:0',
                    'max:10000',
                ]
                : [
                    'nullable',
                    'integer',
                    'min:0',
                    'max:10000',
                ],
        ]);

        DB::beginTransaction();

        try {
            if (
                $book->isPhysical()
                && isset($validated['total_copies'])
            ) {
                $book = $this->borrowingService
                    ->updateCopyQuantity(
                        $request->user(),
                        $book,
                        (int) $validated['total_copies']
                    );
            }

            $book->fill([
                'title' => $validated['title'],
                'author' => $validated['author'],
                'category' => $validated['category'],
            ])->save();

            DB::commit();

            Log::info('Book updated successfully.', [
                'event' => 'BOOK_UPDATE_SUCCESS',
                'book_id' => $book->id,
                'performed_by' => $request->user()?->id,
                'ip_address' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Book updated successfully.',
                    'data' => $book,
                ], Response::HTTP_OK);
            }

            return redirect()->route('books.index')->with('success', 'Book updated successfully.');

        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Book update failed.', [
                'event' => 'BOOK_UPDATE_FAILED',
                'book_id' => $id,
                'error_message' => $e->getMessage(),
                'ip_address' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update book.',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return back()->withInput()->with('error', 'Error updating book.');
        }
    }

    /**
     * 删除图书及关联物理文件 (Secure File Deletion & Cross-Module Verification)
     */
    public function destroy(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $book = Book::find($id);

        if (! $book) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Book not found.'], Response::HTTP_NOT_FOUND);
            }
            return redirect()->route('books.index')->with('error', 'Book not found.');
        }

        // =========================================================================
        // 1. Consume REST JSON API (跨模块 API 检查活跃状态)
        // =========================================================================
        $hasActiveBorrowings = false;
        try {
            $apiRequest = \Illuminate\Http\Request::create('/api/v1/borrowings/active-counts', 'GET');
            $response = \Illuminate\Support\Facades\Route::dispatch($apiRequest);
            
            if ($response->getStatusCode() === 200) {
                $responseData = json_decode($response->getContent(), true);
                // 只要大于 0，说明目前还有人没还书
                $hasActiveBorrowings = (($responseData['data'][$book->id] ?? 0) > 0);
            }
        } catch (\Exception $apiException) {
            \Illuminate\Support\Facades\Log::error('API Error: ' . $apiException->getMessage());
        }

        // 仅仅当图书 **正在被借出** 时才拦截删除
        if ($hasActiveBorrowings) {
            $message = 'Cannot delete this book because it is currently borrowed by a user. Please wait until it is returned.';

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            return back()->with('error', $message);
        }

        // =========================================================================
        // 2. 执行安全的级联物理删除 (清理历史记录 + 删除图书)
        // =========================================================================
        DB::beginTransaction();
        try {
            // 【关键修改】在删除书本之前，先清除数据库里关联的历史借阅和预约记录，防止触发外键约束报错！
            $book->borrowings()->delete();
            if (method_exists($book, 'reservations')) {
                $book->reservations()->delete();
            }

            // 安全删除已上传的关联物理文件
            if ($book->cover_image_path && Storage::disk('public')->exists($book->cover_image_path)) {
                Storage::disk('public')->delete($book->cover_image_path);
            }

            if ($book->file_path && Storage::disk('local')->exists($book->file_path)) {
                Storage::disk('local')->delete($book->file_path);
            }

            // 最后删除书本本身
            $book->delete();

            DB::commit();

            Log::info('Book and its historical records deleted successfully.', [
                'event' => 'BOOK_DELETION_SUCCESS',
                'book_id' => $id,
                'performed_by' => $request->user()?->id,
            ]);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Book deleted successfully.'], Response::HTTP_OK);
            }

            return redirect()->route('books.index')->with('success', 'Book deleted successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Book deletion failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to delete book: ' . $e->getMessage());
        }
    }

    

}
